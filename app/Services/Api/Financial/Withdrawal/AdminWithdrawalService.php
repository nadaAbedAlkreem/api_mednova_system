<?php

namespace App\Services\Api\Financial\Withdrawal;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Enums\WithdrawalStatus;
use App\Events\WithdrawalStatusChanged;
use App\Models\WithdrawalRequest;
use App\Repositories\IWalletRepositories;
use App\Repositories\IWithdrawalRepositories;
use App\Services\Api\Customer\UploadService;
use App\Services\Api\Financial\FinancialTransactionService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWithdrawalService
{
    public function __construct(
        protected IWalletRepositories $wallets,
        protected IWithdrawalRepositories $withdrawals,
        protected FinancialTransactionService $financialTransactionService,
        protected UploadService $uploadService,
    ) {}

    public function process(WithdrawalRequest $withdrawal, $admin, array $data): void
    {
        if ($withdrawal->status !== WithdrawalStatus::PENDING_REVIEW) {
            throw new DomainException(__('messages.WITHDRAWAL_ALREADY_PROCESSED'));
        }

        match ($data['action']) {
            'approve' => $this->approve($withdrawal, $admin, $data),
            'reject'  => $this->reject($withdrawal, $admin, $data),
        };
    }

    private function approve(WithdrawalRequest $withdrawal, $admin, array $data): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $data) {
            $wallet = $this->wallets->getOrCreateByOwnerForUpdate($withdrawal->owner_id);

            if ((float) $wallet->pending_balance < (float) $withdrawal->amount) {
                Log::critical('Admin withdrawal approve: insufficient pending balance', [
                    'withdrawal_id'   => $withdrawal->id,
                    'pending_balance' => $wallet->pending_balance,
                    'amount'          => $withdrawal->amount,
                ]);
                throw new DomainException(__('messages.WITHDRAWAL_INSUFFICIENT_BALANCE'));
            }

            $wallet->decrement('pending_balance', (float) $withdrawal->amount);

            $this->financialTransactionService->createWalletEntry(
                reference:          $withdrawal,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::WITHDRAWAL->value,
                entryType:          EntryType::ENTRY_DEBIT->value,
                walletId:           $wallet->id,
                grossAmount:        (float) $withdrawal->amount,
                netAmount:          (float) $withdrawal->amount,
                currency:           $wallet->currency ?? 'OMR',
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               [
                    'operation'          => 'withdrawal_transferred',
                    'transfer_reference' => $data['transfer_reference'] ?? null,
                    'admin_id'           => $admin->id,
                    'processed_at'       => now()->toIso8601String(),
                ],
            );

            $storedPath = null;
            $fileHash   = null;

            if (isset($data['transfer_proof']) && $data['transfer_proof'] instanceof UploadedFile) {
                $file       = $data['transfer_proof'];
                $fileHash   = hash_file('sha256', $file->getRealPath());
                $storedPath = $this->uploadService->upload(
                    $file,
                    'private/withdrawal-proofs',
                    'local',
                    now()->format('Y/m'),
                );
            }

            $withdrawal->update([
                'status'              => WithdrawalStatus::TRANSFERRED->value,
                'transfer_reference'  => $data['transfer_reference'] ?? null,
                'transfer_proof_path' => $storedPath,
                'transfer_proof_hash' => $fileHash,
                'admin_note'          => $data['admin_note'] ?? null,
                'processed_by_type'   => get_class($admin),
                'processed_by_id'     => $admin->id,
                'processed_at'        => now(),
            ]);

            DB::afterCommit(function () use ($withdrawal) {
                $formatted = number_format((float) $withdrawal->amount, 3, '.', '');

                event(new WithdrawalStatusChanged(
                    $withdrawal,
                    __('messages.withdrawal_transferred_user', [
                        'amount'   => $formatted,
                        'currency' => $withdrawal->currency,
                    ]),
                    'transferred_user',
                    $withdrawal->owner_id,
                    $withdrawal->owner_type,
                ));
            });
        });
    }

    private function reject(WithdrawalRequest $withdrawal, $admin, array $data): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $data) {
            $wallet = $this->wallets->getOrCreateByOwnerForUpdate($withdrawal->owner_id);
            $amount = (float) $withdrawal->amount;

            $wallet->decrement('pending_balance', $amount);
            $wallet->increment('available_balance', $amount);

            $this->financialTransactionService->createWalletEntry(
                reference: $withdrawal,
                gatewayPaymentId: null,
                transactionType: TransactionType::WITHDRAWAL_REVERSAL->value,
                entryType: EntryType::ENTRY_CREDIT->value,
                walletId: $wallet->id,
                grossAmount: $amount,
                netAmount: $amount,
                currency: $wallet->currency ?? 'OMR',
                status: AmountStatus::STATUS_AVAILABLE->value,
                meta: [
                    'operation'              => 'withdrawal_rejected',
                    'reason'                 => $data['admin_note'] ?? null,
                    'withdrawal_request_id'  => $withdrawal->id,
                ],
            );

            $withdrawal->update([
                'status'            => WithdrawalStatus::REJECTED->value,
                'admin_note'        => $data['admin_note'],
                'processed_by_type' => get_class($admin),
                'processed_by_id'   => $admin->id,
                'processed_at'      => now(),
            ]);

            DB::afterCommit(function () use ($withdrawal, $data) {
                event(new WithdrawalStatusChanged(
                    $withdrawal,
                    __('messages.withdrawal_rejected_user', [
                        'reason' => $data['admin_note'] ?? '',
                    ]),
                    'rejected_user',
                    $withdrawal->owner_id,
                    $withdrawal->owner_type,
                ));
            });
        });
    }
}

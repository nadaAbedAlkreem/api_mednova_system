<?php

namespace App\Services\Api\Financial\Withdrawal;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Enums\WithdrawalStatus;
use App\Events\WithdrawalStatusChanged;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\WithdrawalRequest;
use App\Repositories\IBankAccountRepositories;
use App\Repositories\IWalletRepositories;
use App\Repositories\IWithdrawalRepositories;
use App\Services\Api\Financial\FinancialTransactionService;
use DomainException;
use Illuminate\Container\Attributes\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function __construct(
        protected IWalletRepositories $wallets,
        protected IWithdrawalRepositories $withdrawals,
        protected IBankAccountRepositories $bankAccounts,
        protected FinancialTransactionService $financialTransactionService,
    ) {}

    public function requestWithdrawal(Customer $user, float $amount): WithdrawalRequest
    {
        $bankAccount = $this->bankAccounts->findVerifiedByOwner($user->id, get_class($user));

        if (!$bankAccount) {
            throw new DomainException(__('messages.WITHDRAWAL_NO_VERIFIED_BANK_ACCOUNT'));
        }

        $minAmount = config('financial.withdrawal.min_amount', 5.000);
        if ($amount < $minAmount) {
            throw new DomainException(
                __('messages.WITHDRAWAL_AMOUNT_TOO_LOW', [
                    'min' => number_format($minAmount, 3, '.', ''),
                ])
            );
        }

//        $existing = $this->withdrawals->findPendingByOwner($user->id, get_class($user));
//
//        if ($existing) {
//            throw new DomainException(__('messages.WITHDRAWAL_PENDING_EXISTS'));
//        }

        return DB::transaction(function () use ($user, $amount, $bankAccount) {
            $wallet = $this->wallets->getOrCreateByOwnerForUpdate($user->id);

            $existingPending = $this->withdrawals->findPendingByOwner(
                $user->id,
                get_class($user)
            );

            if ($existingPending) {
                throw new DomainException(__('messages.WITHDRAWAL_PENDING_EXISTS'));
            }

            if ((float) $wallet->available_balance < $amount) {
                throw new DomainException(__('messages.WITHDRAWAL_INSUFFICIENT_BALANCE'));
            }

            $wallet->decrement('available_balance', $amount);
            $wallet->increment('pending_balance', $amount);

            $withdrawal = $this->withdrawals->createWithdrawal([
                'owner_type'      => get_class($user),
                'owner_id'        => $user->id,
                'wallet_id'       => $wallet->id,
                'bank_account_id' => $bankAccount->id,
                'amount'          => $amount,
                'currency'        => $wallet->currency ?? 'OMR',
                'status'          => WithdrawalStatus::PENDING_REVIEW->value,
            ]);

            $this->financialTransactionService->createWalletEntry(
                reference: $withdrawal,
                gatewayPaymentId: null,
                transactionType: TransactionType::WITHDRAWAL->value,
                entryType: EntryType::ENTRY_DEBIT->value,
                walletId: $wallet->id,
                grossAmount: $amount,
                netAmount: $amount,
                currency: $wallet->currency ?? 'OMR',
                status: AmountStatus::STATUS_PENDING->value,
                meta: [
                    'operation'       => 'withdrawal_requested',
                    'bank_account_id' => $bankAccount->id,
                ],
            );

            DB::afterCommit(function () use ($user, $withdrawal) {
                $formatted = number_format((float) $withdrawal->amount, 3, '.', '');
                \Illuminate\Support\Facades\Log::info(' service  ');

                event(new WithdrawalStatusChanged(
                    $withdrawal,
                    'withdrawal_requested' // كود موحد للحدث
                ));
//                event(new WithdrawalStatusChanged(
//                    $withdrawal,
//                    __('messages.WITHDRAWAL_REQUESTED_USER', [
//                        'amount'   => $formatted,
//                        'currency' => $withdrawal->currency,
//                    ]),
//                    'withdrawal_requested_user',
//                    $user->id,
//                    get_class($user),
//                ));
//
//                event(new WithdrawalStatusChanged(
//                    $withdrawal,
//                    __('messages.WITHDRAWAL_REQUESTED_ADMIN'),
//                    'withdrawal_requested_admin',
//                    1,
//                    Admin::class,
//                ));
            });

            return $withdrawal;
        });
    }

    public function cancelWithdrawal(Customer $user, int $withdrawalId): void
    {
        $withdrawal = $this->withdrawals->findById($withdrawalId);

        if (!$withdrawal) {
            throw new DomainException(__('messages.WITHDRAWAL_NOT_FOUND'));
        }

        if ($withdrawal->owner_id !== $user->id || $withdrawal->owner_type !== get_class($user)) {
            throw new DomainException(__('messages.WITHDRAWAL_NOT_OWNED'));
        }

        if (!$withdrawal->status->isCancellable()) {
            throw new DomainException(__('messages.WITHDRAWAL_CANNOT_CANCEL'));
        }

        DB::transaction(function () use ($user, $withdrawal) {
            $wallet = $this->wallets->getOrCreateByOwnerForUpdate($user->id);
            $amount = (float) $withdrawal->amount;

            $wallet->decrement('pending_balance', $amount);
            $wallet->increment('available_balance', $amount);

            $this->withdrawals->updateWithdrawal($withdrawal, [
                'status' => WithdrawalStatus::CANCELLED_BY_USER->value,
            ]);

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
                    'operation'     => 'withdrawal_cancelled_by_user',
                    'withdrawal_id' => $withdrawal->id,
                ],
            );

            DB::afterCommit(function () use ($user, $withdrawal) {
                $formatted = number_format((float) $withdrawal->amount, 3, '.', '');
                event(new WithdrawalStatusChanged(
                    $withdrawal,
                   'withdrawal_cancelled'
                ));
            });
        });
    }

    public function getUserWithdrawals(Customer $user, int $perPage): LengthAwarePaginator
    {
        return $this->withdrawals->getByOwner($user->id, get_class($user), $perPage);
    }
}

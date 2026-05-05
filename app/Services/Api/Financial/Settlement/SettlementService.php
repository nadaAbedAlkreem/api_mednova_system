<?php

namespace App\Services\Api\Financial\Settlement;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\FinancialStatus;
use App\Enums\TransactionType;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Repositories\IWalletRepositories;
use App\Services\Api\Financial\FinancialTransactionService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    public function __construct(
        protected FinancialTransactionService $financialTransactionService,
        protected IWalletRepositories $wallets,
    ) {}

    public function settle(ConsultationChatRequest|ConsultationVideoRequest $consultation): void
    {
        // ── Step 1: Pre-transaction validations ──────────────────────────
        if ($consultation->financial_status !== FinancialStatus::REVIEW_WINDOW->value) {
            throw new DomainException(__('messages.SETTLEMENT_REQUIRES_REVIEW_WINDOW'));
        }

        if (!$consultation->review_deadline || !now()->greaterThan($consultation->review_deadline)) {
            throw new DomainException(__('messages.SETTLEMENT_REVIEW_WINDOW_NOT_EXPIRED'));
        }

        $price = (float) $consultation->consultation_price;

        if ($price <= 0) {
            throw new DomainException(__('messages.INVALID_CONSULTATION_AMOUNT'));
        }

        // ── Step 2: Accounting identity check ────────────────────────────
        $earning  = (float) $consultation->consultant_earning_amount;
        $platform = (float) $consultation->platform_commission_amount;
        $sum      = (float) bcadd((string) $earning, (string) $platform, 3);

        if (abs($price - $sum) > 0.001) {
            Log::channel('financial')->critical('settlement.accounting_mismatch', [
                'consultation_id'     => $consultation->id,
                'consultation_price'  => $price,
                'consultant_earning'  => $earning,
                'platform_commission' => $platform,
                'sum'                 => $sum,
                'diff'                => abs($price - $sum),
            ]);
            throw new DomainException(__('messages.SETTLEMENT_ACCOUNTING_MISMATCH'));
        }

        // ── Step 3: DB::transaction ───────────────────────────────────────
        DB::transaction(function () use ($consultation, $price, $earning, $platform) {

            // Lock platform wallet FIRST, then consultant (deadlock prevention)
            $platformWallet   = $this->wallets->getPlatformWallet();
            $consultantWallet = $this->wallets->getOrCreateByOwnerForUpdate(
                $consultation->consultant_id
            );

            // Verify platform has enough in escrow
            if ((float) $platformWallet->pending_balance < $price) {
                Log::channel('financial')->critical('settlement.insufficient_pending', [
                    'consultation_id' => $consultation->id,
                    'required'        => $price,
                    'available'       => $platformWallet->pending_balance,
                ]);
                throw new DomainException(__('messages.INSUFFICIENT_PLATFORM_PENDING_BALANCE'));
            }

            $currency           = $platformWallet->currency ?? 'OMR';
            $consultantCurrency = $consultantWallet->currency ?? 'OMR';

            // Transaction 1 — Release from escrow (debit platform pending)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::CONSULTATION_RELEASE->value,
                entryType:          EntryType::ENTRY_DEBIT->value,
                walletId:           $platformWallet->id,
                grossAmount:        $price,
                netAmount:          $price,
                currency:           $currency,
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               ['operation' => 'settlement_release', 'role' => 'platform_escrow'],
                platformCommission: 0,
                vatAmount:          0,
            );

            // Transaction 2 — Consultant earnings (credit consultant)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::CONSULTATION_CREDIT->value,
                entryType:          EntryType::ENTRY_CREDIT->value,
                walletId:           $consultantWallet->id,
                grossAmount:        $price,
                netAmount:          $earning,
                currency:           $consultantCurrency,
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               ['operation' => 'consultant_earning', 'role' => 'consultant'],
                platformCommission: $platform,
                vatAmount:          0,
            );

            // Transaction 3 — Platform commission (credit platform available)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::PLATFORM_FEE->value,
                entryType:          EntryType::ENTRY_CREDIT->value,
                walletId:           $platformWallet->id,
                grossAmount:        $platform,
                netAmount:          $platform,
                currency:           $currency,
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               ['operation' => 'platform_commission', 'role' => 'platform'],
                platformCommission: 0,
                vatAmount:          0,
            );

            // Update wallet balances (atomic SQL)
            $platformWallet->decrement('pending_balance', $price);
            $platformWallet->increment('available_balance', $platform);
            $consultantWallet->increment('available_balance', $earning);

            // Update consultation
            $consultation->update([
                'financial_status' => FinancialStatus::WITHDRAWABLE->value,
                'settled_at'       => now(),
            ]);

            // ── Step 4: Post-commit notifications ────────────────────────
            DB::afterCommit(function () use ($consultation, $earning, $consultantCurrency) {

                event(new \App\Events\ConsultationRequested(
                    $consultation,
                    __('messages.settlement_completed_patient', [
                        'consultation_id' => $consultation->id,
                        'consultant'      => $consultation->consultant->full_name ?? 'المستشار',
                    ]),
                    'settlement_completed_patient'
                ));

                event(new \App\Events\ConsultationRequested(
                    $consultation,
                    __('messages.settlement_completed_consultant', [
                        'consultation_id' => $consultation->id,
                        'amount'          => number_format($earning, 3),
                        'currency'        => $consultantCurrency,
                    ]),
                    'settlement_completed_consultant'
                ));
            });
        });
    }
}

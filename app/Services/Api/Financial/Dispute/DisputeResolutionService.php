<?php

namespace App\Services\Api\Financial\Dispute;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\FinancialStatus;
use App\Enums\TransactionType;
use App\Events\ConsultationRequested;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\IWalletRepositories;
use App\Services\Api\Financial\FinancialTransactionService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeResolutionService
{
    public function __construct(
        protected FinancialTransactionService $financialTransactionService,
        protected IWalletRepositories         $wallets,
    ) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Patient wins — refund consultation_price from platform frozen to patient available.
     */
    public function resolveForPatient(Dispute $dispute, mixed $admin, ?string $note): void
    {
        DB::transaction(function () use ($dispute, $admin, $note) {

            // ── 1. Re-fetch + lock dispute row ────────────────────────────
            $dispute = Dispute::whereKey($dispute->id)->lockForUpdate()->firstOrFail();

            // ── 2. Status guard (primary idempotency layer) ───────────────
            $this->guardNotResolved($dispute);

            // ── 3. Load + lock consultation row ───────────────────────────
            $consultation = ($dispute->reference_type)::whereKey($dispute->reference_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $dispute->amount;

            // ── 4. Transaction idempotency (secondary layer) ──────────────
            $this->guardNoExistingRelease($consultation);

            // ── 5. Lock wallets (platform first, then patient) ────────────
            $platformWallet = Wallet::where('id', $dispute->wallet_id)->lockForUpdate()->firstOrFail();
            $patientWallet  = $this->wallets->getOrCreateByOwnerForUpdate($consultation->patient_id);

            // ── 6. Frozen balance guard ───────────────────────────────────
            if ((float) $platformWallet->frozen_balance < $amount) {
                Log::channel('financial')->critical('dispute.resolution_failed', [
                    'dispute_id'             => $dispute->id,
                    'consultation_id'        => $consultation->id,
                    'reason'                 => 'insufficient_platform_frozen_balance',
                    'required'               => $amount,
                    'platform_frozen_balance'=> $platformWallet->frozen_balance,
                ]);
                throw new DomainException(__('messages.INSUFFICIENT_PLATFORM_FROZEN_BALANCE'));
            }

            $currency = $platformWallet->currency ?? 'OMR';

            // ── 7. Ledger entries ─────────────────────────────────────────
            $sharedMeta = [
                'dispute_id'   => $dispute->id,
                'resolution'   => 'refund',
                'admin_id'     => $admin->id,
                'admin_note'   => $note,
                'from_balance' => 'frozen_balance',
            ];

            // 7a. Release frozen escrow (debit platform)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::DISPUTE_RELEASE->value,
                entryType:          EntryType::ENTRY_DEBIT->value,
                walletId:           $platformWallet->id,
                grossAmount:        $amount,
                netAmount:          $amount,
                currency:           $currency,
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               array_merge($sharedMeta, ['role' => 'platform_holding']),
                platformCommission: 0,
                vatAmount:          0,
            );

            // 7b. Refund to patient (credit patient)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::REFUND->value,
                entryType:          EntryType::ENTRY_CREDIT->value,
                walletId:           $patientWallet->id,
                grossAmount:        $amount,
                netAmount:          $amount,
                currency:           $patientWallet->currency ?? 'OMR',
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               array_merge($sharedMeta, ['role' => 'patient', 'refund_mode' => 'dispute_resolution']),
                platformCommission: 0,
                vatAmount:          0,
            );

            // ── 8. Wallet balance updates ─────────────────────────────────
            $platformWallet->decrement('frozen_balance', $amount);
            $patientWallet->increment('available_balance', $amount);

            // ── 9. Update dispute ─────────────────────────────────────────
            $dispute->update([
                'status'           => 'resolved',
                'resolution'       => 'refund',
                'resolved_by_type' => get_class($admin),
                'resolved_by_id'   => $admin->id,
                'resolved_at'      => now(),
                'meta'             => array_merge($dispute->meta ?? [], ['admin_note' => $note]),
            ]);

            // ── 10. Update consultation financial status ───────────────────
            $consultation->update([
                'financial_status' => FinancialStatus::REFUNDED_INTERNAL->value,
            ]);

            Log::channel('financial')->info('dispute.resolved_for_patient', [
                'dispute_id'      => $dispute->id,
                'consultation_id' => $consultation->id,
                'amount'          => $amount,
                'currency'        => $currency,
                'admin_id'        => $admin->id,
                'resolved_at'     => now()->toIso8601String(),
            ]);

            // ── 11. Post-commit notifications ─────────────────────────────
            DB::afterCommit(function () use ($consultation, $dispute, $amount, $currency) {
                $consultation->load(['patient', 'consultant']);
                $this->fireNotifications($consultation, $dispute, $amount, $currency, 'refund');
            });
        });
    }

    /**
     * Consultant wins — release consultation_price from platform frozen,
     * split into consultant earning + platform commission.
     */
    public function resolveForConsultant(Dispute $dispute, mixed $admin, ?string $note): void
    {
        DB::transaction(function () use ($dispute, $admin, $note) {

            // ── 1. Re-fetch + lock dispute row ────────────────────────────
            $dispute = Dispute::whereKey($dispute->id)->lockForUpdate()->firstOrFail();

            // ── 2. Status guard ───────────────────────────────────────────
            $this->guardNotResolved($dispute);

            // ── 3. Load + lock consultation row ───────────────────────────
            $consultation = ($dispute->reference_type)::whereKey($dispute->reference_id)
                ->lockForUpdate()
                ->firstOrFail();

            $price    = (float) $consultation->consultation_price;
            $earning  = (float) $consultation->consultant_earning_amount;
            $platform = (float) $consultation->platform_commission_amount;

            // ── 4. Transaction idempotency ────────────────────────────────
            $this->guardNoExistingRelease($consultation);

            // ── 5. Accounting identity check ──────────────────────────────
            $sum = (float) bcadd((string) $earning, (string) $platform, 3);
            if (abs($price - $sum) > 0.001) {
                Log::channel('financial')->critical('dispute.resolution_accounting_mismatch', [
                    'dispute_id'          => $dispute->id,
                    'consultation_id'     => $consultation->id,
                    'consultation_price'  => $price,
                    'consultant_earning'  => $earning,
                    'platform_commission' => $platform,
                    'sum'                 => $sum,
                ]);
                throw new DomainException(__('messages.SETTLEMENT_ACCOUNTING_MISMATCH'));
            }

            // ── 6. Lock wallets (platform first, then consultant) ─────────
            $platformWallet   = Wallet::where('id', $dispute->wallet_id)->lockForUpdate()->firstOrFail();
            $consultantWallet = $this->wallets->getOrCreateByOwnerForUpdate($consultation->consultant_id);

            // ── 7. Frozen balance guard ───────────────────────────────────
            if ((float) $platformWallet->frozen_balance < $price) {
                Log::channel('financial')->critical('dispute.resolution_failed', [
                    'dispute_id'              => $dispute->id,
                    'consultation_id'         => $consultation->id,
                    'reason'                  => 'insufficient_platform_frozen_balance',
                    'required'                => $price,
                    'platform_frozen_balance' => $platformWallet->frozen_balance,
                ]);
                throw new DomainException(__('messages.INSUFFICIENT_PLATFORM_FROZEN_BALANCE'));
            }

            $currency           = $platformWallet->currency ?? 'OMR';
            $consultantCurrency = $consultantWallet->currency ?? 'OMR';

            // ── 8. Ledger entries ─────────────────────────────────────────
            $sharedMeta = [
                'dispute_id'   => $dispute->id,
                'resolution'   => 'release',
                'admin_id'     => $admin->id,
                'admin_note'   => $note,
                'from_balance' => 'frozen_balance',
            ];

            // 8a. Release frozen escrow (debit platform)
            $this->financialTransactionService->createWalletEntry(
                reference:          $consultation,
                gatewayPaymentId:   null,
                transactionType:    TransactionType::DISPUTE_RELEASE->value,
                entryType:          EntryType::ENTRY_DEBIT->value,
                walletId:           $platformWallet->id,
                grossAmount:        $price,
                netAmount:          $price,
                currency:           $currency,
                status:             AmountStatus::STATUS_AVAILABLE->value,
                meta:               array_merge($sharedMeta, ['role' => 'platform_holding']),
                platformCommission: 0,
                vatAmount:          0,
            );

            // 8b. Consultant earnings (credit consultant)
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
                meta:               array_merge($sharedMeta, ['role' => 'consultant']),
                platformCommission: $platform,
                vatAmount:          0,
            );

            // 8c. Platform commission (credit platform available)
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
                meta:               array_merge($sharedMeta, ['role' => 'platform']),
                platformCommission: 0,
                vatAmount:          0,
            );

            // ── 9. Wallet balance updates ─────────────────────────────────
            $platformWallet->decrement('frozen_balance', $price);
            $platformWallet->increment('available_balance', $platform);
            $consultantWallet->increment('available_balance', $earning);

            // ── 10. Update dispute ────────────────────────────────────────
            $dispute->update([
                'status'           => 'resolved',
                'resolution'       => 'release',
                'resolved_by_type' => get_class($admin),
                'resolved_by_id'   => $admin->id,
                'resolved_at'      => now(),
                'meta'             => array_merge($dispute->meta ?? [], ['admin_note' => $note]),
            ]);

            // ── 11. Update consultation ───────────────────────────────────
            $consultation->update([
                'financial_status' => FinancialStatus::WITHDRAWABLE->value,
                'settled_at'       => now(),
            ]);

            Log::channel('financial')->info('dispute.resolved_for_consultant', [
                'dispute_id'      => $dispute->id,
                'consultation_id' => $consultation->id,
                'price'           => $price,
                'earning'         => $earning,
                'platform'        => $platform,
                'currency'        => $currency,
                'admin_id'        => $admin->id,
                'resolved_at'     => now()->toIso8601String(),
            ]);

            // ── 12. Post-commit notifications ─────────────────────────────
            DB::afterCommit(function () use ($consultation, $dispute, $earning, $consultantCurrency) {
                $consultation->load(['patient', 'consultant']);
                $this->fireNotifications($consultation, $dispute, $earning, $consultantCurrency, 'release');
            });
        });
    }

    // =========================================================================
    // Guards
    // =========================================================================

    private function guardNotResolved(Dispute $dispute): void
    {
        if (!in_array($dispute->status, ['opened', 'under_review'], true)) {
            throw new DomainException(__('messages.DISPUTE_ALREADY_RESOLVED'));
        }
    }

    private function guardNoExistingRelease($consultation): void
    {
        $exists = Transaction::query()
            ->where('reference_type', get_class($consultation))
            ->where('reference_id', $consultation->id)
            ->where('transaction_type', TransactionType::DISPUTE_RELEASE->value)
            ->exists();

        if ($exists) {
            throw new DomainException(__('messages.DISPUTE_ALREADY_RESOLVED'));
        }
    }

    // =========================================================================
    // Notifications
    // =========================================================================

    private function fireNotifications(
        $consultation,
        Dispute $dispute,
        float $amount,
        string $currency,
        string $resolution
    ): void {
        $consultationId  = $consultation->id;
        $amountFormatted = number_format($amount, 3);

        if ($resolution === 'refund') {
            $patientMessage    = __('messages.dispute_resolved_refund_patient', [
                'amount'   => $amountFormatted,
                'currency' => $currency,
            ]);
            $consultantMessage = __('messages.dispute_resolved_refund_consultant', [
                'consultation_id' => $consultationId,
            ]);
        } else {
            $patientMessage    = __('messages.dispute_resolved_release_patient', [
                'consultation_id' => $consultationId,
            ]);
            $consultantMessage = __('messages.dispute_resolved_release_consultant', [
                'amount'   => $amountFormatted,
                'currency' => $currency,
            ]);
        }

        event(new ConsultationRequested($consultation, $patientMessage,    'dispute_resolved_patient'));
        event(new ConsultationRequested($consultation, $consultantMessage, 'dispute_resolved_consultant'));
    }
}

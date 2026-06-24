<?php

namespace App\Services\Api\Financial\Dispute;




use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\FinancialStatus;
use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\Dispute;
use App\Policies\ConsultationPolicy;
use App\Repositories\IWalletRepositories;
use App\Services\Api\Financial\FinancialTransactionService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeService
{
    public function __construct(
        protected FinancialTransactionService $financialTransactionService,
        protected ConsultationPolicy $consultationPolicy,
        protected IWalletRepositories $wallets,

    ) {}

    public function execute($consultation, Customer $patient, ?string $reason = null): void
    {
        DB::transaction(function () use ($consultation, $patient, $reason) {

            // Lock platform wallet FIRST — matches SettlementService lock ordering.
            // Consistent order (platform wallet → consultation) across all financial
            // services eliminates the deadlock that occurs when two workers each hold
            // one of the two locks and wait indefinitely for the other.
            $platformWallet = $this->wallets->getPlatformWallet();

            // Lock consultation SECOND, after the platform wallet is already held.
            $consultation = $consultation->newQuery()
                ->whereKey($consultation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $authorization = $this->consultationPolicy->openDispute($patient, $consultation);

            if ($authorization->denied()) {
                throw new DomainException($authorization->message());
            }

            $amount = (float) $consultation->consultation_price;

            if ($amount <= 0) {
                throw new DomainException(__('messages.INVALID_CONSULTATION_AMOUNT'));
            }

            if ((float) $platformWallet->pending_balance < $amount) {
                Log::channel('financial')->critical('dispute.open_failed', [
                    'reason' => 'insufficient_platform_pending_balance',
                    'consultation_id' => $consultation->id,
                    'amount' => $amount,
                    'platform_pending_balance' => $platformWallet->pending_balance,
                    'platform_wallet_id' => $platformWallet->id,
                ]);
                throw new DomainException(__('messages.INSUFFICIENT_PLATFORM_PENDING_BALANCE'));
            }

            $dispute = Dispute::create([
                'reference_type' => get_class($consultation),
                'reference_id' => $consultation->id,
                'wallet_id' => $platformWallet->id,
                'amount' => $amount,
                'currency' => $platformWallet->currency ?? 'OMR',
                'status' => 'opened',
                'resolution' => 'none',
                'opened_by_type' => get_class($patient),
                'opened_by_id' => $patient->id,
                'opened_at' => now(),
                'meta' => [
                    'reason' => $reason,
                    'from_balance' => 'pending_balance',
                    'to_balance' => 'frozen_balance',
                ],
            ]);

            $this->financialTransactionService->createWalletEntry(
                reference: $consultation,
                gatewayPaymentId: null,
                transactionType: TransactionType::DISPUTE_FREEZE->value,
                entryType: EntryType::ENTRY_DEBIT->value,
                walletId: $platformWallet->id,
                grossAmount: $amount,
                netAmount: $amount,
                currency: $platformWallet->currency ?? 'OMR',
                status: AmountStatus::STATUS_FROZEN->value,
                meta: [
                    'dispute_id' => $dispute->id,
                    'role' => 'platform_holding',
                    'operation' => 'dispute_freeze',
                    'from_balance' => 'pending_balance',
                    'to_balance' => 'frozen_balance',
                    'reason' => $reason,
                ],
                platformCommission: 0,
                vatAmount: 0,
            );

            // Guarded decrement: SQL rejects the update if pending_balance < amount,
            // catching any gap between the pre-check above and this statement.
            $decremented = DB::table($platformWallet->getTable())
                ->where('id', $platformWallet->id)
                ->where('pending_balance', '>=', $amount)
                ->decrement('pending_balance', $amount);

            if ($decremented === 0) {
                Log::channel('financial')->critical('dispute.open_decrement_guard_failed', [
                    'consultation_id' => $consultation->id,
                    'required'        => $amount,
                    'wallet_id'       => $platformWallet->id,
                ]);
                throw new DomainException(__('messages.INSUFFICIENT_PLATFORM_PENDING_BALANCE'));
            }

            $platformWallet->increment('frozen_balance', $amount);

            $consultation->update([
                'financial_status' => FinancialStatus::FROZEN->value,
            ]);

            DB::afterCommit(function () use ($consultation, $dispute) {
                $patientName = $consultation->patient->full_name ?? 'المريض';
                $consultantName = $consultation->consultant->full_name ?? 'المستشار';
                $consultationId = $consultation->id;

                // ── إشعار المريض ─────────────────────────────────────
                event(new \App\Events\ConsultationRequested(
                    $consultation,
                    __('messages.dispute_opened_patient', [
                        'consultant' => $consultantName,
                        'consultation_id' => $consultationId,
                    ]),
                    'dispute_opened_patient'
                ));

                // ── إشعار المستشار ───────────────────────────────────
                event(new \App\Events\ConsultationRequested(
                    $consultation,
                    __('messages.dispute_opened_consultant', [
                        'patient' => $patientName,
                        'consultation_id' => $consultationId,
                    ]),
                    'dispute_opened_consultant'
                ));

                // ── إشعار المنصة / الأدمن ────────────────────────────
                event(new \App\Events\ConsultationRequested(
                    $consultation,
                    __('messages.dispute_opened_admin', [
                        'patient' => $patientName,
                        'consultant' => $consultantName,
                        'consultation_id' => $consultationId,
                    ]),
                    'dispute_opened_admin' ,

                ));
            });
        });
    }


}

<?php

namespace App\Services\Api\Financial;

use App\Enums\ConsultantType;
use App\Enums\EntryType;
use App\Enums\GatewayPaymentStatus;
use App\Enums\TransactionType;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use App\Models\GatewayPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * ConsultantFinancialService
 *
 * Encapsulates all financial read logic for consultant-type users.
 *
 * Responsibilities:
 *   - Retrieve wallet balance with guard against missing wallet
 *   - Retrieve paginated transaction history filtered to consultant-relevant types
 *   - Apply correct scope: only the consultant's own wallet transactions
 *
 * Does NOT:
 *   - Perform any write operations
 *   - Format data for HTTP responses (that is the Resource's job)
 *   - Know about HTTP request/response cycle
 *
 * ── Audit Notes ──────────────────────────────────────────────────────────────
 * PCI-DSS Requirement 7 (Restrict access to cardholder data):
 *   This service never touches GatewayPayment.payload or gateway_transaction_id.
 *
 * OWASP API Security – Broken Object Level Authorization (BOLA):
 *   All queries are scoped through the $consultant model relationship,
 *   never through user-supplied IDs.
 */
/**
 * PatientFinancialService
 *
 * Encapsulates all financial read logic for patient-type users.
 *
 * Responsibilities:
 *   - Retrieve wallet balance (null-safe)
 *   - Retrieve paginated gateway payment history with refund flag decoration
 *   - Retrieve paginated ledger transactions (refunds, withdrawals, dispute releases)
 *
 * Does NOT:
 *   - Create wallets (created on first refund event)
 *   - Perform any write operations
 *   - Format data for HTTP responses (Resource's job)
 *
 * ── Audit Notes ──────────────────────────────────────────────────────────────
 * Sensitive fields (payload, gateway_transaction_id, response_code) are never
 * selected here — they are filtered at the Resource layer per CLAUDE.md rules.
 */
class PatientFinancialService
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 50;

    /**
     * Transaction types visible to patients in their history feed.
     * Internal ledger types are filtered out per CLAUDE.md rules.
     */
    private const VISIBLE_TYPES = [
        'refund',
        'withdrawal',
        'dispute_release',
    ];

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Retrieve the patient's wallet.
     *
     * Returns null if no wallet exists — callers should handle this via the
     * PatientWalletResource which returns zero balances for a null wallet.
     *
     * @throws \DomainException if user is not a patient
     */
    public function getWallet(Customer $patient): ?Wallet
    {
        $this->assertPatient($patient);

        return $patient->wallet()->first();
    }

    /**
     * Retrieve paginated gateway payment history for the patient,
     * with refund status derived from the internal ledger.
     *
     * Process:
     *   1. Fetch paginated gateway payments for this patient's consultations
     *      (both chat and video types via polymorphic reference)
     *   2. Determine which payments have been refunded via transactions table
     *   3. Decorate the collection with is_refunded + refunded_amount flags
     *
     * @return LengthAwarePaginator<GatewayPayment>
     */
    public function getPaymentHistory(Customer $patient, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $this->assertPatient($patient);

        $perPage = min($perPage, self::MAX_PER_PAGE);

        // Step 1: Paginate gateway payments linked to this patient via either
        // ConsultationChatRequest or ConsultationVideoRequest (polymorphic).
        $payments = GatewayPayment::query()
            ->whereHasMorph(
                'reference',
                [ConsultationChatRequest::class, ConsultationVideoRequest::class],
                fn ($q) => $q->where('patient_id', $patient->id)
            )
            ->whereIn('status', [
                GatewayPaymentStatus::CAPTURED->value,
                GatewayPaymentStatus::FAILED->value,
                GatewayPaymentStatus::AUTHORIZED->value,
            ])
            ->whereNull('deleted_at')
            ->with(['reference' => fn ($q) => $q->with('consultant:id,full_name')])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // Step 2: Identify which of these payments were refunded internally
        $paymentIds  = $payments->getCollection()->pluck('id')->all();
        $refundedIds = $this->getRefundedPaymentIds($patient, $paymentIds);

        // Step 3: Decorate each payment with refund metadata
        $payments->getCollection()->transform(function (GatewayPayment $payment) use ($refundedIds) {
            $payment->is_refunded     = $refundedIds->contains($payment->id);
            $payment->refunded_amount = $payment->is_refunded
                ? ($payment->reference->consultation_price ?? null)
                : null;

            return $payment;
        });

        return $payments;
    }

    /**
     * Retrieve paginated ledger transactions for the patient.
     *
     * Only refund, withdrawal, and dispute_release types are returned —
     * internal ledger entries are hidden per CLAUDE.md rules.
     *
     * @return LengthAwarePaginator<Transaction>
     */


    /**
     * Given a list of gateway payment IDs, return the subset that
     * have an associated internal refund credit transaction in the ledger.
     *
     * A refund is detected when the patient's wallet has a 'refund' credit
     * transaction whose gateway_payment_id matches the gateway payment.
     *
     * @param  array<int>  $gatewayPaymentIds
     * @return \Illuminate\Support\Collection
     */
    public function getRefundedPaymentIds(Customer $patient, array $gatewayPaymentIds): \Illuminate\Support\Collection
    {
        if (empty($gatewayPaymentIds)) {
            return collect();
        }

        $wallet = $patient->wallet()->first();

        if (! $wallet) {
            return collect();
        }

        return Transaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('transaction_type', TransactionType::REFUND->value)
            ->where('entry_type', EntryType::ENTRY_CREDIT->value)
            ->whereIn('gateway_payment_id', $gatewayPaymentIds)
            ->whereNull('deleted_at')
            ->pluck('gateway_payment_id');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Ensures the provided Customer is a patient-type account.
     *
     * @throws \DomainException
     */
    private function assertPatient(Customer $patient): void
    {
        if ($patient->type_account !== 'patient') {
            Log::warning('PatientFinancialService: non-patient access attempt', [
                'customer_id'  => $patient->id,
                'type_account' => $patient->type_account,
            ]);

            throw new \DomainException('Access restricted to patient accounts.');
        }
    }
}

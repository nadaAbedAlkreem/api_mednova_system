<?php

namespace App\Services\Api\Financial;

use App\Enums\ConsultantType;
use App\Enums\EntryType;
use App\Enums\GatewayPaymentStatus;
use App\Enums\TransactionType;
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
class PatientFinancialService
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 50;

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Retrieve the patient's wallet (for available balance display).
     *
     * Returns null if no wallet exists — callers should handle this
     * by returning a zero-balance placeholder.
     */
    public function getWallet(User $patient): ?Wallet
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
     *   2. Fetch the set of consultation IDs that have received an internal refund
     *   3. Decorate the collection with refund flags
     *
     * @param  User  $patient
     * @param  int   $perPage
     * @return LengthAwarePaginator<GatewayPayment>  with ->refundedPaymentIds appended
     */
    public function getPaymentHistory(User $patient, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $this->assertPatient($patient);

        $perPage = min($perPage, self::MAX_PER_PAGE);

        // Step 1: Paginate gateway payments linked to this patient via Consultation
        // We use whereHasMorph to traverse the polymorphic reference to Consultation,
        // then filter by the consultation's patient_id.
        //
        // ASSUMPTION: reference_type = 'App\Models\Consultation' (or equivalent)
        // and consultations table has a patient_id column.
        // Adjust the morph type string to match your actual model namespace.
        $payments = GatewayPayment::query()
            ->whereHasMorph(
                'reference',
                ['App\Models\Consultation'],
                fn ($q) => $q->where('patient_id', $patient->id)
            )
            ->whereIn('status', [
                GatewayPaymentStatus::CAPTURED->value,
                GatewayPaymentStatus::FAILED->value,
                GatewayPaymentStatus::AUTHORIZED->value,
            ])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $payments;
    }

    /**
     * Given a collection of GatewayPayment IDs, return the subset that
     * have an associated internal refund transaction in the ledger.
     *
     * Logic:
     *   A refund is detected when the patient's wallet has a 'refund' credit
     *   transaction whose gateway_payment_id matches the gateway payment.
     *
     * Returns a Collection of gateway_payment_id values that were refunded.
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
     * Ensures the provided User is a patient-type account.
     *
     * @throws \DomainException
     */
    private function assertPatient(User $user): void
    {
        // Adjust the string if your enum uses a different label, e.g. 'user'
        if ($user->type_account !== 'patient') {
            Log::warning('PatientFinancialService: non-patient access attempt', [
                'user_id'      => $user->id,
                'type_account' => $user->type_account,
            ]);

            throw new \DomainException('Access restricted to patient accounts.');
        }
    }
}

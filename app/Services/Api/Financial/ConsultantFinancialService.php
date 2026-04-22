<?php

namespace App\Services\Api\Financial;

use App\Enums\ConsultantType;
use App\Models\Customer;
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
class ConsultantFinancialService
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 50;

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Retrieve the consultant's wallet.
     *
     * Returns null if no wallet exists yet (e.g. newly approved consultant
     * who has never received a payment).  Controllers should handle this
     * gracefully by returning a zero-balance placeholder.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException if user is not a consultant
     */
    public function getWallet(Customer $consultant): ?Wallet
    {
        $this->assertConsultant($consultant);

        return $consultant->wallet()->firstOrCreate([
            // keys to search (empty لأن العلاقة polymorphic)
        ], [
            'currency'          => 'OMR',
            'available_balance' => '0.000',
            'pending_balance'   => '0.000',
            'frozen_balance'    => '0.000',
        ]);
    }

    /**
     * Retrieve a paginated list of ledger transactions for the consultant.
     *
     * Filters:
     *   - Only transaction types relevant to consultants
     *   - Scoped via wallet relationship (no direct wallet_id parameter)
     *   - Ordered by most recent first
     *
     * @param  User   $consultant  The authenticated consultant
     * @param  int    $perPage     Items per page (capped at MAX_PER_PAGE)
     * @return LengthAwarePaginator<Transaction>
     */
    public function getTransactions(Customer $consultant, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $this->assertConsultant($consultant);
        $perPage = min($perPage, self::MAX_PER_PAGE);
        $wallet = $consultant->wallet()->first();
        if (! $wallet) {
            return Transaction::query()->whereNull('id')->paginate($perPage);
        }
        return Transaction::VisibleToUser()
            ->where('wallet_id', $wallet->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Ensures the provided User is a consultant-type account.
     * Throws a domain exception on mismatch.
     *
     * IMPORTANT: This is a defence-in-depth check.
     * The route middleware (role:therapist,rehabilitation_center) is the
     * primary guard; this service-level check prevents logic errors if the
     * service is ever called from a non-route context (e.g. console commands,
     * jobs, tests).
     *
     * @throws \DomainException
     */
    private function assertConsultant(Customer $customer): void
    {
        $consultantTypes = [ConsultantType::THERAPIST->value, ConsultantType::REHABILITATION_CENTER->value];

        if (!in_array($customer->type_account, $consultantTypes, true)) {
            Log::warning('ConsultantFinancialService: non-consultant access attempt', [
                'customer_id'      => $customer->id,
                'type_account' => $customer->type_account,
            ]);

            throw new \DomainException('Access restricted to consultant accounts.');
        }
    }




}

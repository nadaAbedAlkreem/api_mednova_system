<?php

namespace App\Services\Api\Financial;

use App\Enums\ConsultantType;
use App\Enums\TransactionType;
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
 *   - Retrieve wallet balance (null-safe, no auto-creation)
 *   - Retrieve paginated transaction history filtered to consultant-visible types
 *   - Apply correct scope: only the consultant's own wallet transactions
 *
 * Does NOT:
 *   - Create wallets (responsibility of payment webhook / payout services)
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

    /**
     * Transaction types visible to consultants in their history feed.
     * Internal ledger types (consultation_hold, platform_fee, payment_record,
     * consultation_release) are filtered out per CLAUDE.md rules.
     */

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Retrieve the consultant's wallet.
     *
     * Returns null if no wallet exists yet. The caller (Resource layer) must
     * handle null gracefully by returning zero balances.
     *
     * Note: wallet creation is intentionally NOT performed here. A consultant
     * wallet is created by the payout/webhook service on first earning event.
     *
     * @throws \DomainException if user is not a consultant
     */
    public function getWallet(Customer $consultant): ?Wallet
    {
        $this->assertConsultant($consultant);

        return $consultant->wallet()->first();
    }

    /**
     * Retrieve paginated ledger transactions for the consultant.
     *
     * Filters applied:
     *   - Scoped to the consultant's own wallet
     *   - Only VISIBLE_TYPES are returned (internal ledger types hidden)
     *   - Soft-deleted records excluded
     *   - Most recent first
     *
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

        return Transaction::query()
            ->where('wallet_id', $wallet->id)
            ->whereIn('transaction_type', array_map(fn ($type) => $type->value, TransactionType::visibleForConsultant()))
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Ensures the provided Customer is a consultant-type account.
     * Throws a domain exception on mismatch.
     *
     * IMPORTANT: This is a defence-in-depth check.
     * The route middleware (role:therapist,rehabilitation_center) is the
     * primary guard; this service-level check prevents logic errors if the
     * service is ever called from a non-route context (console commands,
     * jobs, tests).
     *
     * @throws \DomainException
     */
    private function assertConsultant(Customer $customer): void
    {
        $consultantTypes = [
            ConsultantType::THERAPIST->value,
            ConsultantType::REHABILITATION_CENTER->value,
        ];

        if (! in_array($customer->type_account, $consultantTypes, true)) {
            Log::warning('ConsultantFinancialService: non-consultant access attempt', [
                'customer_id'  => $customer->id,
                'type_account' => $customer->type_account,
            ]);

            throw new \DomainException('Access restricted to consultant accounts.');
        }
    }
}

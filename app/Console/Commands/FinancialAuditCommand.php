<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\GatewayPayment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FinancialAuditCommand extends Command
{
    protected $signature = 'financial:audit {--fix : Reserved for future use — not implemented}';

    protected $description = 'Read-only audit of financial data integrity across the entire system';

    public function handle(): int
    {
        if ($this->option('fix')) {
            $this->warn('--fix is not implemented yet.');
        }

        $this->line('');
        $this->info('Starting Financial System Audit — ' . now()->format('Y-m-d H:i:s'));
        $this->line('');

        Log::channel('financial')->info('audit.started', ['at' => now()->toIso8601String()]);

        $results   = [];
        $results[] = $this->checkAccountingIdentity();            // 1
        $results[] = $this->checkPlatformWallet();               // 2
        $results[] = $this->checkConsultantWallets();            // 3
        $results[] = $this->checkPatientWallets();               // 4
        $results[] = $this->checkFinancialStatusConsistency();   // 5
        $results[] = $this->checkOrphanedRecords();              // 6
        $results[] = $this->checkGatewayPayments();               // 7
        $results[] = $this->checkPendingWithdrawals();           // 8
        $results[] = $this->checkDuplicateTransactions();        // 9
        $results[] = $this->checkOverdueReviewWindows();         // 10

        $this->printDetails($results);
        $this->printSummary($results);

        $hasCritical = collect($results)->contains(fn ($r) => $r['status'] === 'critical');

        Log::channel('financial')->info('audit.completed', [
            'critical' => collect($results)->where('status', 'critical')->count(),
            'warning'  => collect($results)->where('status', 'warning')->count(),
            'passed'   => collect($results)->where('status', 'passed')->count(),
        ]);

        return $hasCritical ? 1 : 0;
    }

    // =========================================================================
    // Check 1 — Accounting Identity
    // =========================================================================

    private function checkAccountingIdentity(): array
    {
        $name    = 'Check 1 — Accounting Identity';
        $details = [];

        $tables = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];

        foreach ($tables as $model) {
            $model::where('financial_status', '!=', 'unpaid')
                ->whereNull('deleted_at')
                ->chunkById(200, function ($consultations) use (&$details) {
                    foreach ($consultations as $c) {
                        $price    = (float) $c->consultation_price;
                        $earning  = (float) $c->consultant_earning_amount;
                        $platform = (float) $c->platform_commission_amount;
                        $sum      = (float) bcadd((string) $earning, (string) $platform, 3);

                        if (abs($price - $sum) > 0.001) {
                            $msg = sprintf(
                                '[CRITICAL] Check 1 — Consultation #%d: price=%.3f ≠ earning(%.3f) + commission(%.3f) = %.3f',
                                $c->id, $price, $earning, $platform, $sum
                            );
                            $details[] = $msg;
                            Log::channel('financial')->critical('audit.check1.mismatch', [
                                'consultation_id'     => $c->id,
                                'consultation_price'  => $price,
                                'consultant_earning'  => $earning,
                                'platform_commission' => $platform,
                                'sum'                 => $sum,
                                'diff'                => abs($price - $sum),
                            ]);
                        }
                    }
                });
        }

        $status = empty($details) ? 'passed' : 'critical';
        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check1.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 2 — Platform Wallet Reconciliation
    // =========================================================================

    private function checkPlatformWallet(): array
    {
        $name    = 'Check 2 — Platform Wallet';
        $details = [];

        $platformWallet = Wallet::where('owner_type', 'platform')
            ->where('owner_id', 1)
            ->where('currency', 'OMR')
            ->whereNull('deleted_at')
            ->first();

        if (!$platformWallet) {
            $details[] = '[CRITICAL] Check 2 — Platform wallet record not found.';
            Log::channel('financial')->critical('audit.check2.no_wallet');
            return ['name' => $name, 'status' => 'critical', 'details' => $details];
        }

        $wid = $platformWallet->id;

        // ── expected_pending ─────────────────────────────────────────────
        $holdCredit    = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'consultation_hold')
            ->where('entry_type', 'credit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $releaseDebit  = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'consultation_release')
            ->where('entry_type', 'debit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $freezeDebit   = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'dispute_freeze')
            ->where('entry_type', 'debit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $refundPlatformDebit = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'refund')
            ->where('entry_type', 'debit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $expectedPending = $holdCredit - $releaseDebit - $freezeDebit - $refundPlatformDebit;

        // ── expected_available ───────────────────────────────────────────
        $platformFeeCredit = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'platform_fee')
            ->where('entry_type', 'credit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $expectedAvailable = $platformFeeCredit;

        // ── expected_frozen ──────────────────────────────────────────────
        $disputeReleaseDebit = (float) Transaction::where('wallet_id', $wid)
            ->where('transaction_type', 'dispute_release')
            ->where('entry_type', 'debit')
            ->whereNull('deleted_at')
            ->sum('net_amount');

        $expectedFrozen = $freezeDebit - $disputeReleaseDebit;

        // ── Compare ──────────────────────────────────────────────────────
        $actualPending   = (float) $platformWallet->pending_balance;
        $actualAvailable = (float) $platformWallet->available_balance;
        $actualFrozen    = (float) $platformWallet->frozen_balance;

        $status = 'passed';

        if (abs($expectedPending - $actualPending) > 0.001) {
            $details[] = sprintf(
                '[CRITICAL] Check 2 — Platform pending_balance: expected=%.3f, actual=%.3f, diff=%.3f',
                $expectedPending, $actualPending, abs($expectedPending - $actualPending)
            );
            $status = 'critical';
        }

        if (abs($expectedAvailable - $actualAvailable) > 0.001) {
            $details[] = sprintf(
                '[CRITICAL] Check 2 — Platform available_balance: expected=%.3f, actual=%.3f, diff=%.3f',
                $expectedAvailable, $actualAvailable, abs($expectedAvailable - $actualAvailable)
            );
            $status = 'critical';
        }

        if (abs($expectedFrozen - $actualFrozen) > 0.001) {
            $details[] = sprintf(
                '[CRITICAL] Check 2 — Platform frozen_balance: expected=%.3f, actual=%.3f, diff=%.3f',
                $expectedFrozen, $actualFrozen, abs($expectedFrozen - $actualFrozen)
            );
            $status = 'critical';
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check2.passed');
        } else {
            Log::channel('financial')->critical('audit.check2.failed', ['details' => $details]);
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 3 — Consultant Wallet Reconciliation
    // =========================================================================

    private function checkConsultantWallets(): array
    {
        $name    = 'Check 3 — Consultant Wallets';
        $details = [];

        $consultantIds = Customer::whereIn('type_account', ['therapist', 'rehabilitation_center'])
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($consultantIds->isEmpty()) {
            return ['name' => $name, 'status' => 'passed', 'details' => []];
        }

        $wallets = Wallet::where('owner_type', Customer::class)
            ->whereIn('owner_id', $consultantIds)
            ->whereNull('deleted_at')
            ->get();

        $status = 'passed';

        foreach ($wallets as $wallet) {
            $wid = $wallet->id;
            $oid = $wallet->owner_id;

            // expected_available
            $creditCredit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'consultation_credit')
                ->where('entry_type', 'credit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $withdrawalDebit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'withdrawal')
                ->where('entry_type', 'debit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $reversalCredit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'withdrawal_reversal')
                ->where('entry_type', 'credit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            // NOTE: In the current implementation the consultant's earning is NOT credited to
            // their wallet until the review window expires and settlement runs. If a dispute is
            // opened during review_window the funds remain frozen in the PLATFORM wallet (escrow),
            // so no dispute_freeze debit is ever written to the consultant's wallet — this query
            // will always return 0. It is kept here for future compatibility in case a feature is
            // added that freezes funds directly in the consultant's wallet.
            $disputeFreezeDebit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'dispute_freeze')
                ->where('entry_type', 'debit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            // NOTE: dispute_release credit on a consultant wallet DOES occur today: when a dispute
            // is resolved in the consultant's favour, their earning (consultant_earning_amount) is
            // credited from the platform's frozen balance directly to consultant.available_balance
            // via a dispute_release transaction.
            $disputeReleaseCredit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'dispute_release')
                ->where('entry_type', 'credit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $expectedAvailable = $creditCredit
                - $withdrawalDebit
                + $reversalCredit
                - $disputeFreezeDebit
                + $disputeReleaseCredit;

            // expected_pending (active withdrawal requests)
            $expectedPending = (float) WithdrawalRequest::where('owner_id', $oid)
                ->where('owner_type', Customer::class)
                ->where('status', 'pending_review')
                ->whereNull('deleted_at')
                ->sum('amount');

            $actualAvailable = (float) $wallet->available_balance;
            $actualPending   = (float) $wallet->pending_balance;

            if (abs($expectedAvailable - $actualAvailable) > 0.001) {
                $details[] = sprintf(
                    '[CRITICAL] Check 3 — Consultant wallet #%d (owner_id=%d) available: expected=%.3f, actual=%.3f, diff=%.3f',
                    $wid, $oid, $expectedAvailable, $actualAvailable, abs($expectedAvailable - $actualAvailable)
                );
                $status = 'critical';
                Log::channel('financial')->critical('audit.check3.available_mismatch', [
                    'wallet_id'    => $wid,
                    'owner_id'     => $oid,
                    'expected'     => $expectedAvailable,
                    'actual'       => $actualAvailable,
                ]);
            }

            if (abs($expectedPending - $actualPending) > 0.001) {
                $details[] = sprintf(
                    '[CRITICAL] Check 3 — Consultant wallet #%d (owner_id=%d) pending: expected=%.3f, actual=%.3f, diff=%.3f',
                    $wid, $oid, $expectedPending, $actualPending, abs($expectedPending - $actualPending)
                );
                $status = 'critical';
                Log::channel('financial')->critical('audit.check3.pending_mismatch', [
                    'wallet_id' => $wid,
                    'owner_id'  => $oid,
                    'expected'  => $expectedPending,
                    'actual'    => $actualPending,
                ]);
            }
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check3.passed', ['wallets_checked' => $wallets->count()]);
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 4 — Patient Wallet Reconciliation
    // =========================================================================

    private function checkPatientWallets(): array
    {
        $name    = 'Check 4 — Patient Wallets';
        $details = [];

        $patientIds = Customer::where('type_account', 'patient')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($patientIds->isEmpty()) {
            return ['name' => $name, 'status' => 'passed', 'details' => []];
        }

        $wallets = Wallet::where('owner_type', Customer::class)
            ->whereIn('owner_id', $patientIds)
            ->whereNull('deleted_at')
            ->get();

        $status = 'passed';

        foreach ($wallets as $wallet) {
            $wid = $wallet->id;
            $oid = $wallet->owner_id;

            $refundCredit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'refund')
                ->where('entry_type', 'credit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $withdrawalDebit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'withdrawal')
                ->where('entry_type', 'debit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $reversalCredit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'withdrawal_reversal')
                ->where('entry_type', 'credit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

            $expectedAvailable = $refundCredit - $withdrawalDebit + $reversalCredit;
            $actualAvailable   = (float) $wallet->available_balance;

            if (abs($expectedAvailable - $actualAvailable) > 0.001) {
                $details[] = sprintf(
                    '[CRITICAL] Check 4 — Patient wallet #%d (owner_id=%d) available: expected=%.3f, actual=%.3f, diff=%.3f',
                    $wid, $oid, $expectedAvailable, $actualAvailable, abs($expectedAvailable - $actualAvailable)
                );
                $status = 'critical';
                Log::channel('financial')->critical('audit.check4.available_mismatch', [
                    'wallet_id' => $wid,
                    'owner_id'  => $oid,
                    'expected'  => $expectedAvailable,
                    'actual'    => $actualAvailable,
                ]);
            }
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check4.passed', ['wallets_checked' => $wallets->count()]);
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 5 — Financial Status Consistency
    // =========================================================================

    private function checkFinancialStatusConsistency(): array
    {
        $name    = 'Check 5 — Financial Status Consistency';
        $details = [];
        $status  = 'passed';

        $tables = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];

        foreach ($tables as $model) {
            $shortClass = class_basename($model);

            $model::whereNotIn('financial_status', ['unpaid', 'withdrawn', 'payment_suspended'])
                ->whereNull('deleted_at')
                ->chunkById(200, function ($consultations) use (&$details, &$status, $model, $shortClass) {
                    foreach ($consultations as $c) {
                        $fs = $c->financial_status instanceof \BackedEnum
                            ? $c->financial_status->value
                            : (string) $c->financial_status;

                        $txExists = fn (string $type) => Transaction::where('reference_type', $model)
                            ->where('reference_id', $c->id)
                            ->where('transaction_type', $type)
                            ->whereNull('deleted_at')
                            ->exists();

                        if ($fs === 'held') {
                            if (!$txExists('consultation_hold')) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (held): missing consultation_hold transaction', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.held_no_hold', ['model' => $shortClass, 'id' => $c->id]);
                            }
                            if ($txExists('consultation_release')) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (held): unexpected consultation_release transaction', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.held_has_release', ['model' => $shortClass, 'id' => $c->id]);
                            }
                        }

                        if ($fs === 'review_window') {
                            if (empty($c->review_deadline)) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (review_window): review_deadline is null', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.review_window_no_deadline', ['model' => $shortClass, 'id' => $c->id]);
                            }
                        }

                        if ($fs === 'withdrawable') {
                            if (!$txExists('consultation_credit')) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (withdrawable): missing consultation_credit transaction', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.withdrawable_no_credit', ['model' => $shortClass, 'id' => $c->id]);
                            }
                            if (!$txExists('platform_fee')) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (withdrawable): missing platform_fee transaction', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.withdrawable_no_fee', ['model' => $shortClass, 'id' => $c->id]);
                            }
                        }

                        if ($fs === 'frozen') {
                            $hasOpenDispute = Dispute::where('reference_type', $model)
                                ->where('reference_id', $c->id)
                                ->whereIn('status', ['opened', 'under_review'])
                                ->whereNull('deleted_at')
                                ->exists();
                            if (!$hasOpenDispute) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (frozen): no open/under_review dispute found', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.frozen_no_dispute', ['model' => $shortClass, 'id' => $c->id]);
                            }
                        }

                        if ($fs === 'refunded_internal') {
                            if (!$txExists('refund')) {
                                $details[] = sprintf('[WARNING] Check 5 — %s #%d (refunded_internal): missing refund transaction', $shortClass, $c->id);
                                $status = 'warning';
                                Log::channel('financial')->warning('audit.check5.refunded_no_tx', ['model' => $shortClass, 'id' => $c->id]);
                            }
                        }
                    }
                });
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check5.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 6 — Orphaned Records
    // =========================================================================

    private function checkOrphanedRecords(): array
    {
        $name    = 'Check 6 — Orphaned Records';
        $details = [];

        $nullWalletCount = Transaction::whereNull('wallet_id')
            ->whereNull('deleted_at')
            ->count();

        if ($nullWalletCount > 0) {
            $details[] = sprintf('[WARNING] Check 6 — %d transaction(s) have null wallet_id', $nullWalletCount);
            Log::channel('financial')->warning('audit.check6.null_wallet_id', ['count' => $nullWalletCount]);
        }

        $existingWalletIds = Wallet::whereNull('deleted_at')->pluck('id');

        $orphanCount = Transaction::whereNotNull('wallet_id')
            ->whereNull('deleted_at')
            ->whereNotIn('wallet_id', $existingWalletIds)
            ->count();

        if ($orphanCount > 0) {
            $details[] = sprintf('[WARNING] Check 6 — %d transaction(s) reference a wallet that no longer exists', $orphanCount);
            Log::channel('financial')->warning('audit.check6.missing_wallet', ['count' => $orphanCount]);
        }

        $status = empty($details) ? 'passed' : 'warning';

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check6.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 7 — Gateway Payment Amounts
    // =========================================================================

    private function checkGatewayPayments(): array
    {
        $name    = 'Check 7 — Gateway Payment Amounts';
        $details = [];
        $status  = 'passed';

        $tables = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];

        foreach ($tables as $model) {
            $shortClass = class_basename($model);

            GatewayPayment::where('status', 'captured')
                ->where('reference_type', $model)
                ->whereNull('deleted_at')
                ->chunkById(200, function ($payments) use (&$details, &$status, $model, $shortClass) {
                    foreach ($payments as $payment) {
                        $consultation = $model::withTrashed()->find($payment->reference_id);

                        if (!$consultation) {
                            $details[] = sprintf(
                                '[CRITICAL] Check 7 — GatewayPayment #%d: referenced %s #%d not found',
                                $payment->id, $shortClass, $payment->reference_id
                            );
                            $status = 'critical';
                            Log::channel('financial')->critical('audit.check7.missing_consultation', [
                                'payment_id'     => $payment->id,
                                'reference_type' => $model,
                                'reference_id'   => $payment->reference_id,
                            ]);
                            continue;
                        }

                        $paymentAmount     = (float) $payment->amount;
                        $consultationGross = (float) $consultation->gross_amount;

                        if (abs($paymentAmount - $consultationGross) > 0.001) {
                            $details[] = sprintf(
                                '[CRITICAL] Check 7 — GatewayPayment #%d ↔ %s #%d: payment.amount=%.3f ≠ gross_amount=%.3f, diff=%.3f',
                                $payment->id, $shortClass, $consultation->id,
                                $paymentAmount, $consultationGross,
                                abs($paymentAmount - $consultationGross)
                            );
                            $status = 'critical';
                            Log::channel('financial')->critical('audit.check7.amount_mismatch', [
                                'payment_id'      => $payment->id,
                                'consultation_id' => $consultation->id,
                                'payment_amount'  => $paymentAmount,
                                'gross_amount'    => $consultationGross,
                                'diff'            => abs($paymentAmount - $consultationGross),
                            ]);
                        }
                    }
                });
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check7.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 8 — Pending Withdrawals vs Wallet
    // =========================================================================

    private function checkPendingWithdrawals(): array
    {
        $name    = 'Check 8 — Pending Withdrawals';
        $details = [];
        $status  = 'passed';

        $wallets = Wallet::where('owner_type', Customer::class)
            ->whereNull('deleted_at')
            ->get();

        foreach ($wallets as $wallet) {
            $oid = $wallet->owner_id;

            $expectedPending = (float) WithdrawalRequest::where('owner_id', $oid)
                ->where('owner_type', Customer::class)
                ->where('status', 'pending_review')
                ->whereNull('deleted_at')
                ->sum('amount');

            $actualPending = (float) $wallet->pending_balance;

            if (abs($expectedPending - $actualPending) > 0.001) {
                $details[] = sprintf(
                    '[WARNING] Check 8 — Wallet #%d (owner_id=%d) pending_balance: expected=%.3f, actual=%.3f, diff=%.3f',
                    $wallet->id, $oid, $expectedPending, $actualPending, abs($expectedPending - $actualPending)
                );
                $status = 'warning';
                Log::channel('financial')->warning('audit.check8.pending_mismatch', [
                    'wallet_id' => $wallet->id,
                    'owner_id'  => $oid,
                    'expected'  => $expectedPending,
                    'actual'    => $actualPending,
                ]);
            }
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check8.passed', ['wallets_checked' => $wallets->count()]);
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 9 — Duplicate Transactions
    // =========================================================================

    private function checkDuplicateTransactions(): array
    {
        $name    = 'Check 9 — Duplicate Transactions';
        $details = [];
        $status  = 'passed';

        $tables = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];

        $typesToCheck = [
            'consultation_hold',
            'consultation_release',
            'consultation_credit',
            'platform_fee',
        ];

        foreach ($tables as $model) {
            $shortClass = class_basename($model);

            foreach ($typesToCheck as $txType) {
                $duplicates = Transaction::where('reference_type', $model)
                    ->where('transaction_type', $txType)
                    ->whereNull('deleted_at')
                    ->selectRaw('reference_id, COUNT(*) as cnt')
                    ->groupBy('reference_id')
                    ->having('cnt', '>', 1)
                    ->get();

                foreach ($duplicates as $row) {
                    $details[] = sprintf(
                        '[CRITICAL] Check 9 — %s #%d has %d "%s" transactions (expected at most 1)',
                        $shortClass, $row->reference_id, $row->cnt, $txType
                    );
                    $status = 'critical';
                    Log::channel('financial')->critical('audit.check9.duplicate_transaction', [
                        'model'            => $shortClass,
                        'consultation_id'  => $row->reference_id,
                        'transaction_type' => $txType,
                        'count'            => $row->cnt,
                    ]);
                }
            }
        }

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check9.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Check 10 — Overdue Review Windows
    // =========================================================================

    private function checkOverdueReviewWindows(): array
    {
        $name      = 'Check 10 — Overdue Review Windows';
        $details   = [];
        $threshold = now()->subHour();

        $tables = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];

        foreach ($tables as $model) {
            $shortClass = class_basename($model);

            $model::where('financial_status', 'review_window')
                ->whereNotNull('review_deadline')
                ->where('review_deadline', '<', $threshold)
                ->whereNull('deleted_at')
                ->chunkById(200, function ($consultations) use (&$details, $shortClass) {
                    foreach ($consultations as $c) {
                        $details[] = sprintf(
                            '[WARNING] Check 10 — %s #%d: financial_status=review_window, review_deadline=%s (overdue)',
                            $shortClass, $c->id, $c->review_deadline
                        );
                        Log::channel('financial')->warning('audit.check10.overdue_review_window', [
                            'model'           => $shortClass,
                            'consultation_id' => $c->id,
                            'review_deadline' => (string) $c->review_deadline,
                        ]);
                    }
                });
        }

        $status = empty($details) ? 'passed' : 'warning';

        if ($status === 'passed') {
            Log::channel('financial')->info('audit.check10.passed');
        }

        return ['name' => $name, 'status' => $status, 'details' => $details];
    }

    // =========================================================================
    // Output Helpers
    // =========================================================================

    private function printDetails(array $results): void
    {
        foreach ($results as $result) {
            if ($result['status'] === 'passed' || empty($result['details'])) {
                continue;
            }
            foreach ($result['details'] as $line) {
                if (str_starts_with($line, '[CRITICAL]')) {
                    $this->error($line);
                } else {
                    $this->warn($line);
                }
            }
        }

        if (collect($results)->contains(fn ($r) => $r['status'] !== 'passed')) {
            $this->line('');
        }
    }

    private function printSummary(array $results): void
    {
        $criticalCount = collect($results)->where('status', 'critical')->count();
        $warningCount  = collect($results)->where('status', 'warning')->count();
        $passedCount   = collect($results)->where('status', 'passed')->count();

        $width = 52; // inner width between ║ chars

        $border  = '╔' . str_repeat('═', $width) . '╗';
        $divider = '╠' . str_repeat('═', $width) . '╣';
        $bottom  = '╚' . str_repeat('═', $width) . '╝';

        $pad = fn (string $text) => '║ ' . str_pad($text, $width - 2) . ' ║';

        $this->line($border);
        $this->line($pad('Financial System Audit Report'));
        $this->line($pad(now()->format('Y-m-d H:i:s')));
        $this->line($divider);

        foreach ($results as $result) {
            $icon = match ($result['status']) {
                'passed'   => '✅ PASSED',
                'warning'  => '⚠️  ' . count($result['details']) . ' WARN' . (count($result['details']) !== 1 ? 'S' : ''),
                'critical' => '❌ ' . count($result['details']) . ' CRITICAL',
                default    => '❓ UNKNOWN',
            };
            // Truncate name to keep line from overflowing
            $name = mb_substr($result['name'], 0, 32);
            $line = '║ ' . str_pad($name, 33) . str_pad($icon, $width - 35) . ' ║';
            $this->line($line);
        }

        $this->line($divider);
        $summary = sprintf('CRITICAL: %d | WARNING: %d | PASSED: %d', $criticalCount, $warningCount, $passedCount);
        $this->line($pad($summary));
        $this->line($bottom);
        $this->line('');
    }
}

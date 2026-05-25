<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
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
        $results[] = $this->checkAccountingIdentity();       // 1
        $results[] = $this->checkPlatformWallet();           // 2
        $results[] = $this->checkConsultantWallets();        // 3
        // Checks 4-10 are added in Part 2 via artisan make:command --force rewrite

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

            $disputeFreezeDebit = (float) Transaction::where('wallet_id', $wid)
                ->where('transaction_type', 'dispute_freeze')
                ->where('entry_type', 'debit')
                ->whereNull('deleted_at')
                ->sum('net_amount');

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

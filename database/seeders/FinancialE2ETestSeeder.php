<?php

namespace Database\Seeders;

use App\Enums\AmountStatus;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Models\Admin;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\GatewayPayment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\Api\Financial\BankAccount\BankAccountService;
use App\Services\Api\Financial\ConsultationRefundService;
use App\Services\Api\Financial\Dispute\DisputeResolutionService;
use App\Services\Api\Financial\Dispute\DisputeService;
use App\Services\Api\Financial\FinancialTransactionService;
use App\Services\Api\Financial\Settlement\SettlementService;
use App\Services\Api\Financial\Withdrawal\AdminWithdrawalService;
use App\Services\Api\Financial\Withdrawal\WithdrawalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FinancialE2ETestSeeder extends Seeder
{
    // ── Financial constants (golden rule: EARN_AMT + PLAT_AMT = PRICE) ───────
    private const PRICE     = 100.000;
    private const GW_RATE   = 0.020;   // 2 %
    private const GW_AMT    = 2.000;
    private const GROSS     = 102.000;
    private const PLAT_RATE = 0.100;   // 10 %
    private const PLAT_AMT  = 10.000;
    private const EARN_AMT  = 90.000;

    private string   $runId;
    private Customer $patient1;
    private Customer $patient2;
    private Customer $consultant1;
    private Customer $consultant2;
    private Admin    $admin;
    private Wallet   $platformWallet;
    private array    $results = [];

    // =========================================================================
    // Entry point
    // =========================================================================

    public function run(): void
    {
        $this->runId = now()->format('ymdHis'); // 12 chars — unique per run

        if (app()->environment('production')) {
            $this->command->error('FinancialE2ETestSeeder is forbidden in production. Aborting.');
            return;
        }

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════╗');
        $this->command->info('║   Financial E2E Test Seeder  (run=' . $this->runId . ') ║');
        $this->command->info('╚════════════════════════════════════════════╝');
        $this->command->info('');

        $this->setup();

        $this->runScenario('Scenario 1 — Chat Settlement',          fn () => $this->scenario1());
        $this->runScenario('Scenario 2 — Refund (held)',            fn () => $this->scenario2());
        $this->runScenario('Scenario 3 — Dispute → Patient Wins',   fn () => $this->scenario3());
        $this->runScenario('Scenario 4 — Dispute → Consultant Wins',fn () => $this->scenario4());
        $this->runScenario('Scenario 5 — Withdrawal Approved',      fn () => $this->scenario5());
        $this->runScenario('Scenario 6 — Withdrawal Rejected',       fn () => $this->scenario6());
        $this->runScenario('Scenario 7 — Patient Withdrawal',         fn () => $this->scenario7());
        $this->runScenario('Scenario 8 — Idempotency Guard',          fn () => $this->scenario8());
        $this->runScenario('Scenario 9 — Expired Dispute Block',      fn () => $this->scenario9());
        $this->runScenario('Scenario 10 — No Bank Account Block',     fn () => $this->scenario10());
        $this->runScenario('Scenario 11 — Double Withdrawal Block',   fn () => $this->scenario11());
        $this->runScenario('Scenario 12 — Accounting Mismatch',       fn () => $this->scenario12());

        $this->printSummary();
    }

    // =========================================================================
    // Setup
    // =========================================================================

    private function setup(): void
    {
        $this->command->line("[Setup] run={$this->runId}  Creating users + platform wallet...");

        // ── Patients — boot() auto-sets approval_status=approved, account_status=active
        $this->patient1 = Customer::create([
            'full_name'    => 'E2E_TEST_Patient1',
            'email'        => "e2e_p1_{$this->runId}@test.local",
            'password'     => Hash::make('password'),
            'type_account' => 'patient',
            'phone'        => '0123456789',
        ]);

        $this->patient2 = Customer::create([
            'full_name'    => 'E2E_TEST_Patient2',
            'email'        => "e2e_p2_{$this->runId}@test.local",
            'password'     => Hash::make('password'),
            'type_account' => 'patient',
            'phone'        => '0123456789',

        ]);

        // ── Consultants — boot() auto-sets approval_status=pending, account_status=inactive
        //    Override after create so services pass status guards.
        $this->consultant1 = Customer::create([
            'full_name'    => 'E2E_TEST_Consultant1',
            'email'        => "e2e_c1_{$this->runId}@test.local",
            'password'     => Hash::make('password'),
            'type_account' => 'therapist',
            'phone'        => '0123456789',

        ]);
        Customer::where('id', $this->consultant1->id)->update([
            'approval_status' => 'approved',
            'account_status'  => 'active',
        ]);
        $this->consultant1->refresh();

        $this->consultant2 = Customer::create([
            'full_name'    => 'E2E_TEST_Consultant2',
            'email'        => "e2e_c2_{$this->runId}@test.local",
            'password'     => Hash::make('password'),
            'type_account' => 'therapist',
            'phone'        => '0123456789',



        ]);
        Customer::where('id', $this->consultant2->id)->update([
            'approval_status' => 'approved',
            'account_status'  => 'active',
        ]);
        $this->consultant2->refresh();

        // ── Admin (shared across runs — idempotent)
        $this->admin = Admin::firstOrCreate(
            ['email' => 'e2e_admin@test.local'],
            ['full_name' => 'E2E_TEST_Admin', 'password' => Hash::make('password') , 'phone' => '0123456789']
        );

        // ── Platform wallet (shared — idempotent)
        $this->platformWallet = Wallet::firstOrCreate(
            ['owner_type' => 'platform', 'owner_id' => 1],
            ['currency' => 'OMR', 'available_balance' => 0, 'pending_balance' => 0, 'frozen_balance' => 0]
        );
        $this->platformWallet->refresh();

        $this->command->line(sprintf(
            '[Setup] ✓  patients=[#%d,#%d]  consultants=[#%d,#%d]  platform_wallet=#%d  pending=%.3f',
            $this->patient1->id, $this->patient2->id,
            $this->consultant1->id, $this->consultant2->id,
            $this->platformWallet->id,
            $this->platformWallet->pending_balance,
        ));
    }

    // =========================================================================
    // Scenario runner — individual try/catch per scenario
    // =========================================================================

    private function runScenario(string $name, callable $fn): void
    {
        $this->command->line('');
        $this->command->line("┌─ {$name}");
        try {
            $fn();
            $this->results[] = ['name' => $name, 'pass' => true];
            $this->command->info('└─ PASS');
        } catch (\Throwable $e) {
            $this->results[] = ['name' => $name, 'pass' => false];
            $this->command->error('└─ FAIL: ' . $e->getMessage());
            $this->command->warn('   ' . class_basename($e) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a consultation row with all financial fields pre-calculated.
     * Does NOT touch wallets or transactions — call simulatePayment() next.
     */
    private function createTestConsultation(
        Customer $patient,
        Customer $consultant,
        string   $type,            // 'chat' | 'video'
        float    $price,
        string   $status,          // consultation status (e.g. 'completed')
        string   $financialStatus  // initial financial_status (typically 'unpaid')
    ): ConsultationChatRequest|ConsultationVideoRequest {
        $gwAmt   = round($price * self::GW_RATE,   3);
        $gross   = round($price + $gwAmt,           3);
        $platAmt = round($price * self::PLAT_RATE,  3);
        $earnAmt = round($price - $platAmt,         3);

        $data = [
            'patient_id'                 => $patient->id,
            'consultant_id'              => $consultant->id,
            'consultant_type'            => $consultant->type_account,
            'status'                     => $status,
            'financial_status'           => $financialStatus,
            'consultation_price'         => $price,
            'gateway_commission_rate'    => self::GW_RATE,
            'gateway_commission_amount'  => $gwAmt,
            'gross_amount'               => $gross,
            'platform_commission_rate'   => self::PLAT_RATE,
            'platform_commission_amount' => $platAmt,
            'consultant_earning_amount'  => $earnAmt,
        ];

        return $type === 'chat'
            ? ConsultationChatRequest::create($data)
            : ConsultationVideoRequest::create($data);
    }

    /**
     * Simulate a successful gateway payment:
     *   - Creates GatewayPayment (status=captured)
     *   - Ledger: consultation_hold credit on platform wallet
     *   - Increments platform.pending_balance by consultation_price
     *   - Sets consultation.financial_status = 'held'
     */
    private function simulatePayment(
        ConsultationChatRequest|ConsultationVideoRequest $consultation
    ): GatewayPayment {
        $price = (float) $consultation->consultation_price;
        $gross = (float) $consultation->gross_amount;

        $gatewayPayment = GatewayPayment::create([
            'reference_type'         => get_class($consultation),
            'reference_id'           => $consultation->id,
            'gateway'                => 'amwal',
            'gateway_transaction_id' => 'E2E_' . strtoupper(uniqid()),
            'payment_method'         => 'card',
            'amount'                 => $gross,
            'net_received_amount'    => $price,
            'currency'               => 'OMR',
            'country'                => 'OM',
            'status'                 => 'captured',
        ]);

        $this->platformWallet->refresh();

        app(FinancialTransactionService::class)->createWalletEntry(
            reference:          $consultation,
            gatewayPaymentId:   $gatewayPayment->id,
            transactionType:    TransactionType::CONSULTATION_HOLD->value,
            entryType:          EntryType::ENTRY_CREDIT->value,
            walletId:           $this->platformWallet->id,
            grossAmount:        $gross,
            netAmount:          $price,
            currency:           'OMR',
            status:             AmountStatus::STATUS_PENDING->value,
            meta:               ['operation' => 'payment_received', 'gateway' => 'amwal'],
            platformCommission: 0,
            vatAmount:          0,
        );

        $this->platformWallet->increment('pending_balance', $price);
        $this->platformWallet->refresh();

        $consultation->update(['financial_status' => 'held']);

        return $gatewayPayment;
    }

    /** Throw if condition is false. */
    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException("Assertion failed: {$message}");
        }
    }

    /** bccomp wrapper: returns true when a === b (scale 3). */
    private function eq(string $a, string $b): bool
    {
        return bccomp($a, $b, 3) === 0;
    }

    /** Return the customer wallet, creating it (balance=0) if not yet present. */
    private function walletOf(Customer $customer): Wallet
    {
        return Wallet::firstOrCreate(
            [
                'owner_type' => Customer::class,
                'owner_id'   => $customer->id,
            ],
            [
                'currency'          => 'OMR',
                'available_balance' => 0,
                'pending_balance'   => 0,
                'frozen_balance'    => 0,
            ]
        );
    }

    // =========================================================================
    // Scenario 1 — Chat Consultation Settlement
    // =========================================================================

    private function scenario1(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient1, $this->consultant1, 'chat', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);

        // Move to review_window with an expired deadline so settle() proceeds
        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->subDay(),
        ]);
        $consultation->refresh();

        $c1AvailableBefore  = (float) (Wallet::where('owner_type', Customer::class)->where('owner_id', $this->consultant1->id)->first()?->available_balance ?? 0);
        $this->platformWallet->refresh();
        $platformPendingBefore   = (float) $this->platformWallet->pending_balance;
        $platformAvailableBefore = (float) $this->platformWallet->available_balance;

        app(SettlementService::class)->settle($consultation);

        $consultation->refresh();
        $this->platformWallet->refresh();
        $c1Wallet = $this->walletOf($this->consultant1);

        $this->assert(
            $consultation->financial_status === 'withdrawable',
            "financial_status={$consultation->financial_status}, expected=withdrawable"
        );

        $this->assert(
            $this->eq((string) $c1Wallet->available_balance, number_format($c1AvailableBefore + self::EARN_AMT, 3, '.', '')),
            "consultant1 available: got {$c1Wallet->available_balance}, expected " . number_format($c1AvailableBefore + self::EARN_AMT, 3, '.', '')
        );

        $this->assert(
            $this->eq((string) $this->platformWallet->available_balance, number_format($platformAvailableBefore + self::PLAT_AMT, 3, '.', '')),
            "platform available: got {$this->platformWallet->available_balance}"
        );

        $this->assert(
            $this->eq((string) $this->platformWallet->pending_balance, number_format($platformPendingBefore - self::PRICE, 3, '.', '')),
            "platform pending: got {$this->platformWallet->pending_balance}"
        );

        $settlementTxCount = Transaction::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->whereIn('transaction_type', [
                TransactionType::CONSULTATION_RELEASE->value,
                TransactionType::CONSULTATION_CREDIT->value,
                TransactionType::PLATFORM_FEE->value,
            ])
            ->count();
        $this->assert($settlementTxCount === 3, "expected 3 settlement transactions, got {$settlementTxCount}");

        $this->command->line(sprintf(
            '│  consultant1.available=%.3f  platform.pending=%.3f  platform.available=%.3f',
            $c1Wallet->available_balance,
            $this->platformWallet->pending_balance,
            $this->platformWallet->available_balance,
        ));
    }

    // =========================================================================
    // Scenario 2 — Refund (held → refunded_internal)
    // =========================================================================

    private function scenario2(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient1, $this->consultant2, 'chat', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);
        // consultation is now financial_status='held'

        $p1WalletBefore = (float) $this->walletOf($this->patient1)->available_balance;

        // ConsultationRefundService MUST be called inside DB::transaction with lockForUpdate
        DB::transaction(function () use ($consultation) {
            $locked = $consultation->newQuery()
                ->whereKey($consultation->id)
                ->lockForUpdate()
                ->firstOrFail();

            app(ConsultationRefundService::class)->processInternalRefund($locked, 'e2e_test_refund');
        });

        $consultation->refresh();
        $p1Wallet = $this->walletOf($this->patient1);

        $this->assert(
            $consultation->financial_status === 'refunded_internal',
            "financial_status={$consultation->financial_status}, expected=refunded_internal"
        );

        $this->assert(
            $this->eq((string) $p1Wallet->available_balance, number_format($p1WalletBefore + self::PRICE, 3, '.', '')),
            "patient1 available: got {$p1Wallet->available_balance}"
        );

        $hasRefundTx = Transaction::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->where('transaction_type', TransactionType::REFUND->value)
            ->exists();
        $this->assert($hasRefundTx, 'refund transaction missing');

        $this->command->line(sprintf('│  patient1.available=%.3f', $p1Wallet->available_balance));
    }

    // =========================================================================
    // Scenario 3 — Dispute → Patient Wins
    // =========================================================================

    private function scenario3(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient2, $this->consultant1, 'video', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);

        // review_window with deadline in the FUTURE — patient can still open a dispute
        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->addHours(48),
        ]);
        $consultation->refresh();

        $p2WalletBefore = (float) $this->walletOf($this->patient2)->available_balance;

        // Open dispute
        app(DisputeService::class)->execute($consultation, $this->patient2, 'e2e_patient_dispute');

        $consultation->refresh();
        $dispute = Dispute::where('reference_type', ConsultationVideoRequest::class)
            ->where('reference_id', $consultation->id)
            ->firstOrFail();

        $this->assert($consultation->financial_status === 'frozen',  "expected frozen after dispute");
        $this->assert($dispute->status === 'opened', "dispute.status={$dispute->status}, expected=opened");

        // Resolve for patient
        app(DisputeResolutionService::class)->resolveForPatient($dispute, $this->admin, 'e2e_patient_wins');

        $consultation->refresh();
        $dispute->refresh();
        $p2Wallet = $this->walletOf($this->patient2);
        $this->platformWallet->refresh();

        $this->assert($consultation->financial_status === 'refunded_internal', "expected refunded_internal");
        $this->assert($dispute->status === 'resolved', "dispute.status={$dispute->status}, expected=resolved");
        $this->assert(
            $this->eq((string) $p2Wallet->available_balance, number_format($p2WalletBefore + self::PRICE, 3, '.', '')),
            "patient2 available: got {$p2Wallet->available_balance}"
        );

        $this->command->line(sprintf(
            '│  patient2.available=%.3f  platform.frozen=%.3f',
            $p2Wallet->available_balance,
            $this->platformWallet->frozen_balance,
        ));
    }

    // =========================================================================
    // Scenario 4 — Dispute → Consultant Wins
    // =========================================================================

    private function scenario4(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient2, $this->consultant2, 'video', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);

        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->addHours(48),
        ]);
        $consultation->refresh();

        $c2AvailableBefore = (float) (Wallet::where('owner_type', Customer::class)->where('owner_id', $this->consultant2->id)->first()?->available_balance ?? 0);

        // Open dispute
        app(DisputeService::class)->execute($consultation, $this->patient2, 'e2e_consultant_dispute');

        $consultation->refresh();
        $dispute = Dispute::where('reference_type', ConsultationVideoRequest::class)
            ->where('reference_id', $consultation->id)
            ->firstOrFail();

        $this->assert($consultation->financial_status === 'frozen', "expected frozen after dispute");

        // Resolve for consultant
        app(DisputeResolutionService::class)->resolveForConsultant($dispute, $this->admin, 'e2e_consultant_wins');

        $consultation->refresh();
        $dispute->refresh();
        $c2Wallet = $this->walletOf($this->consultant2);
        $this->platformWallet->refresh();

        $this->assert($consultation->financial_status === 'withdrawable', "expected withdrawable");
        $this->assert($dispute->status === 'resolved', "dispute.status={$dispute->status}, expected=resolved");
        $this->assert(
            $this->eq((string) $c2Wallet->available_balance, number_format($c2AvailableBefore + self::EARN_AMT, 3, '.', '')),
            "consultant2 available: got {$c2Wallet->available_balance}"
        );

        $hasPlatformFee = Transaction::where('reference_type', ConsultationVideoRequest::class)
            ->where('reference_id', $consultation->id)
            ->where('transaction_type', TransactionType::PLATFORM_FEE->value)
            ->exists();
        $this->assert($hasPlatformFee, 'platform_fee transaction missing');

        $this->command->line(sprintf(
            '│  consultant2.available=%.3f  platform.available=%.3f',
            $c2Wallet->available_balance,
            $this->platformWallet->available_balance,
        ));
    }

    // =========================================================================
    // Scenario 5 — Withdrawal Approved (consultant1, balance from Scenario 1)
    // =========================================================================

    private function scenario5(): void
    {
        $c1Wallet = $this->walletOf($this->consultant1);
        $available = (float) $c1Wallet->available_balance;

        $this->assert($available > 0, "consultant1 has no available balance — did Scenario 1 pass?");

        $withdrawAmount = min($available, self::EARN_AMT);

        // BankAccountService::store() sends an OTP email (queued, non-blocking).
        // We immediately force-verify the account so the withdrawal can proceed.
        $bankAccount = app(BankAccountService::class)->store($this->consultant1, [
            'bank_name'           => 'Bank Muscat',
            'account_holder_name' => 'E2E Consultant One',
            'account_number'      => "9910{$this->runId}01",
            'iban'                => "OM810010{$this->runId}01",
            'swift_code'          => 'BMUSOMRX',
            'bank_country'        => 'OM',
        ]);
        $bankAccount->update(['status' => 'verified', 'verified_at' => now()]);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($this->consultant1, $withdrawAmount);

        $c1Wallet->refresh();
        $this->assert(
            $this->eq((string) $c1Wallet->pending_balance, number_format($withdrawAmount, 3, '.', '')),
            "pending_balance wrong after request: got {$c1Wallet->pending_balance}"
        );

        $pendingBeforeApprove = (float) $c1Wallet->pending_balance;

        app(AdminWithdrawalService::class)->process($withdrawal, $this->admin, [
            'action'             => 'approve',
            'transfer_reference' => "E2E_TRANSFER_{$this->runId}_C1",
        ]);

        $withdrawal->refresh();
        $c1Wallet->refresh();

        $this->assert(
            $withdrawal->status->value === 'transferred',
            "withdrawal.status={$withdrawal->status->value}, expected=transferred"
        );

        $this->assert(
            $this->eq((string) $c1Wallet->pending_balance, number_format($pendingBeforeApprove - $withdrawAmount, 3, '.', '')),
            "consultant1 pending not decremented: got {$c1Wallet->pending_balance}"
        );

        $this->command->line(sprintf(
            '│  withdrawal.status=%s  consultant1.pending=%.3f',
            $withdrawal->status->value,
            $c1Wallet->pending_balance,
        ));
    }

    // =========================================================================
    // Scenario 6 — Withdrawal Rejected (consultant2, balance from Scenario 4)
    // =========================================================================

    private function scenario6(): void
    {
        $c2Wallet = $this->walletOf($this->consultant2);
        $available = (float) $c2Wallet->available_balance;

        $this->assert($available > 0, "consultant2 has no available balance — did Scenario 4 pass?");

        $withdrawAmount = min($available, self::EARN_AMT);

        $bankAccount = app(BankAccountService::class)->store($this->consultant2, [
            'bank_name'           => 'Bank Muscat',
            'account_holder_name' => 'E2E Consultant Two',
            'account_number'      => "9910{$this->runId}02",
            'iban'                => "OM810010{$this->runId}02",
            'swift_code'          => 'BMUSOMRX',
            'bank_country'        => 'OM',
        ]);
        $bankAccount->update(['status' => 'verified', 'verified_at' => now()]);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($this->consultant2, $withdrawAmount);

        // Capture available AFTER request (it has been decremented)
        $c2Wallet->refresh();
        $availableAfterRequest = (float) $c2Wallet->available_balance;

        app(AdminWithdrawalService::class)->process($withdrawal, $this->admin, [
            'action'     => 'reject',
            'admin_note' => 'e2e_test_rejection',
        ]);

        $withdrawal->refresh();
        $c2Wallet->refresh();

        $this->assert(
            $withdrawal->status->value === 'rejected',
            "withdrawal.status={$withdrawal->status->value}, expected=rejected"
        );

        $this->assert(
            $this->eq((string) $c2Wallet->available_balance, number_format($availableAfterRequest + $withdrawAmount, 3, '.', '')),
            "consultant2 available not restored: got {$c2Wallet->available_balance}"
        );

        $hasReversal = Transaction::where('reference_type', WithdrawalRequest::class)
            ->where('transaction_type', TransactionType::WITHDRAWAL_REVERSAL->value)
            ->where('wallet_id', $c2Wallet->id)
            ->exists();
        $this->assert($hasReversal, 'withdrawal_reversal transaction missing');

        $this->command->line(sprintf(
            '│  withdrawal.status=%s  consultant2.available=%.3f',
            $withdrawal->status->value,
            $c2Wallet->available_balance,
        ));
    }

    // =========================================================================
    // Scenario 7 — Patient Withdrawal (patient2 balance from Scenario 3)
    // =========================================================================

    private function scenario7(): void
    {
        $p2Wallet  = $this->walletOf($this->patient2);
        $available = (float) $p2Wallet->available_balance;

        $this->assert(
            $available > 0,
            "patient2 balance is 0.000 — Scenario 3 must pass first (dispute resolved for patient2)"
        );

        $withdrawAmount = min($available, self::PRICE);

        $bankAccount = app(BankAccountService::class)->store($this->patient2, [
            'bank_name'           => 'Bank Muscat',
            'account_holder_name' => 'E2E Patient Two',
            'account_number'      => "9910{$this->runId}03",
            'iban'                => "OM810010{$this->runId}03",
            'swift_code'          => 'BMUSOMRX',
            'bank_country'        => 'OM',
        ]);
        $bankAccount->update(['status' => 'verified', 'verified_at' => now()]);

        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($this->patient2, $withdrawAmount);

        $p2Wallet->refresh();
        $this->assert(
            $this->eq((string) $p2Wallet->pending_balance, number_format($withdrawAmount, 3, '.', '')),
            "pending_balance wrong after request: got {$p2Wallet->pending_balance}"
        );

        app(AdminWithdrawalService::class)->process($withdrawal, $this->admin, [
            'action'             => 'approve',
            'transfer_reference' => "E2E_TRANSFER_{$this->runId}_P2",
        ]);

        $withdrawal->refresh();
        $p2Wallet->refresh();

        $this->assert(
            $withdrawal->status->value === 'transferred',
            "withdrawal.status={$withdrawal->status->value}, expected=transferred"
        );

        $this->assert(
            $this->eq((string) $p2Wallet->pending_balance, '0.000'),
            "patient2 pending not zero after approval: got {$p2Wallet->pending_balance}"
        );

        $this->command->line(sprintf(
            '│  withdrawal.status=%s  patient2.pending=%.3f',
            $withdrawal->status->value,
            $p2Wallet->pending_balance,
        ));
    }

    // =========================================================================
    // Scenario 8 — Idempotency Guard (settle() twice must not double-credit)
    // =========================================================================

    private function scenario8(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient1, $this->consultant1, 'chat', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);

        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->subDay(),
        ]);
        $consultation->refresh();

        // First settle — must succeed
        app(SettlementService::class)->settle($consultation);
        $consultation->refresh();
        $this->assert($consultation->financial_status === 'withdrawable', 'first settle() failed — status not withdrawable');

        // Second settle — must throw (idempotency guard: status is no longer review_window)
        $threw = false;
        try {
            app(SettlementService::class)->settle($consultation);
        } catch (\DomainException $e) {
            $threw = true;
        }
        $this->assert($threw, 'second settle() did not throw — idempotency guard missing');

        // Exactly 3 settlement transactions, never 6
        $settlementCount = Transaction::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->whereIn('transaction_type', [
                TransactionType::CONSULTATION_RELEASE->value,
                TransactionType::CONSULTATION_CREDIT->value,
                TransactionType::PLATFORM_FEE->value,
            ])
            ->count();
        $this->assert($settlementCount === 3, "expected 3 settlement transactions, got {$settlementCount}");

        $this->command->line("│  idempotency guard triggered correctly  settlement_tx={$settlementCount}");
    }

    // =========================================================================
    // Scenario 9 — Expired Dispute Block
    // =========================================================================

    private function scenario9(): void
    {
        $consultation = $this->createTestConsultation(
            $this->patient1, $this->consultant2, 'chat', self::PRICE, 'completed', 'unpaid'
        );

        $this->simulatePayment($consultation);

        // Expired deadline → patient can no longer open a dispute
        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->subDay(),
        ]);
        $consultation->refresh();

        $disputeCountBefore = Dispute::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->count();

        $threw = false;
        try {
            app(DisputeService::class)->execute($consultation, $this->patient1, 'e2e_expired_dispute');
        } catch (\DomainException $e) {
            $threw = true;
        }
        $this->assert($threw, 'DisputeService did not reject expired deadline');

        $consultation->refresh();
        $this->assert(
            $consultation->financial_status === 'review_window',
            "financial_status changed despite rejection: got {$consultation->financial_status}"
        );

        $disputeCountAfter = Dispute::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->count();
        $this->assert($disputeCountAfter === $disputeCountBefore, 'Dispute record was created despite expired deadline');

        $this->command->line('│  expired dispute correctly blocked  financial_status=review_window');
    }

    // =========================================================================
    // Scenario 10 — No Bank Account Block
    // =========================================================================

    private function scenario10(): void
    {
        // Fresh consultant with no bank account registered
        $consultant3 = Customer::create([
            'full_name'    => 'E2E_TEST_Consultant3',
            'email'        => "e2e_c3_{$this->runId}@test.local",
            'password'     => Hash::make('password'),
            'type_account' => 'therapist',
            'phone'        => '0123456789',


        ]);
        Customer::where('id', $consultant3->id)->update([
            'approval_status' => 'approved',
            'account_status'  => 'active',
        ]);
        $consultant3->refresh();

        // Give consultant3 a real balance via settlement
        $consultation = $this->createTestConsultation(
            $this->patient1, $consultant3, 'chat', self::PRICE, 'completed', 'unpaid'
        );
        $this->simulatePayment($consultation);
        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->subDay(),
        ]);
        $consultation->refresh();
        app(SettlementService::class)->settle($consultation);

        $c3Wallet      = $this->walletOf($consultant3);
        $availableBefore = (float) $c3Wallet->available_balance;
        $this->assert($availableBefore > 0, 'consultant3 has no balance after settlement');

        // Withdrawal attempt with no bank account must throw
        $threw = false;
        try {
            app(WithdrawalService::class)->requestWithdrawal($consultant3, $availableBefore);
        } catch (\DomainException $e) {
            $threw = true;
        }
        $this->assert($threw, 'requestWithdrawal did not reject missing bank account');

        $c3Wallet->refresh();
        $this->assert(
            $this->eq((string) $c3Wallet->available_balance, number_format($availableBefore, 3, '.', '')),
            "wallet balance changed despite rejection: got {$c3Wallet->available_balance}"
        );

        $this->command->line(sprintf(
            '│  no-bank-account block confirmed  consultant3.available=%.3f',
            $c3Wallet->available_balance,
        ));
    }

    // =========================================================================
    // Scenario 11 — Double Withdrawal Block
    // =========================================================================

    private function scenario11(): void
    {
        // consultant2 has available balance + verified bank account from Scenarios 4 and 6
        $c2Wallet  = $this->walletOf($this->consultant2);
        $available = (float) $c2Wallet->available_balance;

        $this->assert($available >= 90.0, "consultant2 insufficient balance for S11 — did Scenarios 4+6 pass? got {$available}");

        $halfAmount = 45.000;

        // First request — must succeed
        $first = app(WithdrawalService::class)->requestWithdrawal($this->consultant2, $halfAmount);
        $this->assert(
            $first->status->value === 'pending_review',
            "first withdrawal status wrong: got {$first->status->value}"
        );

        // Second request while first is still pending — must throw
        $threw = false;
        try {
            app(WithdrawalService::class)->requestWithdrawal($this->consultant2, $halfAmount);
        } catch (\DomainException $e) {
            $threw = true;
        }
        $this->assert($threw, 'second requestWithdrawal did not reject pending duplicate');

        $pendingCount = WithdrawalRequest::where('owner_type', Customer::class)
            ->where('owner_id', $this->consultant2->id)
            ->where('status', 'pending_review')
            ->count();
        $this->assert($pendingCount === 1, "expected exactly 1 pending_review withdrawal, got {$pendingCount}");

        $c2Wallet->refresh();
        $this->assert(
            $this->eq((string) $c2Wallet->pending_balance, number_format($halfAmount, 3, '.', '')),
            "pending_balance wrong: got {$c2Wallet->pending_balance}"
        );

        $this->command->line(sprintf(
            '│  double withdrawal blocked  pending_count=%d  consultant2.pending=%.3f',
            $pendingCount,
            $c2Wallet->pending_balance,
        ));
    }

    // =========================================================================
    // Scenario 12 — Accounting Mismatch (earn + platform ≠ price → settle aborts)
    // =========================================================================

    private function scenario12(): void
    {
        // Intentionally broken: 50 + 30 = 80 ≠ 100
        $consultation = ConsultationChatRequest::create([
            'patient_id'                 => $this->patient1->id,
            'consultant_id'              => $this->consultant1->id,
            'consultant_type'            => $this->consultant1->type_account,
            'status'                     => 'completed',
            'financial_status'           => 'unpaid',
            'consultation_price'         => 100.000,
            'gateway_commission_rate'    => 0.020,
            'gateway_commission_amount'  => 2.000,
            'gross_amount'               => 102.000,
            'platform_commission_rate'   => 0.100,
            'platform_commission_amount' => 30.000,
            'consultant_earning_amount'  => 50.000,
        ]);

        $this->simulatePayment($consultation);

        $consultation->update([
            'financial_status' => 'review_window',
            'review_deadline'  => now()->subDay(),
        ]);
        $consultation->refresh();

        $this->platformWallet->refresh();
        $platformAvailableBefore = (float) $this->platformWallet->available_balance;

        $threw = false;
        try {
            app(SettlementService::class)->settle($consultation);
        } catch (\DomainException $e) {
            $threw = true;
        }
        $this->assert($threw, 'SettlementService did not throw on accounting mismatch');

        $consultation->refresh();
        $this->assert(
            $consultation->financial_status === 'review_window',
            "financial_status changed despite mismatch: got {$consultation->financial_status}"
        );

        $settlementCount = Transaction::where('reference_type', ConsultationChatRequest::class)
            ->where('reference_id', $consultation->id)
            ->whereIn('transaction_type', [
                TransactionType::CONSULTATION_RELEASE->value,
                TransactionType::CONSULTATION_CREDIT->value,
                TransactionType::PLATFORM_FEE->value,
            ])
            ->count();
        $this->assert($settlementCount === 0, "settlement transactions created despite mismatch: got {$settlementCount}");

        $this->platformWallet->refresh();
        $this->assert(
            $this->eq((string) $this->platformWallet->available_balance, number_format($platformAvailableBefore, 3, '.', '')),
            "platform.available changed despite mismatch: got {$this->platformWallet->available_balance}"
        );

        $this->command->line("│  accounting mismatch correctly blocked  settlement_tx={$settlementCount}");
    }

    // =========================================================================
    // Summary table
    // =========================================================================

    private function printSummary(): void
    {
        $total  = count($this->results);
        $passed = collect($this->results)->where('pass', true)->count();
        $failed = $total - $passed;

        $this->command->line('');
        $this->command->line('┌──────────────────────────────────────────────────────┐');
        $this->command->line('│                      SUMMARY                         │');
        $this->command->line('├──────────────────────────────────────────────────────┤');

        foreach ($this->results as $i => $result) {
            $num    = str_pad((string) ($i + 1), 2, ' ', STR_PAD_LEFT);
            $status = $result['pass'] ? '[PASS]' : '[FAIL]';
            $this->command->line("│  {$num}. {$result['name']}  …  {$status}");
        }

        $this->command->line('├──────────────────────────────────────────────────────┤');

        $footerText = $failed === 0
            ? "  {$passed}/{$total} passed — all green"
            : "  {$passed}/{$total} passed — {$failed} FAILED";

        $this->command->line("│{$footerText}");
        $this->command->line('└──────────────────────────────────────────────────────┘');
        $this->command->line('');
        $this->command->info('Done. Run: php artisan financial:audit');
    }
}

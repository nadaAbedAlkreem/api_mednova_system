<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Services\Api\Financial\Settlement\SettlementService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessReviewWindowExpiry extends Command
{
    protected $signature = 'consultations:process-review-expiry';
    protected $description = 'Send 47-hour dispute reminders and settle expired review windows.';

    public function __construct(protected SettlementService $settlementService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->sendExpiryReminders();
        $this->processSettlements();

        return self::SUCCESS;
    }

    // ── Part A: 47-hour reminder (1 hour before expiry) ──────────────────

    private function sendExpiryReminders(): void
    {
        $windowStart = now()->addHour();
        $windowEnd   = now()->addHours(2);

        $this->remindModel(ConsultationChatRequest::class, $windowStart, $windowEnd);
        Log::channel('financial')->error('settlement.reminder', []);
        $this->remindModel(ConsultationVideoRequest::class, $windowStart, $windowEnd);
    }

    private function remindModel(string $model, $windowStart, $windowEnd): void
    {
        $model::where('financial_status', 'review_window')
            ->whereBetween('review_deadline', [$windowStart, $windowEnd])
            ->where('review_window_reminder_sent', false)
            ->whereNull('deleted_at')
            ->chunkById(50, function ($consultations) {
                foreach ($consultations as $consultation) {
                    try {
                        event(new \App\Events\ConsultationRequested(
                            $consultation,
                            __('messages.review_window_expiring_patient', [
                                'consultation_id' => $consultation->id,
                            ]),
                            'review_window_expiring_patient'
                        ));

                        $consultation->update(['review_window_reminder_sent' => true]);

                        $this->info("Reminder sent: #{$consultation->id}");
                    } catch (\Throwable $e) {
                        Log::channel('financial')->error('settlement.reminder_failed', [
                            'consultation_id' => $consultation->id,
                            'error'           => $e->getMessage(),
                        ]);
                        $this->warn("Reminder failed #{$consultation->id}: {$e->getMessage()}");
                    }
                }
            });
    }

    // ── Part B: Settle expired review windows ────────────────────────────

    private function processSettlements(): void
    {
        $settled = 0;
        $skipped = 0;
        $failed  = 0;

        $settle = function ($consultations) use (&$settled, &$skipped, &$failed) {
            foreach ($consultations as $consultation) {
                try {
                    $this->settlementService->settle($consultation);
                    $settled++;
                    $this->info("Settled: #{$consultation->id}");
                } catch (DomainException $e) {
                    $skipped++;
                    Log::channel('financial')->error('settlement.skipped', [
                        'consultation_id' => $consultation->id,
                        'reason'          => $e->getMessage(),
                    ]);
                    $this->warn("Skipped #{$consultation->id}: {$e->getMessage()}");
                } catch (\Throwable $e) {
                    $failed++;
                    Log::channel('financial')->critical('settlement.failed', [
                        'consultation_id' => $consultation->id,
                        'error'           => $e->getMessage(),
                        'trace'           => $e->getTraceAsString(),
                    ]);
                    $this->error("Failed #{$consultation->id}: {$e->getMessage()}");
                }
            }
        };

        ConsultationChatRequest::where('financial_status', 'review_window')
            ->where('review_deadline', '<=', now())
            ->whereNull('deleted_at')
            ->chunkById(50, $settle);

        ConsultationVideoRequest::where('financial_status', 'review_window')
            ->where('review_deadline', '<=', now())
            ->whereNull('deleted_at')
            ->chunkById(50, $settle);

        $this->info("Done. Settled: {$settled}, Skipped: {$skipped}, Failed: {$failed}");

        Log::channel('financial')->info('settlement.batch_complete', [
            'settled' => $settled,
            'skipped' => $skipped,
            'failed'  => $failed,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorFailedJobsCommand extends Command
{
    protected $signature = 'monitor:failed-jobs';
    protected $description = 'Check for failed queue jobs and alert admin if any are found';

    public function handle(): int
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            $this->info('No failed jobs found.');
            return self::SUCCESS;
        }

        // Sample the most recent failures for context
        $recent = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get(['uuid', 'queue', 'failed_at', 'exception'])
            ->map(fn ($job) => [
                'uuid'      => $job->uuid,
                'queue'     => $job->queue,
                'failed_at' => $job->failed_at,
                'exception' => mb_substr($job->exception, 0, 300),
            ])
            ->toArray();

        Log::channel('financial')->critical('monitor.failed_jobs_detected', [
            'total_count' => $count,
            'recent'      => $recent,
            'checked_at'  => now()->toIso8601String(),
        ]);

        $this->error("CRITICAL: {$count} failed job(s) detected in the queue.");

        // Persist a notification for admin so it appears in the control panel
        try {
            Notification::create([
                'type'            => 'system_failed_jobs',
                'notifiable_id'   => 1,
                'notifiable_type' => Admin::class,
                'data'            => json_encode([
                    'failed_jobs_count' => $count,
                    'message'           => "{$count} failed job(s) found in the queue. Immediate review required.",
                    'recent_failures'   => $recent,
                    'checked_at'        => now()->toIso8601String(),
                ]),
                'read_at' => null,
                'status'  => 'pending',
            ]);
        } catch (\Throwable $e) {
            // Notification creation must never mask the critical log
            Log::channel('financial')->error('monitor.failed_jobs_notification_failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return self::FAILURE;
    }
}

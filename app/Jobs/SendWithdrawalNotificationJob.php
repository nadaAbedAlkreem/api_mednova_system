<?php

namespace App\Jobs;
use App\Events\WithdrawalStatusChangedBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
class SendWithdrawalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public $withdrawal,
        public $message,
        public $eventType,
        public $notification
    )
    {
//        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('الـ in job  استقبل الحدث بنجاح!');
            // استدعاء البث
            broadcast(new WithdrawalStatusChangedBroadcast(
                $this->withdrawal,
                $this->message,
                $this->eventType,
                $this->notification
            ));

            $this->notification->update(['status' => 'sent']);

        } catch (Throwable $e) {
            $this->notification->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('SendWithdrawalNotificationJob permanently failed', [
            'withdrawal_id' => $this->withdrawal->id,
            'error'         => $exception->getMessage(),
        ]);
        $this->notification->update(['status' => 'failed']);
    }
}

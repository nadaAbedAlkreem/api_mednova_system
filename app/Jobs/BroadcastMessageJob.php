<?php

namespace App\Jobs;


use App\Events\MessageSent;
use App\Models\Message;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class BroadcastMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            broadcast(new MessageSent($this->message))->toOthers();
            $this->message->update(['status' => 'sent']);

            Log::info("✅ Message broadcast successfully", [
                 'receiver_id' => $this->message
            ]);
        } catch (Exception $e) {
            $this->message->update(['status' => 'failed']);

            Log::error("❌ Failed to broadcast message", [
                'message_id' => $this->message,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * عدد مرات إعادة المحاولة قبل الفشل النهائي.
     */
    public $tries = 3;

    /**
     * الفاصل الزمني بين كل محاولة.
     */
    public $backoff = 10; // بالثواني
}

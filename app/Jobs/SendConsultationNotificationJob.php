<?php

namespace App\Jobs;

use App\Events\ConsultationRequested;
use App\Events\ConsultationRequestedBroadcast;
use App\Models\ConsultationChatRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendConsultationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public  $consultation , $message , $eventType ,$notification;

    public $tries = 5;


    public $backoff = [10, 30, 60, 120];
    public function __construct(ConsultationChatRequest $consultation , $message  , $eventType ,$notification)
    {
        $this->consultation = $consultation;
        $this->message = $message;
        $this->eventType = $eventType;
        $this->notification = $notification;
    }

    public function handle()
    {
        try {

            broadcast(new ConsultationRequestedBroadcast($this->consultation , $this->message , $this->eventType , $this->notification))->toOthers();
            $this->notification->update(['status' => 'sent']);
            $this->notification->save();
        } catch (\Throwable $e) {
            $this->notification->update(['status' => 'failed']);
            $this->notification->save();

            throw $e;
        }
    }
    public function failed(\Throwable $exception)
    {
        Log::critical('SendConsultationNotificationJob permanently failed', [
            'consultation_id' => $this->consultation->id,
            'error_message' => $exception->getMessage(),
        ]);
        $this->notification->update(['status' => 'failed']);
        $this->notification->save();


    }
}

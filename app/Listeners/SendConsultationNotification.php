<?php

namespace App\Listeners;

use App\Jobs\SendConsultationNotificationJob;
use App\Models\Notification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendConsultationNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        Log::info('Listener executed for ConsultationRequested', [
            'consultation_id' => $event->consultation->id,
            'patient_name' => $event->consultation->patient->full_name,
            'consultant_id' => $event->consultation->consultant_id,
        ]);

        try {
            $notifiable_id = $event->eventType === 'accepted' ? $event->consultation->patient_id : ($event->eventType === 'cancelled' ? ($event->consultation->action_by === 'consultable' ? $event->consultation->patient_id : $event->consultation->consultant_id) : $event->consultation->consultant_id);
            $notification = Notification::create([
                'type' => 'consultation_requested',
                'notifiable_id' => $notifiable_id,
                'notifiable_type' => get_class($event->consultation->consultant),
                'data' => json_encode([
                    'consultation_id' => $event->consultation->id,
                    'patient_name' => $event->consultation->patient->full_name,
                    'patient_id' => $event->consultation->patient_id,
                    'consultant_name' => $event->consultation->consultant->full_name,
                    'consultant_id' => $event->consultation->consultant_id,
                    'consultant_type' => $event->consultation->consultant_type,
                    'message' => $event->message,
                ]),
                'read_at' => null,
                'status' => 'pending',
            ]);

            Log::info('Notification created successfully', [
                'notification_id' => $notification->id,
                'consultation_id' => $event->consultation->id,
            ]);

            SendConsultationNotificationJob::dispatch($event->consultation,$event->message, $event->eventType , $notification);
        } catch (Throwable $e) {
            Log::error('Failed to create consultation notification', [
                'consultation_id' => $event->consultation->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

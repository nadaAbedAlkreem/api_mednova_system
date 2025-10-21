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
        $consultation = $event->consultation;
        $eventType = $event->eventType;
        $message = $event->message;

        Log::info('Listener executed for Consultation Event', [
            'consultation_id' => $consultation->id,
            'event_type' => $eventType,
        ]);

        try {
            $targets = match ($eventType) {
                'requested', 'cancelled_by_patient' => [$consultation->consultant_id], // عند إرسال طلب استشارة → إشعار للمستشار
                'accepted', 'cancelled_by_consultant' => [$consultation->patient_id],     // عند قبول الطلب → إشعار للمريض
                'reminder_for_all' , 'cancelled_by_system', 'completed' => [$consultation->patient_id, $consultation->consultant_id], // النظام ألغى → إشعار للطرفين
                default => [],
            };

            if (empty($targets)) {
                Log::warning("No notification target found for event type: {$eventType}");
                return;
            }

            foreach ($targets as $targetId) {
                $notification = Notification::create([
                    'type' => 'consultation_' . $eventType,
                    'notifiable_id' => $targetId,
                    'notifiable_type' => get_class($consultation->consultant),
                    'data' => json_encode([
                        'consultation_id' => $consultation->id,
                        'patient_id' => $consultation->patient_id,
                        'patient_name' => $consultation->patient->full_name,
                        'consultant_id' => $consultation->consultant_id,
                        'consultant_name' => $consultation->consultant->full_name,
                        'consultant_type' => $consultation->consultant_type,
                        'message' => $message,
                    ]),
                    'read_at' => null,
                    'status' => 'pending',
                ]);

                SendConsultationNotificationJob::dispatch(
                    $consultation,
                    $message,
                    $eventType,
                    $notification
                );

                Log::info('Notification created successfully', [
                    'notification_id' => $notification->id,
                    'target_id' => $targetId,
                    'event_type' => $eventType,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to create consultation notification', [
                'consultation_id' => $consultation->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

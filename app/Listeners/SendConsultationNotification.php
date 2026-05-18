<?php

namespace App\Listeners;

use App\Jobs\SendConsultationNotificationJob;
use App\Models\Admin;
use App\Models\Notification;
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
        $adminId = 1;

        Log::info('Listener executed for Consultation Event -----------', [
            'consultation_id' => $consultation->id,
            'event_type' => $eventType,
        ]);

        try {
            $targets = match ($eventType) {
                'dispute_opened_consultant',
                'dispute_resolved_consultant',
                'requested',
                'settlement_completed_consultant',
                'cancelled_by_patient',
                'active_by_patient' => [[
                        'id' => $consultation->consultant_id,
                        'type' => get_class($consultation->consultant),
                    ]],
                'dispute_opened_patient',
                'dispute_resolved_patient',
                'review_window_opened',
                'accepted',
                'cancelled_by_consultant',
                'review_window_expiring_patient',
                'settlement_completed_patient',
                'refund_issued' ,
                'active_by_consultant' =>
                [['id' => $consultation->patient_id, 'type' => get_class($consultation->patient)]],
                'reminder_for_all',
                'cancelled_by_system',
                'completed' => [
                    [
                        'id' => $consultation->patient_id,
                        'type' => get_class($consultation->patient),
                    ],
                    [
                        'id' => $consultation->consultant_id,
                        'type' => get_class($consultation->consultant),
                    ],
                ],
                'dispute_opened_admin' => [
                    [
                        'id' => $adminId,
                        'type' => Admin::class,
                    ],
                ],
                default => [],
            };

            if (empty($targets)) {
                Log::warning("No notification target found for event type: {$eventType}");
                return;
            }
            $notification = new Notification();
            foreach ($targets as $target) {
                $notification = Notification::create([
                    'type' => 'consultation_' . $eventType,
                    'notifiable_id' => $target['id'],
                    'notifiable_type' => $target['type'],
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

                Log::info('Notification created successfully',
                    [
                    'notification_id' => $notification->id,
                    'target_id' => $target['id'],
                    'target_type' => $target['type'],
                    'event_type' => $eventType,
                   ]);
            }
            SendConsultationNotificationJob::dispatch(
                $consultation,
                $message,
                $eventType,
                $notification
            );
        } catch (Throwable $e) {
            Log::error('Failed to create consultation notification', [
                'consultation_id' => $consultation->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

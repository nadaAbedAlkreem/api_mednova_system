<?php

namespace App\Services\Api\Consultation;

use App\Enums\ConsultationStatus;
use App\Enums\FinancialStatus;
use App\Events\ConsultationRequested;
use App\Models\ConsultationVideoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoConsultationStatusService
{
    protected ZoomMeetingService $zoomMeetingService;
//    private const REMINDER_LEVELS = [200, 300, 700];
    private const REMINDER_LEVELS = [10, 30, 60]; // بالدقائق
    private const REMINDER_LEVELS_PENDING = [6 ,12,23];

    public function __construct(ZoomMeetingService $zoomMeetingService)
    {
        $this->zoomMeetingService = $zoomMeetingService;
    }

    public function processPending(Carbon $now): void
    {
        ConsultationVideoRequest::with(['patient', 'consultant'])
            ->where('status', ConsultationStatus::PENDING->value)
            ->chunkById(100, function ($consultations) use ($now) {
                foreach ($consultations as $consultation) {
                    $hours = Carbon::parse($consultation->created_at)->diffInHours($now);
                    $this->handlePendingReminders($consultation, $hours);
                    if ($hours >= 24) {
                        $this->cancel($consultation, 'لم يتم اعتماد الاستشارة خلال 24 ساعة');
                    }
                }
            });
    }

    private function handlePendingReminders($consultation, int $hours ): void
    {
        foreach (self::REMINDER_LEVELS_PENDING as $level) {
            if ($hours >= $level && $consultation->last_reminder_level < $level) {
                $this->sendReminder($consultation, "يوجد الاستشارة في حالة انتظار الموافقة منذ {$hours}  يجب عليك توجه اما قبول او رفضها ساعة" , 'requested');
                $consultation->update([
                    'last_reminder_level' => $level,
                    'last_reminder_sent_at' => now(),
                ]);
                break;
            }
        }
    }


    public function processAccepted(Carbon $now): void
    {
        ConsultationVideoRequest::with(['appointmentRequest', 'patient', 'consultant'])
            ->where('status', ConsultationStatus::ACCEPTED->value)
            ->chunkById(100, function ($consultations) use ($now) {
                foreach ($consultations as $consultation) {
                    $appointment = $consultation->appointmentRequest;

                    if (!$appointment) {
                        continue;
                    }

                    $startTime = Carbon::parse($appointment->requested_time);

                    if ($now->gte($startTime)) {
                        $consultation->update(['status' => ConsultationStatus::ACTIVE->value]);
                        $this->sendReminder($consultation, "جلسة الفيديو بدأت الآن");
                    }
                }
            });
    }


    public function processActive(Carbon $now): void
    {
        ConsultationVideoRequest::with(['appointmentRequest', 'patient', 'consultant', 'activities'])
            ->where('status', 'active')
            ->chunkById(100, function ($consultations) use ($now) {
                foreach ($consultations as $consultation) {
                    if (!$consultation->appointmentRequest) {
                        continue;
                    }
                    $endTime = Carbon::parse($consultation->appointmentRequest->confirmed_end_time);
                    if ($now->gte($endTime)) {
                        $this->endApiZoomPlatform($consultation);
                        $this->endMeeting($consultation);
                        continue;
                    }
                    $this->processActivityReminders($consultation, $now);
                }
            });
    }

    private function endApiZoomPlatform(ConsultationVideoRequest $consultation): void
    {
        try {
            $this->zoomMeetingService->endMeetingLinkZoom($consultation);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }


    private function processActivityReminders($consultation, Carbon $now): void
    {
        if ($consultation->activities == null || $consultation->activities->count() == 0) {
            $timeBecameActive = $consultation->updated_at; // أو created_at وقت تغيير الحالة لـ active
//            $minutes = Carbon::parse($timeBecameActive)->diffInSeconds($now);
            $minutes = Carbon::parse($timeBecameActive)->diffInMinutes($now);
            foreach (self::REMINDER_LEVELS as $level) {
                if ($minutes >= $level && $consultation->last_reminder_level < $level) {
                    $doctorName = $consultation->consultant->full_name ?? 'المستشار';
                    $patientName = $consultation->patient->full_name ?? 'المريض';
                    $message = "تنبيه بانضمام أطراف إلى الاستشارة. المستشار: {$doctorName}، المريض: {$patientName}.";
                    $this->sendReminder($consultation, $message);

                    $consultation->update([
                        'last_reminder_level' => $level,
                        'last_reminder_sent_at' => now(),
                    ]);

                    break;
                }
            }
        }
    }



    private function bothParticipantsInteracted($consultation): bool
    {
        $activities = $consultation->activities;
        if ($activities->count() < 2) return false;
        foreach ($activities as $activity) {
            if (!$activity->joined_at) return false;
             $left = $activity->left_at ?? now();
             if(Carbon::parse($activity->joined_at)->diffInMinutes($left) < 5) {
                return false;
            }
        }

        return true;
    }


    public function sendReminder($consultation, string $message , $eventType ='reminder_for_all' ): void
    {
        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تنبيه: {$message}",
            $eventType
        ));
    }


    public function endMeeting($consultation): void
    {
        if ($consultation->status === 'completed') {
            return;
        }

        $endedAt = now();

        DB::transaction(function () use ($consultation, $endedAt) {
            $consultation->appointmentRequest->update([
                'status' => 'completed',
                'is_finished' => true,
                'finished_at' => $endedAt,
            ]);

            $consultation->update([
                'status' => 'completed',
                'ended_at' => $endedAt,
                'financial_status' => FinancialStatus::REVIEW_WINDOW->value,
                'review_deadline' => $endedAt->copy()->addHours(48),
                'action_by' => 'system',
                'action_reason' => 'Video consultation completed and review window opened.',
            ]);
        });

        $consultation->refresh();

        event(new ConsultationRequested(
            $consultation,
            __('messages.SESSION_COMPLETED_BOTH', [
                'patient' => $consultation->patient->full_name,
                'consultant' => $consultation->consultant->full_name,
            ]),
            'completed'
        ));

        event(new ConsultationRequested(
            $consultation,
            __('messages.ending_consultation_session_dispute', [
                'patient' => $consultation->patient->full_name,
                'consultant' => $consultation->consultant->full_name,
            ]),
            'review_window_opened'
        ));

//        $consultation->update([
//            'status' =>'completed',
//        ]);
//
//        $patient  = $consultation->patient->full_name  ?? 'patient';
//        $consultant  =  $consultation->consultant->full_name ?? 'consultant'; ;
//        event(new \App\Events\ConsultationRequested(
//            $consultation,
//            "تم انهاء جلسة الفيديو بين: {$patient}  ,$consultant}",
//            'cancelled_by_system'
//        ));

//        optional($consultation->appointmentRequest)->delete();
//        $consultation->delete();
    }





    public function cancel($consultation, string $reason): void
    {
        $consultation->appointmentRequest->update([
            'status' => 'cancelled',
            'is_finished' =>true,
            'finished_at' => now(),
        ]);

        $consultation->update([
            'status' => 'cancelled',
            'ended_at' => now(),
            'action_by' => 'system',
            'action_reason' => $reason,
        ]);

        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تم إلغاء جلسة الفيديو بسبب: {$reason}",
            'cancelled_by_system'
        ));

    }
//
}

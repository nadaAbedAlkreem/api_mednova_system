<?php

namespace App\Services\api;

use App\Models\ConsultationVideoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\api\ZoomMeetingService;

class VideoConsultationStatusService
{
    protected ZoomMeetingService $zoomMeetingService;
    private const REMINDER_LEVELS = [60, 120, 260];

    public function __construct(ZoomMeetingService $zoomMeetingService)
    {
        $this->zoomMeetingService = $zoomMeetingService;
    }

    public function processPending(Carbon $now): void
    {
        $consultations = ConsultationVideoRequest::with(['patient', 'consultant'])
            ->where('status', 'pending')
            ->get();
        Log::info("consultations-pending: ." .$consultations);

        if ($consultations->isEmpty()) {
            Log::info("No pending consultations to process.");
            return;
        }

        foreach ($consultations as $consultation) {
            $seconds = Carbon::parse($consultation->created_at)->diffInSeconds($now);
            Log::info('pending-check', [
                'consultation_id' => $consultation->id,
                'status' => $consultation->status,
                'created_at' => (string)$consultation->created_at,
                'now' => (string)$now,
                'seconds' => $seconds,
                'last_reminder_level' => $consultation->last_reminder_level,
            ]);

            $this->handlePendingReminders($consultation, $seconds );

//            if ($seconds >= 24 * 3600) {
//                $this->cancel($consultation, 'لم يتم اعتماد الاستشارة خلال 24 ساعة');
//            }
            if ($seconds >= 300) {
                $this->cancel($consultation, 'لم يتم اعتماد الاستشارة خلال 24 ساعة');
            }
        }
    }

    private function handlePendingReminders($consultation, int $seconds ): void
    {
        foreach (self::REMINDER_LEVELS as $level) {
            Log::info('pending-check', [
                'consultation_id' => $consultation->id,
                'status' => $consultation->status,
                'created_at' => (string)$consultation->created_at,
                'seconds' => $seconds,
                'last_reminder_level' => $consultation->last_reminder_level,
            ]);

            if ($seconds >= $level && $consultation->last_reminder_level < $level) {
                $this->sendReminder($consultation, "يوجد الاستشارة في حالة انتظار الموافقة منذ {$seconds}  يجب عليك توجه اما قبول او رفضها ثانية" , 'requested');

                $consultation->update([
                    'last_reminder_level' => $level,
                    'last_reminder_sent_at' => now(),
                ]);
                Log::info('pending-check', [
                    'consultation_id' => $consultation->id,
                    'status' => $consultation->status,
                    'created_at' => (string)$consultation->created_at,
                    'seconds' => $seconds,
                    'last_reminder_level' => $consultation->last_reminder_level,
                ]);
                break;
            }
            Log::info('pending-check', [
                'consultation_id' => $consultation->id,
                'status' => $consultation->status,
                'created_at' => (string)$consultation->created_at,
                'seconds' => $seconds,
                'last_reminder_level' => $consultation->last_reminder_level,
            ]);
        }
    }


    public function processAccepted(Carbon $now): void
    {
        $consultations = ConsultationVideoRequest::with(['appointmentRequest', 'patient', 'consultant'])
            ->where('status', 'accepted')
            ->get();

        foreach ($consultations as $consultation) {
            $appointment = $consultation->appointmentRequest;

            if (!$appointment) {
                continue;
            }

            $startTime = Carbon::parse($appointment->requested_time);

            if ($now->gte($startTime)) {
                $consultation->update(['status' => 'active']);
                $this->sendReminder($consultation, "جلسة الفيديو بدأت الآن");
            }
        }
    }


    public function processActive(Carbon $now): void
    {
        $consultations = ConsultationVideoRequest::with(['appointmentRequest', 'patient', 'consultant', 'activities'])
            ->where('status', 'active')
            ->get();
        Log::info('processActive  : nada99 ' . json_encode($consultations));

        foreach ($consultations as $consultation) {
            Log::info('processActive  : nada99 ' . json_encode($consultation));

            if (!$consultation->appointmentRequest)
            {Log::info('appointmentRequest  : nada99 ' . json_encode($consultation));
            continue;}

            $endTime = Carbon::parse($consultation->appointmentRequest->confirmed_end_time);

            if ($now->gte($endTime)) {
                Log::info('end-api-zoom-platform go to ' . json_encode($consultation));
                $this->endApiZoomPlatform($consultation);
                $this->endMeeting($consultation);
                continue;
            }

            $this->processActivityReminders($consultation, $now);
        }
    }
    private function endApiZoomPlatform(ConsultationVideoRequest $consultation): void
    {
        try {
            Log::info('end-api-zoom-platform' . json_encode($consultation));
            $this->zoomMeetingService->endMeetingLinkZoom($consultation);
        }catch (\Exception $exception){
            Log::error($exception->getMessage());
        }
    }


    private function processActivityReminders($consultation, Carbon $now): void
    {
        if ($consultation->activities == null || $consultation->activities->count() == 0) {
            $timeBecameActive = $consultation->updated_at; // أو created_at وقت تغيير الحالة لـ active
            $seconds = Carbon::parse($timeBecameActive)->diffInSeconds($now);
            Log::info('$seconds' .$seconds);

            Log::info('$timeBecameActive' . $timeBecameActive);
            foreach (self::REMINDER_LEVELS as $level) {
                if ($seconds >= $level && $consultation->last_reminder_level < $level) {
                    $doctorName = $consultation->consultant->full_name ?? 'المستشار';
                    $patientName = $consultation->patient->full_name ?? 'المريض';
                    $message = "تنبيه بانضمام أطراف إلى الاستشارة. المستشار: {$doctorName}، المريض: {$patientName}.";
                    Log::info('$timeBecameActive -- $message' . $message);

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

//        foreach ($consultation->activities as $activity)
//        {
//
//            if ($activity->status !== 'joined') {
//                continue;
//            }
//
//            $seconds = $activity->joined_at
//                ? Carbon::parse($activity->joined_at)->diffInSeconds($now)
//                : null;
//
//            if (!$seconds) continue;
//
//            foreach (self::REMINDER_LEVELS as $level) {
//                if ($seconds >= $level && $activity->last_reminder_level < $level) {
//
//                    $this->sendReminder(
//                        $consultation,
//                        "{$activity->role} لم يتفاعل خلال {$level} ثانية"
//                    );
//
//                    $consultation->update([
//                        'last_reminder_level' => $level,
//                        'last_reminder_sent_at' => now(),
//                    ]);
//
//                    break;
//                }
//            }
//
//            if ($seconds >= 3600) {
//                $this->cancel($consultation, "عدم تفاعل {$activity->role} خلال ساعة");
//            }
//        }



//    private function finalizeActiveSession($consultation): void
//    {
//        if ($this->bothParticipantsInteracted($consultation)) {
//            $this->complete($consultation);
//        } else {
//            $this->cancel($consultation, "لم يتفاعل الطرفان بشكل كافٍ قبل انتهاء الوقت");
//        }
//    }


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
        Log::info('video zoom reminder', ['consultation_id' => $consultation->id]);

        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تنبيه: {$message}",
            $eventType
        ));
    }


    public function endMeeting($consultation): void
    {
        $consultation->appointmentRequest->update([
            'is_finished' =>true,
            'finished_at' => now(),
        ]);

        $consultation->update([
            'status' =>'end',
        ]);

        $patient  = $consultation->patient->full_name  ?? 'patient';
        $consultant  =  $consultation->consultant->full_name ?? 'consultant'; ;
        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تم انهاء جلسة الفيديو بين: {$patient}  ,$consultant}",
            'cancelled_by_system'
        ));

//        optional($consultation->appointmentRequest)->delete();
//        $consultation->delete();
    }





    public function cancel($consultation, string $reason): void
    {
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

//        optional($consultation->appointmentRequest)->delete();
//        $consultation->delete();
    }
//
//
//    public function complete($consultation): void
//    {
//        $consultation->update([
//            'status' => 'completed',
//            'ended_at' => now(),
//        ]);
//
//        event(new \App\Events\ConsultationRequested(
//            $consultation,
//            "تم اكتمال جلسة الفيديو",
//            'completed'
//        ));
//
////        $consultation->delete();
//    }
}

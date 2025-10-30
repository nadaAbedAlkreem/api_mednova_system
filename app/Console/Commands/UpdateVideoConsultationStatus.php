<?php

namespace App\Console\Commands;

use App\Models\ConsultationVideoRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateVideoConsultationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-video-consultation-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        // معالجة الاستشارات pending
        $this->processPending($now);

        // معالجة الاستشارات accepted
        $this->processAccepted($now);

        // معالجة الاستشارات active
        $this->processActive($now);

        $this->info('Video consultation statuses processed successfully.');
    }

    private function processPending(Carbon $now)
    {
        $consultations = ConsultationVideoRequest::with('patient', 'consultant')
            ->where('status', 'pending')
            ->get();

        foreach ($consultations as $consultation) {
            $hoursSinceCreated = Carbon::parse($consultation->created_at)->diffInSeconds($now);  //diffInSeconds diffInHours
            $levels = [6, 12, 24];

            foreach ($levels as $level) {
                if ($hoursSinceCreated >= $level && $consultation->last_reminder_level < $level) {
                    $this->sendReminder($consultation, "الاستشارة في حالة انتظار منذ {$level} ساعة" );
                    $consultation->last_reminder_level = $level;
                    $consultation->last_reminder_sent_at = now();
                    $consultation->save();
                    break;
                }
            }

            if ($hoursSinceCreated >= 24) {
                $this->cancelConsultation($consultation, 'لم يتم اعتماد الاستشارة خلال 24 ساعة');
            }
        }
    }

    private function processAccepted(Carbon $now)
    {
        $consultations = ConsultationVideoRequest::with('appointmentRequest', 'patient', 'consultant')
            ->where('status', 'accepted')
            ->get();

        foreach ($consultations as $consultation) {
            if (!$consultation->appointmentRequest) continue;

            $startTime = Carbon::parse($consultation->appointmentRequest->requested_time);
            if ($now->gte($startTime)) {
                $consultation->update(['status' => 'active']);
                $this->sendReminder($consultation, "جلسة الفيديو بدأت الآن");
            }
        }
    }

    private function processActive(Carbon $now)
    {
        $consultations = ConsultationVideoRequest::with(['appointmentRequest', 'patient', 'consultant', 'activities'])
            ->where('status', 'active')
            ->get();

        foreach ($consultations as $consultation) {
            if (!$consultation->appointmentRequest) continue;

            $endTime = Carbon::parse($consultation->appointmentRequest->confirmed_end_time);
            if ($now->gte($endTime)) {
                $this->completeConsultation($consultation);
                continue;
            }

            // تذكير ومتابعة تفاعل كل طرف
            foreach ($consultation->activities as $activity) {
                $minutesSinceJoined = $activity->joined_at ? Carbon::parse($activity->joined_at)->diffInSeconds($now) : null;  //diffInMinutes //diffInSeconds
                if ($activity->status !== 'joined') continue;

                // التذكيرات: 15، 30، 60 دقيقة
                $reminderLevels = [15, 30, 60];
                foreach ($reminderLevels as $level) {
                    if ($minutesSinceJoined !== null && $minutesSinceJoined >= $level && $activity->last_reminder_level < $level) {
                        $this->sendReminder($consultation, "{$activity->role} لم يتفاعل خلال {$level} دقيقة");
                        $activity->last_reminder_level = $level;
                        $activity->last_reminder_sent_at = now();
                        $activity->save();
                        break;
                    }
                }

                // إلغاء إذا لم يتفاعل أحد بعد ساعة
                if ($minutesSinceJoined !== null && $minutesSinceJoined >= 60) {
                    $this->cancelConsultation($consultation, "عدم تفاعل {$activity->role} خلال ساعة");
                }
            }
        }
    }

    private function sendReminder($consultation, string $message)
    {
        $patientName = $consultation->patient->full_name ?? 'المريض';
        $consultantName = $consultation->consultant->full_name ?? 'المختص';

        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تنبيه: {$message} بين {$patientName} و {$consultantName}",
            'reminder_for_all'
        ));
    }

    private function cancelConsultation($consultation, string $reason)
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
        $consultation->delete();
        $consultation->appointmentRequest->delete();

    }

    private function completeConsultation($consultation)
    {
        $consultation->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        $patientName = $consultation->patient->full_name ?? 'المريض';
        $consultantName = $consultation->consultant->full_name ?? 'المختص';

        event(new \App\Events\ConsultationRequested(
            $consultation,
            "تم اكتمال جلسة الفيديو بين {$patientName} و {$consultantName}",
            'completed'
        ));


    }
}

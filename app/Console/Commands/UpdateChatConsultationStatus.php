<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateChatConsultationStatus extends Command
{
    protected $signature = 'consultations:update-status';
    protected $description = 'Mark consultations as completed after 24 hours of activation';

    public function handle()
    {
        $now = now();

//        // معالجة الجلسات المقبولة
        $this->processConsultations('pending', 'created_at', $now, function ($consultation, $hoursSince) {
            return $consultation->patient_message_count == 0 && $consultation->consultant_message_count == 0;
        });

        // معالجة الجلسات المقبولة
        $this->processConsultations('accepted', 'updated_at', $now, function ($consultation, $hoursSince) {
            return $consultation->patient_message_count == 0 && $consultation->consultant_message_count == 0;
        });

        // معالجة الجلسات النشطة
        $this->processConsultations('active', 'started_at', $now, function ($consultation, $hoursSince) {
            return ($consultation->patient_message_count + $consultation->consultant_message_count) <= 1;
        });



        $this->info('Consultation statuses and notifications processed successfully.');
    }

    /**
     * منطق معالجة نوع من الجلسات (مقبولة أو نشطة)
     */
    private function processConsultations(string $status, string $timeField, Carbon $now, callable $shouldRemind): void
    {

        ConsultationChatRequest::with(['patient:id,full_name', 'consultant:id,full_name'])
            ->where('status', $status)
            ->whereNotNull($timeField)
            ->whereNull('ended_at')
//            ->where($timeField, '<',  now()->subHours(6))
            ->chunkById(100, function ($consultations) use ($now, $shouldRemind, $status, $timeField) {
                foreach ($consultations as $consultation) {
                     $timeValue = $consultation->$timeField;
//                    if (!($timeValue instanceof Carbon)) {
//                        $timeValue = Carbon::parse($timeValue);
//                    }
                    $hoursSince = $timeValue->diffInSeconds($now);
                    if ($timeValue) {
//                        $secondsSince = $now->getTimestamp() - $timeValue->getTimestamp();
//                        $hoursSince = $timeValue->diffInHours($now);
                        $hoursSince = $now->getTimestamp() - $timeValue->getTimestamp();

                    } else {
                        continue;
                    }
                    $noMessages = $shouldRemind($consultation, $hoursSince);

                    if ($noMessages) {
                        $this->handleReminders($consultation, $hoursSince, $status);
                    } else {
                        // إذا كانت الجلسة نشطة يتم إنهاؤها بعد مدة معينة
                        if ($status === 'active' && $hoursSince >= 24) { // يتم انهائها بعد مرور 24 ساعة
                            $this->completeConsultation($consultation);
                        }
                    }
                    if ($consultation->isDirty()) {
                        $consultation->save();
                    }
                }
            });
    }

    /**
     * منطق إرسال التذكيرات أو الإلغاء
     */
    private function handleReminders($consultation, int $hoursSince, string $status)
    {
        $levels = [
            1 => 6, // بعد 6 ساعة
            2 => 12, // بعد 12 ساعة
            3 => 23, // بعد 23 ساعة
        ];

        foreach ($levels as $level => $limit) {
//            if ($secondsSince >= $limit && $consultation->last_reminder_level < $level) {
            if ($hoursSince >= $limit && $consultation->last_reminder_level === $level - 1){
                if ($level === 3) {
                    $this->cancelConsultation($consultation, 'No activity within 24 hours after acceptance');
                } else {
                    $typeEvent = ($status == 'pending') ? 'requested' : 'reminder_for_all';
                    $this->sendReminder($consultation, $limit , $typeEvent);
                }

                $consultation->last_reminder_level = $level;
                $consultation->last_reminder_sent_at = now();
                break;
            }
        }
    }

    private function cancelConsultation($consultation, $reason)
    {
        $consultation->update([
            'status' => 'cancelled',
            'ended_at' => now(),
            'action_by' => 'system',
            'action_reason' => $reason,
        ]);

        $consultantName = $consultation->consultant->full_name ?? 'المختص';
        $patientName = $consultation->patient->full_name ?? 'المريض';
        $message = "تم إلغاء جلسة الاستشارة بين {$consultantName} و {$patientName} لعدم وجود نشاط خلال 24 ساعة.";

        event(new \App\Events\ConsultationRequested(
            $consultation,
            $message,
            'cancelled_by_system'
        ));
//        $consultation->delete();
    }

    private function completeConsultation($consultation)
    {
        $consultation->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        $message = __('messages.SESSION_COMPLETED_BOTH', [
            'patient' => $consultation->patient->full_name,
            'consultant' => $consultation->consultant->full_name,
        ]);

        event(new \App\Events\ConsultationRequested(
            $consultation,
            $message,
            'completed'
        ));
//        $consultation->delete();

    }

    private function sendReminder($consultation, int $hours , $eventType = 'reminder_for_all' )
    {
        $patientName = $consultation->patient->full_name ?? 'المريض';
        $consultantName = $consultation->consultant->full_name ?? 'المختص';

        $message = "تنبيه بعد {$hours} ساعة: مرحبًا {$patientName} و {$consultantName}، يوجد جلسة معتمدة بينكما. يرجى التفاعل معها.";

        event(new \App\Events\ConsultationRequested(
            $consultation,
            $message,
            $eventType
        ));
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ConsultationChatRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateConsultationStatus extends Command
{
    protected $signature = 'consultations:update-status';
    protected $description = 'Mark consultations as completed after 24 hours of activation';

    public function handle()
    {
        $now = Carbon::now();

        $acceptedConsultations = ConsultationChatRequest::where('status', 'accepted')
            ->whereNotNull('updated_at')
            ->get();
        Log::info('reminder', [
             '$acceptedConsultations' => $acceptedConsultations,
        ]);

        foreach ($acceptedConsultations as $consultation) {
            $hoursSinceAccepted = $consultation->updated_at->diffInSeconds($now);
            $noMessages = $consultation->patient_message_count == 0 && $consultation->consultant_message_count == 0;
            Log::info('reminder', [
                '$acceptedConsultations' => 'for',
            ]);
            Log::info('reminder', [
                '$hoursSinceAccepted' => $hoursSinceAccepted,
            ]);

            if ($noMessages) {
                if ($hoursSinceAccepted >= 24 && $consultation->last_reminder_level < 3) {
                    $this->cancelConsultation($consultation, 'No activity within 24 hours after acceptance');
                    $consultation->last_reminder_level = 3;
                    $consultation->last_reminder_sent_at = now();
                    Log::info('reminder', [
                        '$acceptedConsultations' => 3,
                    ]);
                } elseif ($hoursSinceAccepted >= 12 && $consultation->last_reminder_level < 2) {
                    $this->sendReminder($consultation, $hoursSinceAccepted);
                    $consultation->last_reminder_level = 2;
                    $consultation->last_reminder_sent_at = now();
                    Log::info('reminder', [
                        '$acceptedConsultations' => 2,
                    ]);
                } elseif ($hoursSinceAccepted >= 6 && $consultation->last_reminder_level < 1) {
                    $this->sendReminder($consultation, $hoursSinceAccepted);
                    $consultation->last_reminder_level = 1;
                    $consultation->last_reminder_sent_at = now();
                    Log::info('reminder', [
                        '$acceptedConsultations' => 1,
                    ]);
                }
                Log::info('reminder', [
                    '$acceptedConsultations' => $acceptedConsultations,
                ]);
                $consultation->save();
            }
        }

        /**
         */
//        $activeConsultations = ConsultationChatRequest::where('status', 'active')
//            ->whereNotNull('started_at')
//            ->get();
//
//        foreach ($activeConsultations as $consultation) {
//            $hoursSinceStarted = $consultation->started_at->diffInHours($now);
//            $totalMessages = $consultation->patient_message_count + $consultation->consultant_message_count;
//
//            if ($consultation->patient_message_count == 0 || $consultation->consultant_message_count == 0) {
//                if ($hoursSinceAccepted >= 24 && $consultation->last_reminder_level < 3) {
//                    $this->cancelConsultation($consultation, 'No activity within 24 hours after acceptance');
//                    $consultation->last_reminder_level = 3;
//                    $consultation->last_reminder_sent_at = now();
//                } elseif ($hoursSinceAccepted >= 12 && $consultation->last_reminder_level < 2) {
//                    $this->sendReminder($consultation, $hoursSinceAccepted);
//                    $consultation->last_reminder_level = 2;
//                    $consultation->last_reminder_sent_at = now();
//                } elseif ($hoursSinceAccepted >= 6 && $consultation->last_reminder_level < 1) {
//                    $this->sendReminder($consultation, $hoursSinceAccepted);
//                    $consultation->last_reminder_level = 1;
//                    $consultation->last_reminder_sent_at = now();
//                }
//
//            } else {
//                if ($hoursSinceStarted >= 24) {
//                    if ($totalMessages == 0) {
//                        $this->cancelConsultation($consultation, 'Insufficient interaction');
//                    } else {
//                        $this->completeConsultation($consultation);
//                    }
//                }
//            }
//        }

        $this->info('Consultation statuses and notifications processed successfully.');
    }


    private function cancelConsultation($consultation, $reason)
    {
        $consultantName = $consultation->consultant->full_name ?? 'المختص';
        $patientName = $consultation->patient->full_name ?? 'المريض';

        // نص السبب
        $message = "تم إلغاء جلسة الاستشارة بين المختص {$consultantName} والمريض {$patientName} من قبل المنصة لعدم وجود نشاط بينهم خلال فترة 24 ساعة.";

        $consultation->status = 'cancelled';
        $consultation->ended_at = now();
        $consultation->action_by = 'system';
        $consultation->action_reason = $reason;
        $consultation->save();

        event(new \App\Events\ConsultationRequested(
            $consultation,
            $message,
            'cancelled_by_system'
        ));
    }


    private function completeConsultation($consultation)
    {
        $consultation->status = 'completed';
        $consultation->ended_at = now();
        $consultation->save();
        $message = __('messages.SESSION_COMPLETED_BOTH', ['patient' => $consultation->patient->full_name  , 'consultant' => $consultation->consultant->full_name  ]);
        event(new \App\Events\ConsultationRequested(
            $consultation, $message,'completed'
        ));
    }


    private function sendReminder($consultation, int $hoursSinceAccepted)
    {
        $patientName = $consultation->patient->full_name ?? 'المريض';
        $consultantName = $consultation->consultant->full_name ?? 'المختص';

        if ($hoursSinceAccepted >= 12 && $hoursSinceAccepted < 24) {
            $message = "تنبيه بعد 12 ساعة: مرحبًا $patientName و $consultantName يوجد جلسة معتمدة بينكما. يرجى التفاعل معها قبل مرور 24 ساعة. ";
        } elseif ($hoursSinceAccepted >= 6 && $hoursSinceAccepted < 12) {
            $message = "تنبيه بعد 6 ساعات: مرحبًا $patientName و $consultantName يوجد جلسة معتمدة بينكما. يرجى التفاعل معها قبل مرور 24 ساعة.";
        } else {
            $message = "مرحبًا $patientName و $consultantName يوجد جلسة معتمدة بينكما. يرجى التفاعل معها قبل مرور 24 ساعة. ";
        }
        event(new \App\Events\ConsultationRequested(
            $consultation,
            $message,
            'reminder_for_all'
        ));
    }

}

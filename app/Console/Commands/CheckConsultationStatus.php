<?php

namespace App\Console\Commands;

use App\Models\ConsultationVideoRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckConsultationStatus extends Command
{
    protected $signature = 'consultations:check-status';
    protected $description = 'Check and update consultations status after 72 hours.';

    public function handle()
    {
        ConsultationVideoRequest::where('status', 'end')
//            ->where('updated_at', '<', Carbon::now()->subHours(72)) in prod
            ->where('updated_at', '<', Carbon::now()->subSecond(5))
            ->chunkById(100, function ($consultations) {
                foreach ($consultations as $c) {
                    $consultant = $c->consultant_approved;
                    $patient = $c->patient_approved;
                    $duration = $c->actual_duration_minutes ?? 0;
                    [$status, $reason] = $this->determineStatus($consultant, $patient, $duration);

                    $c->update([
                        'status' => $status,
                        'action_by' => 'system',
                        'action_reason' => $reason,
                    ]);
                }
            });

        // الاستشارات التي انتهى وقتها ولم يتم حسمها بعد
//        $consultations = ConsultationVideoRequest::where('status', 'end')
//            ->where('updated_at', '<', Carbon::now()->subHours(72))
//            ->get();
//
//
//        foreach ($consultations as $c) {
//
//            $consultant = $c->consultant_approved;
//            $patient = $c->patient_approved;
//            $duration = $c->actual_duration_minutes ?? 0;
//            [$status, $reason] = $this->determineStatus($consultant, $patient, $duration);
//
//            $c->status = $status;
//            $c->action_by = 'system';
//            $c->action_reason = $reason;
//            $c->save();

//            // 1) الطرفان وافقا
//            if ($consultant && $patient) {
//                $c->status = 'completed';
//                $c->action_by = 'system';
//                $c->action_reason = 'Both parties approved.';
//            }
//
//            // 2) الطرفان رفضا
//            elseif (!$consultant && !$patient && ($consultant !== null && $patient !== null)) {
//                $c->status = 'cancelled';
//                $c->action_by = 'system';
//                $c->action_reason = 'Both parties rejected.';
//            }
//
//            // 3) اختلاف الآراء → نزاع
//            elseif (($consultant && !$patient) || (!$consultant && $patient)) {
//                $c->status = 'disputed';
//                $c->action_by = 'system';
//                $c->action_reason = 'Approval disagreement.';
//            }
//
//            // 4) لا يوجد رد من الطرفين – الجلسة تمت فعلياً
//            elseif ($consultant === false && $patient === false && $duration > 5) {
//                // ملاحظة: false الافتراضي عند عدم الرد
//                $c->status = 'completed';
//                $c->action_by = 'system';
//                $c->action_reason = 'No response from both parties, session completed.';
//            }
//
//            // 5) لا يوجد رد من الطرفين – الجلسة لم تتم
//            elseif ($duration <= 5) {
//                $c->status = 'cancelled';
//                $c->action_by = 'system';
//                $c->action_reason = 'Session did not happen (duration <= 5 minutes).';
//            }
//
//            // 6) أي حالة غير واضحة → نزاع
//            else {
//                $c->status = 'disputed';
//                $c->action_by = 'system';
//                $c->action_reason = 'Unclear condition, flagged for manual review.';
//            }
//
//            $c->save();
//        }
//
//        $this->info("Consultation status check completed.");

        return 0;
    }


    private function determineStatus($consultant, $patient, $duration)
    {
        // الطرفان وافقا
        Log::info('NADA HERE 777' , compact('consultant', 'patient'));
         if ($consultant == 1 && $patient == 1) {
            return ['completed', 'Both parties approved.'];
        }

        // الطرفان رفضا
        if ($consultant == 0 && $patient == 0) {
            // الجلسة تمت؟
//            if ($duration > 5) {
//                return ['completed', 'No response from both parties, session completed.'];
//            }
            return ['cancelled', 'Session did not happen (duration <= 5 minutes).'];
        }

        // اختلاف الآراء
        if (($consultant == 1 && $patient == 0) ||
            ($consultant == 0 && $patient == 1)) {
            return ['disputed', 'Approval disagreement.'];
        }

        // حالات غير واضحة
        return ['disputed', 'Unclear condition, flagged for manual review.'];
    }

}

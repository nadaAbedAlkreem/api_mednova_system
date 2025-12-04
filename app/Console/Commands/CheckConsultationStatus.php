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
            ->where('updated_at', '<', Carbon::now()->subSecond(20))
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

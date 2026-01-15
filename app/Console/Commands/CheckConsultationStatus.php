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
            ->where('updated_at', '<', Carbon::now()->subHours(72))
//            ->where('updated_at', '<', Carbon::now()->subSecond(20))
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
        if (is_null($consultant) || is_null($patient)) {
            return ['pending_review', 'No response from one or both parties.'];
        }

        if ($consultant === 1 && $patient === 1) {
            return ['completed', 'Both parties approved.'];
        }

        if ($consultant === 0 && $patient === 0) {
            return ['cancelled', 'Both parties declined the consultation.'];
        }

        if (
            ($consultant === 1 && $patient === 0) ||
            ($consultant === 0 && $patient === 1)
        ) {
            return ['disputed', 'Approval disagreement.'];
        }

        // أي حالة غريبة
        return ['pending_review', 'Unclear condition, flagged for manual review.'];
    }

}

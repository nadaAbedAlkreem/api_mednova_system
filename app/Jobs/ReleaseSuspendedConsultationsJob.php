<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReleaseSuspendedConsultationsJob implements ShouldQueue
{
    use Queueable;


    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $models = [
            ConsultationChatRequest::class,
            ConsultationVideoRequest::class,
        ];


        foreach ($models as $model) {
            $model::query()
                ->where('financial_status', FinancialStatus::PAYMENT_SUSPENDED->value)
                ->where('suspended_until', '<=', now())
                ->chunkById(100, function ($consultations) {
                    foreach ($consultations as $consultation) {
                        $consultation->update([
                            'financial_status' => FinancialStatus::UNPAID->value,
                            'suspended_until'  => null,
                        ]);

                        Log::channel('financial')->info('consultation.suspension_lifted', [
                            'consultation_id'  => $consultation->id,
                            'suspension_count' => $consultation->suspension_count,
                        ]);

                        // هنا ترسل إشعار للمريض إذا أردت
                        // $consultation->patient->notify(new PaymentResumedNotification($consultation));
                    }
                });
        }

    }
}

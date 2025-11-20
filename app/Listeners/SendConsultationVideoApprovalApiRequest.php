<?php

namespace App\Listeners;

use App\Jobs\SendConsultationApprovalApiJob;
use Illuminate\Support\Facades\Log;

class SendConsultationVideoApprovalApiRequest
{

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        SendConsultationApprovalApiJob::dispatch(
            $event->dateTime,                  // وقت بدء الجلسة
            $event->sessionDurationHours,      // مدة الجلسة بالساعات أو بالدقائق حسب ما تحدد في Job
            $event->consultation               // النموذج نفسه
        );

        Log::info('Listener dispatched SendConsultationApprovalApiJob', [
            'consultation_id' => $event->consultation->id,
            'start_time' => $event->dateTime,
            'duration' => $event->sessionDurationHours,
        ]);
    }
}

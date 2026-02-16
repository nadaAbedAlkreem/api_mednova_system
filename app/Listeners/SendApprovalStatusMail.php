<?php

namespace App\Listeners;

use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use function Laravel\Prompts\error;

class SendApprovalStatusMail
{
    /**
     * Create the event listener.
     */
    public function handle(CustomerApprovalStatusChanged $event)
    {
        $url = url("https://mednovacare.com/profile");
        Log::info('Approved 444  mail');

        if ($event->status === StatusType::APPROVED) {
            Mail::to($event->customer)
                ->send(new AccountApprovedMail($event->customer, $url));
            Log::info('Approved', [$event->customer]);

        }
        if ($event->status === StatusType::REJECTED) {
            Mail::to($event->customer)
                ->send(new AccountRejectedMail(
                    $event->customer,
                    $url,
                    $event->reason
                ));
        }
    }
}

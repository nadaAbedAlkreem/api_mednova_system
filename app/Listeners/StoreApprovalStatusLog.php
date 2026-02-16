<?php

namespace App\Listeners;

use App\Events\CustomerApprovalStatusChanged;
use App\Models\AccountReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StoreApprovalStatusLog
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerApprovalStatusChanged $event)
    {
        Log::info('Approved 444  accou');

        AccountReview::create([
            'customer_id' => $event->customer->id,
            'status'      => $event->status,
            'reason'      => $event->reason,
            'reviewed_by' => $event->adminId
        ]);
    }
}

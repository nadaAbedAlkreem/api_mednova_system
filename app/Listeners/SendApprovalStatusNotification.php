<?php

namespace App\Listeners;

use App\Events\AccountStatusNotificationEvent;
use App\Events\CustomerApprovalStatusChanged;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;


class SendApprovalStatusNotification
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
        Log::info('Approved 444  notification');

        Notification::create([
            'type' => 'customer_approval_status_changed',
            'notifiable_id' => $event->customer->id,
            'notifiable_type' =>Customer::class,
            'data' => json_encode([
                'customer' => $event->customer->id,
                'status' => $event->status,
                'reason' => $event->reason,
                'admin_id' => $event->adminId,
            ]),
            'read_at' => null,
            'status' => 'pending',
        ]);

        broadcast(new AccountStatusNotificationEvent(
            $event->customer->id,
            $event->status,
            $event->reason
        ));


    }
}

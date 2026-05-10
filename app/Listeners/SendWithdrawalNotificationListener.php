<?php

namespace App\Listeners;

use App\Events\WithdrawalStatusChanged;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWithdrawalNotificationListener
{
    public function handle(WithdrawalStatusChanged $event): void
    {
        try {
            Notification::create([
                'type'            => 'withdrawal_' . $event->eventType,
                'notifiable_id'   => $event->targetId,
                'notifiable_type' => $event->targetType,
                'data'            => json_encode([
                    'withdrawal_id' => $event->withdrawal->id,
                    'amount'        => $event->withdrawal->amount,
                    'currency'      => $event->withdrawal->currency,
                    'status'        => $event->withdrawal->status?->value,
                    'message'       => $event->message,
                ]),
                'read_at' => null,
                'status'  => 'sent',
            ]);
        } catch (Throwable $e) {
            Log::error('SendWithdrawalNotificationListener failed', [
                'withdrawal_id' => $event->withdrawal->id,
                'event_type'    => $event->eventType,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}

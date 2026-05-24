<?php

namespace App\Listeners;

use App\Events\WithdrawalStatusChanged;
use App\Jobs\SendWithdrawalNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWithdrawalNotificationListener
{
    public function handle(WithdrawalStatusChanged $event): void
    {
        try {
            $notification = Notification::create([
                'type'            => 'withdrawal_' . $event->eventType,
                'notifiable_id'   => $event->targetId,
                'notifiable_type' => $event->targetType,
                'data'            => json_encode([
                    'withdrawal_id' => $event->withdrawal->id,
                    'amount'        => number_format((float) $event->withdrawal->amount, 3, '.', ''),
                    'currency'      => $event->withdrawal->currency,
                    'status'        => $event->withdrawal->status?->value ?? $event->withdrawal->status,
                    'message'       => $event->message,
                ]),
                'read_at' => null,
                'status'  => 'pending',
            ]);
            SendWithdrawalNotificationJob::dispatch(
                $event->withdrawal,
                $event->message,
                $event->eventType,
                $notification
            );

        } catch (Throwable $e) {
            Log::error('Failed to create withdrawal notification', [
                'withdrawal_id' => $event->withdrawal->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}

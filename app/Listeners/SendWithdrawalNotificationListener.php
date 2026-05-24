<?php

namespace App\Listeners;

use App\Events\WithdrawalStatusChanged;
use App\Jobs\SendWithdrawalNotificationJob;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWithdrawalNotificationListener
{
    public function handle(WithdrawalStatusChanged $event): void
    {
        $withdrawal = $event->withdrawal;
        $actionType = $event->actionType;
        $formattedAmount = number_format((float) $withdrawal->amount, 3, '.', '');
        $currency = $withdrawal->currency;

        try {$targets = [];

            if ($actionType === 'withdrawal_requested') {
                $targets[] = [
                    'id' => $withdrawal->owner_id,
                    'type' => $withdrawal->owner_type,
                    'event_type' => 'requested_user',
                    'message' => __('messages.WITHDRAWAL_REQUESTED_USER', ['amount' => $formattedAmount, 'currency' => $currency]),
                ];
                $targets[] = [
                    'id' => 1,
                    'type' => Admin::class,
                    'event_type' => 'requested_admin',
                    'message' => __('messages.WITHDRAWAL_REQUESTED_ADMIN'),
                ];
            }

            elseif ($actionType === 'withdrawal_cancelled') {
                $targets[] = [
                    'id' => $withdrawal->owner_id,
                    'type' => $withdrawal->owner_type,
                    'event_type' => 'cancelled_user',
                    'message' => __('messages.WITHDRAWAL_CANCELLED_USER', ['amount' => $formattedAmount, 'currency' => $currency]),
                ];
            }

            foreach ($targets as $target) {

                Log::info('الـ Listener استقبل الحدث بنجاح ونفذ الحفظ لـ: ' . $target['type']);

                $notification = Notification::create([
                    'type'            => 'withdrawal_' . $target['event_type'],
                    'notifiable_id'   => $target['id'],
                    'notifiable_type' => $target['type'],
                    'data'            => json_encode([
                        'withdrawal_id' => $withdrawal->id,
                        'amount'        => $formattedAmount,
                        'currency'      => $currency,
                        'status'        => $withdrawal->status?->value ?? $withdrawal->status,
                        'message'       => $target['message'],
                    ]),
                    'read_at' => null,
                    'status'  => 'pending',
                ]);

                // إرسال جوب البث المنفصل لكل مستهدف في الخلفية
                SendWithdrawalNotificationJob::dispatch(
                    $withdrawal,
                    $target['message'],
                    $target['event_type'],
                    $notification
                );
            }

        } catch (Throwable $e) {
            Log::error('Failed inside SendWithdrawalNotificationListener', [
                'withdrawal_id' => $withdrawal->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }

}

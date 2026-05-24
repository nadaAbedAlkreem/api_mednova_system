<?php

namespace App\Events;

use App\Models\Admin;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class WithdrawalStatusChangedBroadcast implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public $withdrawal,
        public $message,
        public string $eventType,
        public $notification
    ) {}

    public function broadcastOn(): array
    {
        $targetType = $this->notification->notifiable_type;
        $targetId = $this->notification->notifiable_id;
        \Illuminate\Support\Facades\Log::info('الـ Listener استقبل الحدث بنجاح!'.$targetType);

        if ($targetType === Admin::class) {
            return [new PrivateChannel('admin.notifications')];
        }

        $channelPrefix = Str::lower(class_basename($targetType));
        \Illuminate\Support\Facades\Log::info('الـ Listener استقبل الحدث بنجاح!'.$channelPrefix. $targetId );

        return [new PrivateChannel($channelPrefix . '.' . $targetId)];
    }

    public function broadcastAs(): string
    {
        return 'withdrawal.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->withdrawal->id,
            'amount'        => number_format((float) $this->withdrawal->amount, 3, '.', ''),
            'currency'      => $this->withdrawal->currency,
            'status'        => $this->withdrawal->status?->value ?? $this->withdrawal->status,
            'event_type'    => $this->eventType,
            'message'       => $this->message,
            'created_at'    => $this->withdrawal->created_at,
        ];
    }
}

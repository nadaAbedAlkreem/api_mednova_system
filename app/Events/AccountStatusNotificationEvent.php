<?php

namespace App\Events;

use App\Enums\StatusType;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class AccountStatusNotificationEvent implements ShouldBroadcast
{
    public function __construct(
        public int $customerId,
        public StatusType $status,
        public ?string $reason
    ) {}

    public function broadcastOn()
    {
        return ['customer.' . $this->customerId];
    }

    public function broadcastAs()
    {
        return 'account.status.updated';
    }

    public function broadcastWith()
    {
        Log::info('Approved 444  bord');

        return [
            'status' => $this->status,
            'reason' => $this->reason,
            'message' => ($this->status == StatusType::APPROVED)? __('messages.welcome_notification'): __('messages.rejected_notification')
        ];
    }
}

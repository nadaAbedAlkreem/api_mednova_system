<?php

namespace App\Events;

use App\Models\ConsultationVideoRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsultationVideoApproval
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public ConsultationVideoRequest $consultation ;
    public $dateTime ;
    public $sessionDurationHours ;


    /**
     * Create a new event instance.
     */
    public function __construct($dateTime , $sessionDurationHours  ,$consultation , )
    {
        $this->consultation = $consultation->load(['patient', 'consultant', 'appointmentRequest']);
        $this->dateTime = $dateTime;
        $this->sessionDurationHours = $sessionDurationHours;
        Log::info('Event fired: ConsultationVideoApproval', [
            'consultation_id' => $consultation->id,
        ]);
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

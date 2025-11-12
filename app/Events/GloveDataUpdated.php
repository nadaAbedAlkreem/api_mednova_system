<?php

namespace App\Events;

use App\Models\GloveData;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GloveDataUpdated implements  ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $tries = 3;
    public $retryAfter = 5;
    public $gloveData;

    public function __construct(GloveData $gloveData)
    {
        $this->gloveData = $gloveData->load('glove');
    }

    public function broadcastOn()
    {
        $customerId = $this->gloveData->glove->customer_id;
        return new PrivateChannel("glove-data.customer.{$customerId}");
    }

    public function broadcastWith()
    {
        return [
            'glove_id'     => $this->gloveData->glove_id,
            'status'       => $this->gloveData->status,
            'flex_thumb'   => $this->gloveData->flex_thumb,
            'flex_index'   => $this->gloveData->flex_index,
            'flex_middle'  => $this->gloveData->flex_middle,
            'flex_ring'    => $this->gloveData->flex_ring,
            'flex_pinky'   => $this->gloveData->flex_pinky,
            'heartbeat'    => $this->gloveData->heartbeat,
            'temperature'  => $this->gloveData->temperature,
            'crc_valid'    => $this->gloveData->crc_valid,
            'updated_at'   => $this->gloveData->updated_at,
        ];
    }

    public function broadcastAs()
    {
        return 'glove-data.updated';
    }
}

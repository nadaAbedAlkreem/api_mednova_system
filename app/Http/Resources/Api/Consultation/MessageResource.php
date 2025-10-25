<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
           return
            [
                'id' => $this->id,
                'sender_id' => new CustomerResource($this->whenLoaded('sender')),
                'receiver_id' =>new CustomerResource($this->whenLoaded('receiver')),
                'message' => $this->message,
                'attachment' =>  $this->attachment,
                'attachment_type' => $this->attachment_type,
                'created_at' => $this->created_at->diffForHumans(),
                'is_sender_current_user' => auth()->id() === $this->sender_id,
                'is_receiver_current_user' => auth()->id() === $this->receiver_id,
                'is_read' => $this->is_read,
                'read_at' =>  $this->created_at->diffForHumans(),
             ] ;
    }
}

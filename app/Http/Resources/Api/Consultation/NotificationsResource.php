<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationsResource extends JsonResource
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
                'id' => $this->id ,
                'customer' => new CustomerResource($this->whenLoaded('notifiable')) ,
                'data' => json_decode($this->data) ,
                'type' => $this->type ,
                'read_at'=> $this->read_at,
                'created_at' =>$this->created_at->format('Y-m-d H:i'),
                ] ;
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessengersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $user = auth()->user() ;
        $model = 'patient';
        if($user->type_account === 'patient'){
            $model = 'consultant';
        }
       return
            [
                'id' => $this->id,
                'messengers' => new CustomerResource($this->whenLoaded($model)),
                'unread_messages_count' => $this->unread_messages_count, // عدد الرسائل غير المقروءة
                'status' => $this->status ,
                'max_messages' => $this->max_messages ,
                'used_messages' => $this->used_messages  ,
                'started_at' => $this->started_at ,
                'ended_at' => $this->ended_at ,

            ] ;
    }
}

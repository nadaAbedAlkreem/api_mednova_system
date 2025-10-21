<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationChatRequestResource extends JsonResource
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
                //يجب عرض تخصص المستشار
                'patient' => new CustomerResource($this->whenLoaded('patient')),
                'consultant' => new CustomerResource($this->whenLoaded('consultant')) ,
                'consultant_type' => $this->consultant_type ,
                'status' => $this->status ,
                'max_messages_for_patient' => $this->max_messages_for_patient ,
                'patient_message_count' => $this->patient_message_count ,
                'consultant_message_count' => $this->consultant_message_count ,
                'first_patient_message_at' => $this->first_patient_message_at ,
                'first_consultant_reply_at' => $this->first_consultant_reply_at ,
                'started_at' => $this->started_at ,
                'ended_at' => $this->ended_at ,
             ] ;
    }
}

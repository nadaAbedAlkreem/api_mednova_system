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
                'status' => $this->status
             ] ;
    }
}

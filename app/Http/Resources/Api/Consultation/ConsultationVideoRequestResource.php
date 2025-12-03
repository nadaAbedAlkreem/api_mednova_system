<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationVideoRequestResource extends JsonResource
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
                'patient' => new CustomerResource($this->whenLoaded('patient')),
                'consultant' => new CustomerResource($this->whenLoaded('consultant')) ,
                'consultant_type' => $this->consultant_type ,
                'appointment'=> new AppointmentResource($this->whenLoaded('appointmentRequest')),
                'status' => $this->status ,
                'consultant_approved' => $this->consultant_approved ,
                'patient_approved' => $this->patient_approved ,
                'duration_minutes' => $this->duration_minutes ?? 10 ,
                'video_room_link' => $this->video_room_link ,
                'session_duration_hours' => $this->session_duration_hours  ?? 1,

             ] ;
    }
}

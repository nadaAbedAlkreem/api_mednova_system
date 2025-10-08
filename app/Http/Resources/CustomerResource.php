<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
                'image' =>  ($this->image)  ,
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone ,
                'type_account' => $this->type_account ,
                'birth_date' => $this->birth_date,
                'gender' => $this->gender,
                'patient_details' => new PatientResource($this->whenLoaded('patient')),
                'location_details' => new LocationResource($this->whenLoaded('location')),
//                'therapist_details' => new PatientResource($this->whenLoaded('therapist')),
//                'center_details' => new PatientResource($this->whenLoaded('rehabilitationCenter'))

             ] ;
    }
}

<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
             'consultant_id' => new CustomerResource($this->whenLoaded('consultant')) ,
             'patient_id' => new CustomerResource($this->whenLoaded('patient')) ,
             'consultant_type'  => $this->consultant_type ,
             'requested_day'=>$this->requested_day ,
             'requested_time' => $this->requested_time ,
             'status'   => $this->status ,
             'type_appointment' => $this->type_appointment ,
             'confirmed_end_time'=> $this->confirmed_end_time
             ] ;
    }
}

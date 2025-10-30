<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use App\Services\api\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

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
             'requested_time' => ($this->requested_time != null) ? $this->requested_time->format('Y-m-d H:i'): null ,
             'confirmed_end_time' => ($this->confirmed_end_time != null)? $this->confirmed_end_time->format('Y-m-d H:i'): null ,
             'status'   => $this->status ,
             'type_appointment' => $this->type_appointment ,
             ] ;
    }
}

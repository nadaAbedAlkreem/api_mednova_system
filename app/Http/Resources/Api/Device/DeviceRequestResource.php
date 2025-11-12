<?php

namespace App\Http\Resources\Api\Device;

use App\Http\Resources\Api\Consultation\ScheduleResource;
use App\Http\Resources\Api\Customer\CenterResource;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Customer\LocationResource;
use App\Http\Resources\Api\Customer\MedicalSpecialtyResource;
use App\Http\Resources\Api\Customer\PatientResource;
use App\Http\Resources\Api\Customer\TherapistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceRequestResource extends JsonResource
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
                'id'=>$this->id,
                'customer' => new CustomerResource($this->whenLoaded('customer')),
                'device' => new DeviceResource($this->whenLoaded('device')),
                'status'=> $this->status ?? 'pending',
                'request_date'=> $this->request_date ?? now(),
                'contact_status'=>$this->contact_status ?? 'pending',
                'notes'=>$this->notes  ,

            ] ;
    }
}

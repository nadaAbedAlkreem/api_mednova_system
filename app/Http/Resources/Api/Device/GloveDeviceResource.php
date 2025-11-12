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

class GloveDeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'device_id'      => new DeviceResource($this->whenLoaded('device')),
            'serial_number'  => $this->serial_number,
            'customer_id'    => new CustomerResource($this->whenLoaded('customer')),
            'status'         => $this->status, // يمكنك تحويل الرقم إلى نص هنا إذا أحببت
            'last_seen_at'   => $this->last_seen_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}

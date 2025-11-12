<?php

namespace App\Http\Resources\Api\Device;

use App\Http\Resources\Api\Consultation\ScheduleResource;
use App\Http\Resources\Api\Customer\CenterResource;
use App\Http\Resources\Api\Customer\LocationResource;
use App\Http\Resources\Api\Customer\MedicalSpecialtyResource;
use App\Http\Resources\Api\Customer\PatientResource;
use App\Http\Resources\Api\Customer\TherapistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
                'name' => $this->name_ar ,

            ] ;
    }
}

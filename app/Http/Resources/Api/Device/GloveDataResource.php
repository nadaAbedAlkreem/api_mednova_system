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

class GloveDataResource extends JsonResource
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
                'glove_id'       => new GloveDeviceResource($this->whenLoaded('glove')),
                'status'         => $this->status,
                'flex_thumb'     => $this->flex_thumb,
                'flex_index'     => $this->flex_index,
                'flex_middle'    => $this->flex_middle,
                'flex_ring'      => $this->flex_ring,
                'flex_pinky'     => $this->flex_pinky,
                'heartbeat'      => $this->heartbeat,
                'temperature'    => $this->temperature,
                'resistance'     => $this->resistance,
                'error_flag'     => $this->error_flag,
                'crc_valid'      => $this->crc_valid,
                'created_at'     => $this->created_at,
                'updated_at'     => $this->updated_at,
            ] ;
    }
}

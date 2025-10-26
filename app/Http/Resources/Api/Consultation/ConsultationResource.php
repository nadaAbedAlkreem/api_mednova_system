<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray($request)
    {
        if ($this->resource instanceof \App\Models\ConsultationChatRequest) {
            return [
                'type' => 'chat',
                'data' => new ConsultationChatRequestResource($this->resource),
            ];
        }

        if ($this->resource instanceof \App\Models\ConsultationVideoRequest) {
            return [
                'type' => 'video',
                'data' => new ConsultationVideoRequestResource($this->resource),
            ];
        }

        return [];
    }
}

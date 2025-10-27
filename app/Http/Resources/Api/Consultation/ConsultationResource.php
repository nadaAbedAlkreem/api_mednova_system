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
        $type = match(true) {
            $this->resource instanceof \App\Models\ConsultationChatRequest => 'chat',
            $this->resource instanceof \App\Models\ConsultationVideoRequest => 'video',
            default => 'unknown'
        };

        $data = match($type) {
            'chat' => new ConsultationChatRequestResource($this->resource),
            'video' => new ConsultationVideoRequestResource($this->resource),
            default => null,
        };
        return [
            'id' => $this->id,
            'type' => $type,
            'status' => $this->status,
            'created_at' => $this->created_at
                ? $this->created_at->locale('ar')->isoFormat('dddd، D MMMM YYYY HH:mm')
                : null,
            'updated_at' => $this->updated_at
                ? $this->updated_at->locale('ar')->isoFormat('dddd، D MMMM YYYY HH:mm')
                : null,
            'data' => $data,
        ];

    }
}

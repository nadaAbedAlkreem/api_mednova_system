<?php

namespace App\Http\Resources\Api\Consultation;

use App\Http\Resources\Api\Customer\CustomerResource;
use App\Services\api\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

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
            'created_at' =>$this->created_at->format('Y-m-d H:i'),
            'updated_at' =>$this->updated_at->format('Y-m-d H:i'),
            'data' => $data,
        ];

    }
}

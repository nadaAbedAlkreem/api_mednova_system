<?php

namespace App\Http\Resources\Api\Consultation;


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
        $type = match (true) {
            $this->resource instanceof \App\Models\ConsultationChatRequest => 'chat',
            $this->resource instanceof \App\Models\ConsultationVideoRequest => 'video',
            default => 'unknown'
        };

        $data = match ($type) {
            'chat' => new ConsultationChatRequestResource($this->resource),
            'video' => new ConsultationVideoRequestResource($this->resource),
            default => null,
        };
        return [
            'id' => $this->id,
            'type' => $type,
            'status' => $this->status,
            'financial_status' => $this->financial_status,
            'review_deadline' => $this->review_deadline,
            'released_at' => $this->released_at,
            'data' => $data,
            'suspended_until' => $this->suspended_until ,
            'suspension_count' => $this->suspension_count ,
            'financial' => [
                'consultation_price' => $this->consultation_price ,
                'gateway_commission_rate' => $this->gateway_commission_rate,
                'gateway_commission_amount' => $this->gateway_commission_amount,
                'gross_amount' => $this->gross_amount,
                'platform_commission_rate' => $this->platform_commission_rate,
                'platform_commission_amount' => $this->platform_commission_amount,
                'consultant_earning_amount' => $this->consultant_earning_amount
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];

    }
}

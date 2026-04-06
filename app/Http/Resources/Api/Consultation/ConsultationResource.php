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
            'financial' => [
                'consultation_price' => $this->consultation_price
                    ? (float)$this->consultation_price
                    : null,
                'gateway_commission_rate' => $this->gateway_commission_rate . '%'
                    ? (float)$this->gateway_commission_rate . '%'
                    : null,
                'gateway_commission_amount' => $this->gateway_commission_amount
                    ? (float)$this->gateway_commission_amount
                    : null,
                'net_amount' => $this->net_amount
                    ? (float)$this->net_amount
                    : null,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];

    }
}

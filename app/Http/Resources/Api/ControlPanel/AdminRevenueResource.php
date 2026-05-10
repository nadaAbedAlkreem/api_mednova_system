<?php

namespace App\Http\Resources\Api\ControlPanel;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRevenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reference = $this->reference;
        $isChat    = str_contains($this->reference_type ?? '', 'Chat');

        return [
            'id'                          => $this->id,
            'consultation'                => $this->buildConsultation($reference, $isChat),
            'consultation_price'          => number_format((float) ($reference->consultation_price ?? 0), 3, '.', ''),
            'platform_commission_rate'    => number_format((float) ($reference->platform_commission_rate ?? 0), 2, '.', ''),
            'platform_commission_amount'  => number_format((float) ($reference->platform_commission_amount ?? 0), 3, '.', ''),
            'consultant_earning_amount'   => number_format((float) ($reference->consultant_earning_amount ?? 0), 3, '.', ''),
            'created_at'                  => $this->created_at?->toIso8601String(),
        ];
    }

    private function buildConsultation($reference, bool $isChat): ?array
    {
        if (!$reference) {
            return null;
        }

        return [
            'id'              => $reference->id,
            'type'            => $isChat ? 'chat' : 'video',
            'patient_name'    => $reference->patient?->full_name,
            'consultant_name' => $reference->consultant?->full_name,
        ];
    }
}

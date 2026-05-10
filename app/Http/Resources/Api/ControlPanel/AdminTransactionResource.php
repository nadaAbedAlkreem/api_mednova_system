<?php

namespace App\Http\Resources\Api\ControlPanel;

use App\Enums\EntryType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTransactionResource extends JsonResource
{
    private const LABELS = [
        'consultation_hold'    => ['ar' => 'حجز مبلغ استشارة', 'en' => 'Consultation Escrow'],
        'consultation_release' => ['ar' => 'تحرير من الضمان',  'en' => 'Escrow Release'],
        'platform_fee'         => ['ar' => 'عمولة منصة',       'en' => 'Platform Commission'],
        'dispute_freeze'       => ['ar' => 'تجميد بسبب نزاع', 'en' => 'Dispute Freeze'],
        'dispute_release'      => ['ar' => 'فك تجميد نزاع',   'en' => 'Dispute Release'],
        'refund'               => ['ar' => 'استرداد للمريض',   'en' => 'Patient Refund'],
        'payment_record'       => ['ar' => 'سجل دفع',         'en' => 'Payment Record'],
    ];

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id'           => $this->id,
            'type'         => $this->transaction_type,
            'label'        => self::LABELS[$this->transaction_type][$locale]
                ?? ucwords(str_replace('_', ' ', $this->transaction_type)),
            'entry_type'   => $this->entry_type,
            'amount'       => $this->formatSignedAmount(),
            'currency'     => $this->currency,
            'status'       => $this->status,
            'consultation' => $this->buildConsultation(),
            'meta'         => $this->meta,
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function formatSignedAmount(): string
    {
        $formatted = number_format((float) $this->net_amount, 3, '.', '');

        return $this->entry_type === EntryType::ENTRY_CREDIT->value
            ? '+' . $formatted
            : '-' . $formatted;
    }

    private function buildConsultation(): ?array
    {
        $reference = $this->reference;

        if (!$reference) {
            return null;
        }

        $isChat = str_contains($this->reference_type ?? '', 'Chat');

        return [
            'id'              => $reference->id,
            'type'            => $isChat ? 'chat' : 'video',
            'patient_name'    => $reference->patient?->full_name,
            'consultant_name' => $reference->consultant?->full_name,
        ];
    }
}

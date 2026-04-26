<?php

namespace App\Http\Resources\Api\Financial;

use App\Enums\ConsultationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    // ── Serialisation ─────────────────────────────────────────────────────────
    // ── Labels ────────────────────────────────────────────────────────────────

    private const STATUS_LABELS = [
        'captured'   => ['ar' => 'تم الدفع بنجاح',  'en' => 'Payment Successful'],
        'failed'     => ['ar' => 'فشل الدفع',        'en' => 'Payment Failed'],
        'authorized' => ['ar' => 'قيد المعالجة',     'en' => 'Processing'],
        'initiated'  => ['ar' => 'قيد المعالجة',     'en' => 'Processing'],
        'expired'    => ['ar' => 'منتهي الصلاحية',   'en' => 'Expired'],
        'refunded'   => ['ar' => 'تم الاسترداد',      'en' => 'Refunded'],
    ];

    private const REFUND_NOTES = [
        'ar' => 'تم استرداد مبلغ الاستشارة إلى رصيدك — رسوم البوابة غير قابلة للاسترداد',
        'en' => 'Consultation amount refunded to your balance — gateway fees are non-refundable',
    ];

    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id'               => $this->id,
            'consultation'     => $this->buildConsultationPayload(),
            'amount_paid'      => $this->formatAmount($this->amount),
            'gateway_fee'      => $this->resolveGatewayFee(),
            'payment_method'   => $this->payment_method,
            'status'           => $this->status,
            'status_label'     => $this->resolveStatusLabel($locale),
            'is_refunded'      => (bool) ($this->is_refunded ?? false),
            'refunded_amount'  => $this->resolveRefundedAmount(),
            'refund_note'      => $this->resolveRefundNote($locale),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function resolveStatusLabel(string $locale): string
    {
        return self::STATUS_LABELS[$this->status][$locale]
            ?? ucwords(str_replace('_', ' ', $this->status));
    }

    private function formatAmount($value): string
    {
        return number_format((float) $value, 3, '.', '');
    }

    private function buildConsultationPayload(): ?array
    {
        $reference = $this->reference;

        if (! $reference) {
            return null;
        }

        return [
            'id'                 => $reference->id,
            'type'               => $this->resolveConsultationType(),
            'consultant_name'    => $reference->consultant?->full_name,
            'consultation_price' => $this->formatAmount($reference->consultation_price ?? 0),
        ];
    }

    private function resolveConsultationType(): string
    {
        if (str_contains($this->reference_type ?? '', 'Chat')) {
            return ConsultationType::CHAT->value;
        }

        if (str_contains($this->reference_type ?? '', 'Video')) {
            return ConsultationType::VIDEO->value;
        }

        return 'unknown';
    }

    private function resolveGatewayFee(): ?string
    {
        $reference = $this->reference;

        if (! $reference || $reference->gateway_commission_amount === null) {
            return null;
        }

        return $this->formatAmount($reference->gateway_commission_amount);
    }

    private function resolveRefundedAmount(): ?string
    {
        if (! ($this->is_refunded ?? false)) {
            return null;
        }

        $amount = $this->refunded_amount ?? null;

        return $amount !== null ? $this->formatAmount($amount) : null;
    }

    private function resolveRefundNote(string $locale): ?string
    {
        if (! ($this->is_refunded ?? false)) {
            return null;
        }

        return self::REFUND_NOTES[$locale] ?? self::REFUND_NOTES['ar'];
    }
}

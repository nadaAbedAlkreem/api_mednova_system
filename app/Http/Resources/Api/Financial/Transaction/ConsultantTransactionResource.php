<?php

namespace App\Http\Resources\Api\Financial\Transaction;

use App\Enums\ConsultationType;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultantTransactionResource extends JsonResource
{
    private const LABELS = [
        'consultation_credit' => ['ar' => 'أرباح استشارة', 'en' => 'Consultation Earnings'],
        'dispute_freeze' => ['ar' => 'تجميد بسبب نزاع', 'en' => 'Dispute Hold'],
        'dispute_release' => ['ar' => 'فك تجميد', 'en' => 'Dispute Resolved'],
        'withdrawal' => ['ar' => 'سحب أرباح', 'en' => 'Withdrawal'],
    ];

    private const STATUS_LABELS = [
        'pending' => ['ar' => 'معلّق', 'en' => 'Pending'],
        'available' => ['ar' => 'متاح', 'en' => 'Available'],
        'frozen' => ['ar' => 'مجمّد', 'en' => 'Frozen'],
    ];


    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        return [
            'id' => $this->id,
            'type' => $this->transaction_type,
            'label' => $this->resolveLabel($locale),
            'amount' => $this->formatSignedAmount(),
            'currency' => $this->currency,
            'status' => $this->status,
            'status_label' => $this->resolveStatusLabel($locale),
            'consultation' => $this->buildConsultationPayload(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function resolveLabel(string $locale): string
    {
        return self::LABELS[$this->transaction_type][$locale]
            ?? ucwords(str_replace('_', ' ', $this->transaction_type));
    }

    private function resolveStatusLabel(string $locale): string
    {
        return self::STATUS_LABELS[$this->status][$locale]
            ?? ucwords(str_replace('_', ' ', $this->status));
    }

    private function formatSignedAmount(): string
    {
        $formatted = number_format((float)$this->net_amount, 3, '.', '');

        return $this->entry_type === EntryType::ENTRY_CREDIT->value
            ? '+' . $formatted
            : '-' . $formatted;
    }

    private function buildConsultationPayload(): ?array
    {
        if ($this->transaction_type === TransactionType::WITHDRAWAL->value) {
            return null;
        }

        $reference = $this->reference;

        if (!$reference) {
            return null;
        }

        return [
            'id' => $reference->id,
            'type' => $this->resolveConsultationType(),
            'patient_name' => isset($reference->patient)
                ? $this->shortenName($reference->patient->full_name)
                : null,
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

    private function shortenName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), 3);

        if (count($parts) < 2) {
            return $parts[0] ?? $fullName;
        }

        return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
    }
}

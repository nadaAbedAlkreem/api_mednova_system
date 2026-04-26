<?php

namespace App\Http\Resources\Api\Financial\Transaction;

use App\Enums\ConsultationType;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientTransactionResource extends JsonResource
{
    // ── Labels ────────────────────────────────────────────────────────────────

    private const LABELS = [
        'refund'          => ['ar' => 'استرداد مبلغ استشارة', 'en' => 'Consultation Refund'],
        'withdrawal'      => ['ar' => 'سحب مكتمل',             'en' => 'Withdrawal Complete'],
        'dispute_release' => ['ar' => 'استرداد بعد نزاع',      'en' => 'Dispute Refund'],
    ];

    private const STATUS_LABELS = [
        'pending'   => ['ar' => 'معلّق', 'en' => 'Pending'],
        'available' => ['ar' => 'متاح',  'en' => 'Available'],
        'frozen'    => ['ar' => 'مجمّد', 'en' => 'Frozen'],
    ];

    /**
     * Special status label override for refund transactions —
     * "تم الإيداع في رصيدك" reads better than "متاح" for a patient refund.
     */
    private const REFUND_STATUS_LABELS = [
        'ar' => 'تم الإيداع في رصيدك',
        'en' => 'Credited to Your Balance',
    ];

    /**
     * Note shown only on refund-related transactions to clarify
     * gateway fees are not refunded.
     */
    private const REFUND_NOTES = [
        'ar' => 'مبلغ الاستشارة فقط — رسوم البوابة غير قابلة للاسترداد',
        'en' => 'Consultation amount only — gateway fees are non-refundable',
    ];

    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id'           => $this->id,
            'type'         => $this->transaction_type,
            'label'        => $this->resolveLabel($locale),
            'description'  => $this->buildDescription($locale),
            'amount'       => $this->formatSignedAmount(),
            'currency'     => $this->currency,
            'status'       => $this->status,
            'status_label' => $this->resolveStatusLabel($locale),
            'note'         => $this->resolveNote($locale),
            'consultation' => $this->buildConsultationPayload(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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
        // Refund transactions get a friendlier status label for patients
        if ($this->isRefundType()) {
            return self::REFUND_STATUS_LABELS[$locale] ?? self::REFUND_STATUS_LABELS['ar'];
        }

        return self::STATUS_LABELS[$this->status][$locale]
            ?? ucwords(str_replace('_', ' ', $this->status));
    }

    private function resolveNote(?string $locale): ?string
    {
        if (! $this->isRefundType()) {
            return null;
        }

        return self::REFUND_NOTES[$locale] ?? self::REFUND_NOTES['ar'];
    }

    private function isRefundType(): bool
    {
        return in_array(
            $this->transaction_type,
            [TransactionType::REFUND->value, TransactionType::DISPUTE_RELEASE->value],
            true
        );
    }

    private function formatSignedAmount(): string
    {
        $formatted = number_format((float) $this->net_amount, 3, '.', '');

        return $this->entry_type === EntryType::ENTRY_CREDIT->value
            ? '+' . $formatted
            : '-' . $formatted;
    }

    private function buildDescription(string $locale): ?string
    {
        $reference = $this->reference;

        // Withdrawal = no consultation context
        if ($this->transaction_type === TransactionType::WITHDRAWAL->value) {
            return $locale === 'en'
                ? 'Transferred to bank account'
                : 'تم التحويل إلى الحساب البنكي';
        }

        // Refund / dispute release with consultation context
        if ($reference && isset($reference->consultant)) {
            $consultantName = $reference->consultant->full_name;
            $consultationType = $this->resolveConsultationType();

            if ($locale === 'en') {
                $typeLabel = $consultationType === 'chat' ? 'chat' : 'video';
                return "The {$typeLabel} consultation with Dr. {$consultantName} was cancelled";
            }

            $typeLabel = $consultationType === 'chat' ? 'النصية' : 'المرئية';
            return "تم إلغاء الاستشارة {$typeLabel} مع د. {$consultantName}";
        }

        return null;
    }

    private function buildConsultationPayload(): ?array
    {
        // Withdrawals are not tied to a consultation
        if ($this->transaction_type === TransactionType::WITHDRAWAL->value) {
            return null;
        }

        $reference = $this->reference;

        if (! $reference) {
            return null;
        }

        return [
            'id'              => $reference->id,
            'type'            => $this->resolveConsultationType(),
            'consultant_name' => $reference->consultant?->full_name,
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
}

<?php

namespace App\Http\Resources\Api\Financial;

use App\Enums\AccountStatus;
use App\Enums\AmountStatus;
use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultantTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    private const TYPE_LABELS = [
        TransactionType::CONSULTATION_CREDIT->value => 'Consultation Earnings',
        TransactionType::REFUND->value => 'Refund Issued',
        TransactionType::DISPUTE_FREEZE->value => 'Dispute Freeze',
        TransactionType::WITHDRAWAL->value => 'Withdrawal',
        TransactionType::PAYMENT_RECORD->value => 'Payment Record',
    ];

    // Map internal status values to display labels


    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resource->transaction_type,        // machine key for frontend logic
            'type_label' => $this->resolveTypeLabel(),                // human readable
            'amount' => $this->resolveSignedAmount(),             // signed float
            'amount_formatted' => $this->formatAmountDisplay(),            // "− 5.000 OMR"
            'currency' => $this->currency,
            'status' => $this->resource->status,
            'consultation_id' => $this->consultation_id,                   // nullable — from meta
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->resource->transaction_type]
            ?? ucwords(str_replace('_', ' ', $this->resource->transaction_type));
    }


    /**
     * Returns a signed float for the net_amount:
     *   Credits  → positive  (e.g.  25.500)
     *   Debits   → negative  (e.g. -10.000)
     */
    private function resolveSignedAmount(): float
    {
        return (float)$this->signed_amount; // computed attribute on Transaction model
    }

    /**
     * Formats for display: "25.500 OMR" or "− 10.000 OMR"
     */
    private function formatAmountDisplay(): string
    {
        $amount = (float)$this->signed_amount;
        $sign = $amount >= 0 ? '' : '− ';
        $abs = number_format(abs($amount), 3);

        return "{$sign}{$abs} {$this->currency}";
    }
}

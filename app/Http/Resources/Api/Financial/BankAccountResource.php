<?php

namespace App\Http\Resources\Api\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    // ── Labels ────────────────────────────────────────────────────────────────

    private const STATUS_LABELS = [
        'pending'  => ['ar' => 'قيد التحقق',  'en' => 'Pending Verification'],
        'verified' => ['ar' => 'تم التحقق',    'en' => 'Verified'],
        'rejected' => ['ar' => 'مرفوض',        'en' => 'Rejected'],
        'blocked'  => ['ar' => 'محظور',        'en' => 'Blocked'],
    ];

    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id'                  => $this->id,
            'bank_name'           => $this->bank_name,
            'account_holder_name' => $this->account_holder_name,
            'account_number'      => $this->maskAccountNumber($this->account_number),
            'iban'                => $this->maskIban($this->iban),
            'swift_code'          => $this->swift_code,
            'bank_country'        => $this->bank_country,
            'status'              => $this->status,
            'status_label'        => self::STATUS_LABELS[$this->status][$locale]
                                     ?? ucwords(str_replace('_', ' ', $this->status)),
            'is_default'          => (bool) $this->is_default,
            'verified_at'         => $this->verified_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function maskAccountNumber(?string $value): ?string
    {
        if (!$value || strlen($value) < 4) {
            return $value;
        }

        return str_repeat('*', strlen($value) - 4) . substr($value, -4);
    }

    private function maskIban(?string $value): ?string
    {
        if (!$value || strlen($value) < 6) {
            return $value;
        }

        return str_repeat('*', strlen($value) - 6) . substr($value, -6);
    }
}

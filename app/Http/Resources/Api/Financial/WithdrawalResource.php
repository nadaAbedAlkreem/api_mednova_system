<?php

namespace App\Http\Resources\Api\Financial;

use App\Enums\WithdrawalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id'           => $this->id,
            'amount'       => number_format((float) $this->amount, 3, '.', ''),
            'currency'     => $this->currency,
            'status'       => $this->status->value,
            'status_label' => $this->status->label($locale),
            'bank_account' => $this->resolveBankAccount($locale),
            'admin_note'   => $this->when(
                $this->status === WithdrawalStatus::REJECTED,
                $this->admin_note
            ),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function resolveBankAccount(string $locale): ?array
    {
        $bank = $this->whenLoaded('bankAccount');

        if (!$bank) {
            return null;
        }

        return [
            'bank_name'           => $bank->bank_name,
            'account_holder_name' => $bank->account_holder_name,
            'account_number'      => $this->maskAccountNumber($bank->account_number),
            'iban'                => $this->maskIban($bank->iban),
        ];
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

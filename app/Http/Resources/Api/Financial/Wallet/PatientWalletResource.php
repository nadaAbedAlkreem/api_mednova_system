<?php

namespace App\Http\Resources\Api\Financial\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientWalletResource extends JsonResource
{
    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        return [
            'total_balance'      => $this->amount('available_balance', 'pending_balance'),
            'available_balance'  => $this->amount('available_balance'),
            'pending_withdrawal' => $this->amount('pending_balance'),
            'currency'           => $this->resource?->currency ?? 'OMR',
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function amount(string ...$fields): string
    {
        $sum = array_reduce($fields, function (float $carry, string $field): float {
            return $carry + (float) ($this->resource?->$field ?? 0);
        }, 0.0);

        return number_format($sum, 3, '.', '');
    }
}

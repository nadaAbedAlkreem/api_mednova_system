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
            'pending_withdrawal' => $this->amount('pending_balance'),// هو مبالغ التي تم طلب السحب وبانتظار تحويل خارج المنصة
            'withdrawable_balance' => $this->amount('available_balance'), // حاليا يساوي متاح ولكن في حال مستقبل كان في قيد على حد ادنى او اعلي
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

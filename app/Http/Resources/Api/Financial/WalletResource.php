<?php

namespace App\Http\Resources\Api\Financial;

use App\Enums\ConsultantType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    private bool $isConsultantView = false;

    // ── View toggles ──────────────────────────────────────────────────────────

    public function forConsultant(): static
    {
        $this->isConsultantView = true;
        return $this;
    }

    public function forPatient(): static
    {
        $this->isConsultantView = false;
        return $this;
    }

    // ── Serialisation ─────────────────────────────────────────────────────────

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isConsultant = $user && $user->type_account !== ConsultantType::PATIENT->value;
        $base = [
            'available_balance' => $this->formatAmount($this->available_balance),
            'currency'          => $this->currency,
            'last_updated'      => $this->updated_at?->toIso8601String(),
        ];
        if ($isConsultant) {
            return array_merge($base, [
                'pending_balance' => $this->formatAmount($this->pending_balance),
                'frozen_balance'  => $this->formatAmount($this->frozen_balance),
                'total_balance'   => $this->formatAmount($this->total_balance),
            ]);
        }

        return $base;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Format a decimal amount to 3 decimal places as a float.
     * bcmath is used internally; we expose as float for JSON.
     */
    private function formatAmount(mixed $value): float
    {
        return round((float) $value, 3);
    }
}

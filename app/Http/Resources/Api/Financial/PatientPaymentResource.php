<?php

namespace App\Http\Resources\Api\Financial;

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

    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'consultation_id' => $this->reference_id,    // reference is always Consultation for patient payments
            'amount'          => (float) $this->amount,
            'amount_formatted'=> number_format((float) $this->amount, 3) . ' ' . $this->currency,
            'net_received_amount'=>  $this->net_received_amount ,
            'net_received_amount_formatted'=> number_format((float) $this->net_received_amount, 3) . ' ' . $this->currency,
            'currency'        => $this->currency,
            'payment_method'  => $this->payment_method_label,  // "Credit / Debit Card"
            'status'          => $this->resolvePatientStatus(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }


}

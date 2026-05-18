<?php

namespace App\Http\Resources\Api\ControlPanel;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWithdrawalDetailResource extends JsonResource
{
    private const STATUS_LABELS = [
        'pending_review'    => ['ar' => 'بانتظار المراجعة', 'en' => 'Pending Review'],
        'transferred'       => ['ar' => 'تم التحويل',       'en' => 'Transferred'],
        'rejected'          => ['ar' => 'مرفوض',            'en' => 'Rejected'],
        'cancelled_by_user' => ['ar' => 'ملغى',             'en' => 'Cancelled'],
    ];

    public function toArray(Request $request): array
    {
        $locale = str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
        $status = $this->status instanceof \App\Enums\WithdrawalStatus
            ? $this->status->value
            : (string) $this->status;

        return [
            'withdrawal' => [
                'id'                  => $this->id,
                'amount'              => number_format((float) $this->amount, 3, '.', ''),
                'currency'            => $this->currency ?? 'OMR',
                'status'              => $status,
                'status_label'        => self::STATUS_LABELS[$status][$locale] ?? $status,
                'admin_note'          => $this->admin_note,
                'transfer_reference'  => $this->transfer_reference,
                'has_transfer_proof'  => (bool) $this->transfer_proof_path,
                'transfer_proof_url'  => $this->transfer_proof_path
                    ? "/api/control-panel/financial/withdrawals/{$this->id}/proof"
                    : null,
                'created_at'          => $this->created_at?->toIso8601String(),
                'processed_at'        => $this->processed_at?->toIso8601String(),
            ],
            'user' => [
                'id'        => $this->owner?->id,
                'full_name' => $this->owner?->full_name,
                'type'      => $this->owner?->type_account,
                'email'     => $this->owner?->email,
                'phone'     => $this->owner?->phone,
            ],
            'bank_account' => $this->bankAccount ? [
                'bank_name'            => $this->bankAccount->bank_name,
                'account_holder_name'  => $this->bankAccount->account_holder_name,
                'account_number'       => $this->bankAccount->account_number,
                'iban'                 => $this->bankAccount->iban,
                'swift_code'           => $this->bankAccount->swift_code,
                'bank_country'         => $this->bankAccount->bank_country,
                'status'               => $this->bankAccount->status,
            ] : null,
            'wallet_snapshot' => $this->wallet ? [
                'available_balance' => number_format((float) $this->wallet->available_balance, 3, '.', ''),
                'pending_balance'   => number_format((float) $this->wallet->pending_balance,   3, '.', ''),
                'total_balance'     => number_format(
                    (float) $this->wallet->available_balance + (float) $this->wallet->pending_balance,
                    3, '.', ''
                ),
            ] : null,
        ];
    }
}

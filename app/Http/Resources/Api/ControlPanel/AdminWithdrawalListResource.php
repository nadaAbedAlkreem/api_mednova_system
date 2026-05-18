<?php

namespace App\Http\Resources\Api\ControlPanel;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWithdrawalListResource extends JsonResource
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
            'id'           => $this->id,
            'user'         => [
                'id'        => $this->owner?->id,
                'full_name' => $this->owner?->full_name,
                'type'      => $this->owner?->type_account,
            ],
            'amount'        => number_format((float) $this->amount, 3, '.', ''),
            'currency'      => $this->currency ?? 'OMR',
            'status'        => $status,
            'status_label'  => self::STATUS_LABELS[$status][$locale] ?? $status,
            'created_at'    => $this->created_at?->toIso8601String(),
            'processed_at'  => $this->processed_at?->toIso8601String(),
        ];
    }
}

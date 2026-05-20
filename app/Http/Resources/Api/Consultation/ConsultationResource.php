<?php

namespace App\Http\Resources\Api\Consultation;

use App\Enums\ConsultantType;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    private const PATIENT_FINANCIAL_LABELS = [
        'unpaid'            => ['ar' => 'غير مدفوعة',     'en' => 'Unpaid'],
        'held'              => ['ar' => 'تم الدفع',        'en' => 'Paid'],
        'review_window'     => ['ar' => 'فترة المراجعة',   'en' => 'Review Period'],
        'withdrawable'      => ['ar' => 'مكتملة',          'en' => 'Completed'],
        'withdrawn'         => ['ar' => 'مكتملة',          'en' => 'Completed'],
        'refunded'          => ['ar' => 'تم الاسترداد',    'en' => 'Refunded'],
        'refunded_internal' => ['ar' => 'تم الاسترداد',    'en' => 'Refunded'],
        'frozen'            => ['ar' => 'قيد المراجعة',    'en' => 'Under Review'],
        'payment_suspended' => ['ar' => 'معلّقة مؤقتاً',   'en' => 'Temporarily Suspended'],
    ];

    private const CONSULTANT_FINANCIAL_LABELS = [
        'unpaid'            => ['ar' => 'بانتظار الدفع',        'en' => 'Awaiting Payment'],
        'held'              => ['ar' => 'بانتظار تقديم الخدمة', 'en' => 'Awaiting Service'],
        'review_window'     => ['ar' => 'بانتظار التسوية',      'en' => 'Awaiting Settlement'],
        'withdrawable'      => ['ar' => 'قابل للسحب',           'en' => 'Withdrawable'],
        'withdrawn'         => ['ar' => 'تم السحب',             'en' => 'Withdrawn'],
        'refunded'          => ['ar' => 'تم إلغاء الاستشارة',  'en' => 'Consultation Cancelled'],
        'refunded_internal' => ['ar' => 'تم إلغاء الاستشارة',  'en' => 'Consultation Cancelled'],
        'frozen'            => ['ar' => 'قيد المراجعة',         'en' => 'Under Review'],
        'payment_suspended' => ['ar' => 'معلّقة مؤقتاً',        'en' => 'Temporarily Suspended'],
    ];

    public function toArray($request)
    {
        $type = match (true) {
            $this->resource instanceof \App\Models\ConsultationChatRequest  => 'chat',
            $this->resource instanceof \App\Models\ConsultationVideoRequest => 'video',
            default => 'unknown',
        };

        $data = match ($type) {
            'chat'  => new ConsultationChatRequestResource($this->resource),
            'video' => new ConsultationVideoRequestResource($this->resource),
            default => null,
        };

        return [
            'id'               => $this->id,
            'type'             => $type,
//            'status'                 => $this->status,
            'financial_status_label' => $this->resolveFinancialStatusLabel($request),
            'financial_status' => $this->financial_status,
//            'review_deadline'  => $this->review_deadline,
//            'released_at'      => $this->released_at,
            'data'             => $data,
            'suspended_until'  => $this->suspended_until,
            'suspension_count' => $this->suspension_count,
            'financial'        => $this->buildFinancialPayload($request),
            'created_at'       => $this->created_at->format('Y-m-d H:i'),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i'),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function buildFinancialPayload($request): array
    {
        $user = $request->user('api');

        if (! $user) {
            return [];
        }

        return $this->isConsultant($user)
            ? $this->financialForConsultant()
            : $this->financialForPatient();
    }

    private function isConsultant($user): bool
    {
        return in_array($user->type_account, [
            ConsultantType::THERAPIST->value,
            ConsultantType::REHABILITATION_CENTER->value,
        ], true);
    }

    private function resolveFinancialStatusLabel($request): string
    {
        $locale = $this->resolveLocale($request);
        $status = $this->financial_status;
        $user   = $request->user('api');

        if (! $user) {
            return ucwords(str_replace('_', ' ', $status));
        }

        $labels = $this->isConsultant($user)
            ? self::CONSULTANT_FINANCIAL_LABELS
            : self::PATIENT_FINANCIAL_LABELS;

        return $labels[$status][$locale]
            ?? ucwords(str_replace('_', ' ', $status));
    }

    private function resolveLocale($request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en')
            ? 'en' : 'ar';
    }

    /**
     * What the consultant sees:
     * - Their share of the consultation
     * - Platform commission (so they understand how earnings are calculated)
     * Hidden:
     * - gross_amount, gateway_fee (patient's payment details)
     */
    private function financialForConsultant(): array
    {
        return [
            'consultation_price'         => $this->consultation_price,
            'platform_commission_rate'   => $this->platform_commission_rate,
            'platform_commission_amount' => $this->platform_commission_amount,
            'your_earning'               => $this->consultant_earning_amount,
        ];
    }

    /**
     * What the patient sees:
     * - What they paid in total (gross)
     * - Breakdown: consultation price + gateway fee
     * Hidden:
     * - platform_commission (internal to platform)
     * - consultant_earning (none of patient's business)
     */
    private function financialForPatient(): array
    {
        return [
            'consultation_price'        => $this->consultation_price,
            'gateway_commission_rate'   => $this->gateway_commission_rate,
            'gateway_commission_amount' => $this->gateway_commission_amount,
            'gross_amount'              => $this->gross_amount,
        ];
    }
}


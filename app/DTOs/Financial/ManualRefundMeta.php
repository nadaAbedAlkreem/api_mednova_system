<?php

namespace App\DTOs\Financial;

/**
 * ManualRefundMeta
 *
 * DTO يُمثّل بيانات الـ meta الخاصة باسترداد يدوي مطلوب.
 * يُحدَّث manual_refund_completed إلى true بعد تنفيذ الاسترداد الفعلي من Gateway.
 *
 * يُحفظ في حقل meta بجدول transactions كـ JSON.
 */
readonly class ManualRefundMeta
{
    public function __construct(
        public bool   $manualRefundRequired,
        public bool   $manualRefundCompleted,
        public int    $consultationId,
        public int    $patientId,
        public int    $consultantId,
    ) {}

    /**
     * استرداد معلّق — بانتظار تنفيذ يدوي من Gateway
     */
    public static function pending($consultation): self
    {
        return new self(
            manualRefundRequired:  true,
            manualRefundCompleted: false,
            consultationId:        $consultation->id,
            patientId:             $consultation->patient_id,
            consultantId:          $consultation->consultant_id,
        );
    }

    /**
     * استرداد مكتمل — تم التنفيذ من Gateway
     */
    public static function completed($consultation): self
    {
        return new self(
            manualRefundRequired:  true,
            manualRefundCompleted: true,
            consultationId:        $consultation->id,
            patientId:             $consultation->patient_id,
            consultantId:          $consultation->consultant_id,
        );
    }

    public function toArray(): array
    {
        return [
            'manual_refund_required'  => $this->manualRefundRequired,
            'manual_refund_completed' => $this->manualRefundCompleted,
            'consultation_id'         => $this->consultationId,
            'patient_id'              => $this->patientId,
            'consultant_id'           => $this->consultantId,
        ];
    }
}

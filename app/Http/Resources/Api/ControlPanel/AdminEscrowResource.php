<?php

namespace App\Http\Resources\Api\ControlPanel;

use App\Models\ConsultationChatRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEscrowResource extends JsonResource
{
    private const STATUS_LABELS = [
        'held'          => ['ar' => 'بانتظار اكتمال الاستشارة', 'en' => 'Awaiting Completion'],
        'review_window' => ['ar' => 'نافذة المراجعة',           'en' => 'Review Window'],
    ];

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        $status = $this->financial_status;
        $isChat = $this->resource instanceof ConsultationChatRequest;

        return [
            'consultation_id'  => $this->id,
            'type'             => $isChat ? 'chat' : 'video',
            'patient_name'     => $this->patient?->full_name,
            'consultant_name'  => $this->consultant?->full_name,
            'amount'           => number_format((float) $this->consultation_price, 3, '.', ''),
            'financial_status' => $status,
            'status_label'     => self::STATUS_LABELS[$status][$locale] ?? $status,
            'review_deadline'  => $this->review_deadline?->toIso8601String(),
            'time_remaining'   => $this->resolveTimeRemaining($locale),
            'paid_at'          => $this->created_at?->toIso8601String(),
            'currency'         => 'OMR',
        ];
    }

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function resolveTimeRemaining(string $locale): ?string
    {
        if ($this->financial_status !== 'review_window' || !$this->review_deadline) {
            return null;
        }

        $deadline = Carbon::parse($this->review_deadline);
        $now      = Carbon::now();

        if ($deadline->isPast()) {
            return $locale === 'en' ? 'Expired' : 'منتهية';
        }

        $hoursRemaining = (int) $now->diffInHours($deadline);

        if ($hoursRemaining > 24) {
            $days = (int) $now->diffInDays($deadline);
            return $locale === 'en' ? "{$days} days" : "{$days} يوم";
        }

        return $locale === 'en' ? "{$hoursRemaining} hours" : "{$hoursRemaining} ساعة";
    }
}

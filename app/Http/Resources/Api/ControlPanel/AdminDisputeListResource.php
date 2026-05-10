<?php

namespace App\Http\Resources\Api\ControlPanel;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDisputeListResource extends JsonResource
{
    private const STATUS_LABELS = [
        'opened'       => ['ar' => 'مفتوح',        'en' => 'Open'],
        'under_review' => ['ar' => 'قيد المراجعة', 'en' => 'Under Review'],
        'resolved'     => ['ar' => 'محلول',        'en' => 'Resolved'],
        'rejected'     => ['ar' => 'مرفوض',        'en' => 'Rejected'],
    ];

    public function toArray(Request $request): array
    {
        $locale    = $this->resolveLocale($request);
        $reference = $this->reference;
        $isChat    = str_contains($this->reference_type ?? '', 'Chat');

        return [
            'id'                      => $this->id,
            'consultation_id'         => $reference?->id,
            'consultation_type'       => $isChat ? 'chat' : 'video',
            'patient_name'            => $reference?->patient?->full_name,
            'consultant_name'         => $reference?->consultant?->full_name,
            'amount'                  => number_format((float) $this->amount, 3, '.', ''),
            'currency'                => $this->currency ?? 'OMR',
            'reason'                  => $this->meta['reason'] ?? null,
            'status'                  => $this->status,
            'status_label'            => self::STATUS_LABELS[$this->status][$locale] ?? $this->status,
            'resolution'              => $this->resolution ?? 'none',
            'opened_at'               => $this->opened_at?->toIso8601String(),
            'hours_since_opened'      => $this->opened_at
                ? Carbon::parse($this->opened_at)->diffInHours(now())
                : null,
            'resolved_at'             => $this->resolved_at?->toIso8601String(),
            'service_evidence_summary' => $this->buildEvidence($reference, $isChat),
        ];
    }

    private function resolveLocale(Request $request): string
    {
        return str_starts_with($request->header('Accept-Language', ''), 'en') ? 'en' : 'ar';
    }

    private function buildEvidence($reference, bool $isChat): ?array
    {
        if (!$reference) {
            return null;
        }

        if ($isChat) {
            $patientCount    = (int) ($reference->patient_message_count    ?? 0);
            $consultantCount = (int) ($reference->consultant_message_count ?? 0);

            return [
                'type'                     => 'chat',
                'consultant_responded'     => $consultantCount > 0,
                'consultant_message_count' => $consultantCount,
                'patient_message_count'    => $patientCount,
            ];
        }

        $activities = $reference->activities ?? collect();

        return [
            'type'         => 'video',
            'joined_count' => $activities->where('status', '!=', 'no_show')->count(),
         ];
    }
}

<?php

namespace App\Http\Resources\Api\ControlPanel;

use App\Models\ConsultationVideoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reference = $this->reference;
        $isChat    = str_contains($this->reference_type, 'Chat');

        return [
            'dispute'          => $this->buildDispute(),
            'consultation'     => $this->buildConsultation($reference, $isChat),
            'patient'          => $this->buildParty($reference->patient ?? null, null),
            'consultant'       => $this->buildParty($reference->consultant ?? null, $reference->consultant_type ?? null),
            'service_evidence' => [
                'chat'  => $isChat  ? $this->buildChatEvidence($reference)  : null,
                'video' => !$isChat ? $this->buildVideoEvidence($reference) : null,
            ],
        ];
    }

    private function buildDispute(): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'resolution'  => $this->resolution,
            'amount'      => $this->amount,
            'currency'    => $this->currency,
            'reason'      => $this->meta['reason'] ?? null,
            'opened_at'   => $this->opened_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'opened_by'   => $this->openedBy ? [
                'id'        => $this->openedBy->id,
                'full_name' => $this->openedBy->full_name ?? $this->openedBy->name ?? null,
            ] : null,
        ];
    }

    private function buildConsultation($reference, bool $isChat): array
    {
        return [
            'id'                         => $reference->id,
            'type'                       => $isChat ? 'chat' : 'video',
            'status'                     => $reference->status,
            'financial_status'           => $reference->financial_status,
            'consultation_price'         => $reference->consultation_price,
            'platform_commission_amount' => $reference->platform_commission_amount,
            'consultant_earning_amount'  => $reference->consultant_earning_amount,
            'started_at'                 => $reference->started_at?->toIso8601String(),
            'ended_at'                   => $reference->ended_at?->toIso8601String(),
            'created_at'                 => $reference->created_at?->toIso8601String(),
        ];
    }

    private function buildParty($party, ?string $type): ?array
    {
        if (!$party) {
            return null;
        }

        $result = [
            'id'        => $party->id,
            'full_name' => $party->full_name,
            'email' => $party->email,
            'phone'     => $party->phone ?? null,
        ];

        if ($type !== null) {
            $result['type'] = $type;
        }

        return $result;
    }

    private function buildChatEvidence($chat): array
    {
        $patientCount    = (int) ($chat->patient_message_count ?? 0);
        $consultantCount = (int) ($chat->consultant_message_count ?? 0);
        $firstPatient    = $chat->first_patient_message_at;
        $firstConsultant = $chat->first_consultant_message_at;

        $responseMinutes = null;
        if ($firstPatient && $firstConsultant) {
            $responseMinutes = (int) $firstPatient->diffInMinutes($firstConsultant);
        }

        return [
            'patient_message_count'       => $patientCount,
            'consultant_message_count'    => $consultantCount,
            'total_messages'              => $patientCount + $consultantCount,
            'first_patient_message_at'    => $firstPatient?->toIso8601String(),
            'first_consultant_message_at' => $firstConsultant?->toIso8601String(),
            'consultant_responded'        => $consultantCount > 0,
            'response_time_minutes'       => $responseMinutes,
        ];
    }

    private function buildVideoEvidence($video): array
    {
        $activities = $video->activities ?? collect();

        return [
            'zoom_meeting_id'           => $video->zoom_meeting_id,
            'video_room_link'           => $video->video_room_link,
            'duration_minutes'          => $video->duration_minutes,
            'total_participants_joined' => $activities->count(),
            'activities'                => $activities->map(fn ($a) => [
                'participant_uuid' => $a->participant_uuid,
                'joined_at'        => $a->joined_at?->toIso8601String(),
                'left_at'          => $a->left_at?->toIso8601String(),
                'duration_minutes' => $a->duration_minutes,
                'status'           => $a->status,
            ])->values()->toArray(),
//            'any_no_show' => $activities->contains('status', 'no_show'),
        ];
    }
}

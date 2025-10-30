<?php

namespace App\Services\api;


use App\Events\ConsultationRequested;
use App\Models\ConsultationVideoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ZoomMeetingService
{

    protected string $zoomApiBase = 'https://api.zoom.us/v2';
    protected string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * إنشاء رابط اجتماع زووم مُجدول بين مريض ومستشار
     *
     * @param \DateTime|string $startTime وقت البدء بالتوقيت UTC أو ISO 8601
     * @param int $duration مدة الجلسة بالدقائق
     * @return array                     بيانات الاجتماع (join_url, start_url, meeting_id, …)
     * @throws \Exception
     */
    public function createMeetingLinkZoom(\DateTime|string $dateTime, int $duration, ConsultationVideoRequest $consultation): array
    {
        // تحقق من أن التوكن موجود
        if (empty($this->accessToken)) {
            throw new \Exception("Zoom access token is missing.");
        }

        $hostUserId = config('services.zoom.host_email'); // البريد المرتبط بحساب Zoom المستضيف
        if (empty($hostUserId)) {
            throw new \Exception("Zoom host email is not configured.");
        }

        // تحويل الوقت إلى ISO 8601 للتأكد من صلاحيته
        try {
            $startTimeIso = Carbon::parse($dateTime)->toIso8601String();
        } catch (\Exception $e) {
            throw new \Exception("Invalid start time format: " . $e->getMessage());
        }

        $payload = [
            'topic' => 'Consultation Session',
            'type' => 2, // Scheduled meeting
            'start_time' => $startTimeIso,
            'duration' => $duration,
            'timezone' => 'UTC',
            'password' => null,
            'settings' => [
                'join_before_host' => true,
                'approval_type' => 0,
                'waiting_room' => false,
                'host_video' => false,
                'participant_video' => false,
                'mute_upon_entry' => true,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->post("{$this->zoomApiBase}/users/{$hostUserId}/meetings", $payload);

        // التحقق من الاستجابة
        if ($response->failed()) {
            $body = $response->body();
            $status = $response->status();
            throw new \Exception("Zoom API request failed with status $status: $body");
        }

        $data = $response->json();

        // التحقق من الحقول الأساسية
        foreach (['id', 'join_url', 'start_url', 'topic', 'start_time', 'duration'] as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Zoom API response missing field: $field");
            }
        }

        return [
            'meeting_id' => $data['id'],
            'join_url' => $data['join_url'],
            'start_url' => $data['start_url'],
            'topic' => $data['topic'],
            'start_time' => $data['start_time'],
            'duration' => $data['duration'],
        ];
    }



    /////////////////
    public function handleEvent(array $payload): void
    {
        $event = $payload['event'] ?? null;
        if (!$event) return;

        switch ($event) {
            case 'meeting.started':
                $this->handleMeetingStarted($payload);
                break;

            case 'meeting.participant_joined':
                $this->handleParticipantJoined($payload);
                break;

            case 'meeting.participant_left':
                $this->handleParticipantLeft($payload);
                break;
        }
    }

    protected function handleMeetingStarted(array $payload): void
    {
        $meetingId = $payload['payload']['object']['id'] ?? null;
        if (!$meetingId) return;

        $consultation = ConsultationVideoRequest::with(['patient', 'consultant'])
            ->where('zoom_meeting_id', $meetingId)
            ->first();

        if (!$consultation) return;

        $consultation->update(['status' => 'active']);

        $patientName = $consultation->patient->full_name ?? 'المريض';
        $consultantName = $consultation->consultant->full_name ?? 'المختص';

        event(new ConsultationRequested(
            $consultation,
            "تنبيه: جلسة الفيديو بدأت الآن بين {$patientName} و {$consultantName}",
            'reminder_for_all'
        ));
    }

    protected function handleParticipantJoined(array $payload): void
    {
        $consultation = ConsultationVideoRequest::with(['consultant', 'patient'])
            ->where('zoom_meeting_id', $payload['object']['id'] ?? null)
            ->first();

        if (!$consultation) return;

        $participantEmail = $payload['object']['participant']['user_email'] ?? null;
        if (!$participantEmail) return;

        $user = ($consultation->consultant->email === $participantEmail)
            ? ['id' => $consultation->consultant_id, 'role' => 'consultant']
            : ['id' => $consultation->patient_id, 'role' => 'patient'];

        $activity = $consultation->activities()->firstOrNew([
            'invitee_id' => $user['id'],
            'role'       => $user['role'],
        ]);

        $activity->joined_at = now();
        $activity->status = 'joined';
        $activity->joined_method = $payload['object']['join_method'] ?? null;
        $activity->ip_address = $payload['object']['ip_address'] ?? null;
        $activity->device = $payload['object']['device'] ?? null;
        $activity->data_center = $payload['object']['data_center'] ?? null;
        $activity->save();
    }

    protected function handleParticipantLeft(array $payload): void
    {
        $consultation = ConsultationVideoRequest::with(['consultant', 'patient'])
            ->where('zoom_meeting_id', $payload['object']['id'] ?? null)
            ->first();

        if (!$consultation) return;

        $participantEmail = $payload['object']['participant']['user_email'] ?? null;
        if (!$participantEmail) return;

        $user = ($consultation->consultant->email === $participantEmail)
            ? ['id' => $consultation->consultant_id, 'role' => 'consultant']
            : ['id' => $consultation->patient_id, 'role' => 'patient'];

        $activity = $consultation->activities()->firstOrNew([
            'invitee_id' => $user['id'],
            'role'       => $user['role'],
        ]);

        $activity->left_at = now();
        if ($activity->joined_at) {
            $activity->duration_minutes = Carbon::parse($activity->joined_at)->diffInMinutes(now());
        }
        $activity->status = 'left';
        $activity->save();
    }

}

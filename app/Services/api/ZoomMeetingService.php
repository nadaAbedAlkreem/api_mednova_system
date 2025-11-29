<?php

namespace App\Services\api;


use App\Events\ConsultationRequested;
use App\Models\ConsultationVideoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomMeetingService
{

    protected string $zoomApiBase = 'https://api.zoom.us/v2';
    protected string $accessToken;

//    public function __construct(string $accessToken)
//    {
//        $this->accessToken = $accessToken;
//    }
    /**
     * الحصول على توكن زووم صالح
     *
     * @return string
     * @throws \Exception
     */
    protected function getAccessToken(): string
    {
        // نحاول أخذ التوكن من الكاش أولاً
        $token = Cache::get('zoom_access_token');
        Log::info('access token 111: ' . $token);

        if ($token) {
            return $token;
        }
        Log::info('access token 111: ' . $token);

        // إذا لم يكن موجود أو انتهت صلاحيته، نولّد توكن جديد
        $response = Http::withBasicAuth(
            config('services.zoom.client_id'),
            config('services.zoom.client_secret')
        )->asForm()->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => config('services.zoom.account_id'),
        ]);
        Log::info('access $response: ' . json_encode($response));


        if ($response->failed()) {
            throw new \Exception("Failed to get Zoom access token: " . $response->body());
        }

        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] - 60; // نقص دقيقة للأمان
        Log::info('access $data: ' . json_encode($data));

        // تخزين التوكن في Cache
        Cache::put('zoom_access_token', $token, $expiresIn);

        return $token;
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
        $hostUserId = config('services.zoom.host_email');
        Log::info('access token $hostUserId: ' . $hostUserId);

        $accessToken = $this->getAccessToken();
        Log::info('access token zoom: ' . $accessToken);

        if (empty($hostUserId)) {
            Log::info('access token $hostUserId: empty');
            throw new \Exception("Zoom host email is not configured.");
        }

        try {
            Log::info('access token $start1: empty');
            $start = Carbon::parse($dateTime);
            Log::info('access token $start: empty');
            $startTimeIso = $start->toIso8601String();
            Log::info('access token $startTimeIso: empty');

            Log::info('access token $start:' . $startTimeIso);


        } catch (\Exception $e) {
            Log::info('access token format:' .  $e->getMessage());
            throw new \Exception("Invalid start time format: " . $e->getMessage());
        }

        $payload = [
            'topic' => 'Consultation Session',
            'type' => 2, // Scheduled meeting
            'start_time' => $startTimeIso,
            'duration' => $duration ?? 30,
            'timezone' => 'UTC',
            'password' => null,
            'settings' => [
                'join_before_host' => false,
                'approval_type' => 0,
                'waiting_room' => false,
                'host_video' => false,
                'participant_video' => false,
                'mute_upon_entry' => true,
                'authentication_option' => 'zoom_account', // أو 'sign_in_to_zoom'
                'authentication_domains' => '', // إذا بدك تحدد دومين معين

            ],
        ];
        Log::info('access token $response  888 : ' .json_encode($payload));

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json',
        ])->post("{$this->zoomApiBase}/users/{$hostUserId}/meetings", $payload);
        Log::info('access token $response  888 : ' .json_encode($response));

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

   public function endMeetingLinkZoom(ConsultationVideoRequest $consultation): array
   {
       try {
           $hostUserId = config('services.zoom.host_email');
           Log::info('access token $hostUserId end-zoom-nada88: ' . $hostUserId);

           $accessToken = $this->getAccessToken();
           Log::info('access token zoom: ' . $accessToken);

           if (empty($hostUserId)) {
               Log::info('access token $hostUserId: empty');
               throw new \Exception("Zoom host email is not configured.");
           }
           $zoomMeetingId = $consultation->zoom_meeting_id;
           Log::info('access token $zoomMeetingId: empty'. $zoomMeetingId);
           if(!$zoomMeetingId)
           {
               throw new \Exception("miss meeting id.");
           }
           $payload = [
               "action" => "end"
           ];
           $response = Http::withHeaders([
               'Authorization' => "Bearer {$accessToken}",
               'Content-Type' => 'application/json',
           ])->post("{$this->zoomApiBase}/meetings/{$zoomMeetingId}/status", $payload);

           // التحقق من الاستجابة
           if ($response->failed()) {
               $body = $response->body();
               $status = $response->status();
               throw new \Exception("Zoom API request failed with status $status: $body");
           }

//           $data = $response->json();
           $meetingData = $response->json();
           Log::info('meetingData end%%: ' . json_encode($meetingData));


           // 2️⃣ ننتظر قليلاً حتى يصبح التقرير جاهز
           sleep(10); // من 10 إلى 30 ثانية حسب سرعة زووم

           // 3️⃣ طلب تقرير المشاركين
           $reportResponse = Http::withHeaders([
               'Authorization' => "Bearer {$accessToken}",
           ])->get("{$this->zoomApiBase}/report/meetings/{$consultation->zoom_meeting_id}/participants");

           if ($reportResponse->failed()) {
               throw new \Exception("Failed getting meeting report: " . $reportResponse->body());
           }

           $participants = $reportResponse->json()['participants'] ?? [];
           Log::info('participants end%%: ' . json_encode($participants));

           // 4️⃣ إعادة البيانات
           return [
               'meeting' => [
                   'meeting_id' => $meetingData['id'],
                   'topic'      => $meetingData['topic'],
                   'start_time' => $meetingData['start_time'],
                   'duration'   => $meetingData['duration'],
               ],
               'participants_report' => $participants,
           ];
//           foreach (['id', 'join_url', 'start_url', 'topic', 'start_time', 'duration'] as $field) {
//               if (!isset($data[$field])) {
//                   throw new \Exception("Zoom API response missing field: $field");
//               }
//           }
//
//           return [
//               'meeting_id' => $data['id'],
//               'join_url' => $data['join_url'],
//               'start_url' => $data['start_url'],
//               'topic' => $data['topic'],
//               'start_time' => $data['start_time'],
//               'duration' => $data['duration'],
//           ];
       }catch (\Exception $exception){
           throw new \Exception("Zoom API request failed with status: " . $exception->getMessage());
       }


   }

    ///////////////
    public function handleEvent(array $payload): void
    {
        $event = $payload['event'] ?? null;
        Log::info('event: ' . $event);
        if (!$event) return;

        switch ($event) {
            case 'meeting.started':
                Log::info('started: ');
                $this->handleMeetingStarted($payload);
                break;

            case 'meeting.participant_joined':
                Log::info('joined: ');
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
        try {
            Log::info('zoom consultation payload: ' .  json_encode($payload)  );

            $consultation = ConsultationVideoRequest::with(['activities','consultant', 'patient'])
                ->where('zoom_meeting_id', $payload['payload']['object']['id'] ?? null)
                ->first();

            Log::info('zoom consultation test: ' . $payload['payload']['object']['id'] . 'maha');

            Log::info('participant: ' . json_encode(data_get($payload, 'payload.object.participant')));
//
//            if (!$consultation) return;
//            Log::info('zoom consultation:  exist');
//
            $participant = data_get($payload, 'payload.object.participant');
            Log::info('zoom consultation $participant test 44 : ' . $participant['user_id']);

            if(!$participant){
                Log::warning("Participant missing!");
                return;
            }

            Log::info("Participant Data: " . json_encode($participant));

            Log::info('zoom consultation:participantEmail');


//            $user = ($consultation->consultant->email === $participantEmail)
//                ? ['id' => $consultation->consultant_id, 'role' => 'consultant']
//                : ['id' => $consultation->patient_id, 'role' => 'patient'];
//            Log::info('zoom consultation:  $user' . json_encode($user) );
            $participantUuid = $payload['payload']['object']['participant']['participant_uuid'] ?? [];
            Log::info('zoom consultation:participantUuid' . json_encode($participantUuid));

            $activity = $consultation->activities()->firstOrNew([
                'consultation_video_request_id' => $consultation['id'],
                'participant_uuid' => $participantUuid ,
              ]);
            Log::info('zoom consultation:activity' . json_encode($activity) );



            $activity->joined_at  = $participant['join_time'] ?? now();
            $activity->status     = 'joined';
            $activity->ip_address = $participant['public_ip'] ?? null;
            $activity->device     = $participant['user_name'] ?? null; // device غير موجود، فلازم تشيلها أو تستبدلها
            $activity->joined_method = null; // غير موجود في Zoom
            $activity->data_center   = null; // غير موجود
            $activity->save();
        }catch (\Exception $exception){
            throw new \Exception("Zoom API request failed with status: " . $exception->getMessage());
        }


    }

    protected function handleParticipantLeft(array $payload): void
    {
        $consultation = ConsultationVideoRequest::with(['consultant', 'patient'])
            ->where('zoom_meeting_id', $payload['payload']['object']['id'] ?? null)
            ->first();
        Log::info('zoom consultation:left' . json_encode($consultation) );

        if (!$consultation) return;
        $participant = data_get($payload, 'payload.object.participant');
        if(!$participant){
            Log::warning("Participant missing!");
            return;
        }
        Log::info("Participant Data: " . json_encode($participant));
        Log::info('zoom consultation:  $participantEmail');
        // Zoom sometimes sends "email" instead of "user_email"
//        $participantEmail = $payload['payload']['object']['participant']['email']
//            ?? $payload['payload']['object']['participant']['user_email']
//            ?? null;
//
//        if (!$participantEmail) {
//            Log::warning('Zoom participant email missing (left event)', $payload);
//            return;
//        }
//
//        $user = ($consultation->consultant->email === $participantEmail)
//            ? ['id' => $consultation->consultant_id, 'role' => 'consultant']
//            : ['id' => $consultation->patient_id, 'role' => 'patient'];
        $participantUuid = $payload['payload']['object']['participant']['participant_uuid'] ?? [];
        Log::info('zoom consultation:participantUuid' . json_encode($participantUuid) );
        $activity = $consultation->activities()->firstOrNew([
            'consultation_video_request_id' => $consultation->id,
            'participant_uuid' => $participantUuid,
        ]);
        Log::info('zoom consultation:participantUuid' . json_encode($participantUuid) );

        $activity->left_at = now();
        if ($activity->joined_at) {
            $activity->duration_minutes = Carbon::parse($activity->joined_at)->diffInMinutes(now());
        }
        $activity->status = 'left';
        $activity->save();
    }
}

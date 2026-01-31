<?php

namespace App\Services\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Repositories\IAppointmentRequestRepositories;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\Api\Customer\TimezoneService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConsultantService
{
    protected IConsultationChatRequestRepositories $chatRepo;
    protected IConsultationVideoRequestRepositories $videoRepo;
    protected IAppointmentRequestRepositories $appointmentRepo;

    public function __construct(IAppointmentRequestRepositories $appointmentRepo, IConsultationChatRequestRepositories $chatRepo, IConsultationVideoRequestRepositories $videoRepo)
    {
        $this->chatRepo = $chatRepo;
        $this->videoRepo = $videoRepo;
        $this->appointmentRepo = $appointmentRepo;
    }

    public function createConsultationByType(array $data, string $type)
    {
        return DB::transaction(function () use ($data, $type) {

            if ($type === 'chat') {
                $consultation = $this->chatRepo->create($data);
                $consultation->load(['patient', 'consultant']);


            } elseif ($type === 'video') {
                if (isset($data['requested_time'])) {
                    $data['confirmed_end_time'] = Carbon::parse($data['requested_time'])->addMinutes(60);
                }

                $appointment = $this->appointmentRepo->create($data);
                $data['appointment_request_id'] = $appointment->id;
                $consultation = $this->videoRepo->create($data);
                $consultation->load(['patient', 'consultant', 'appointmentRequest']);
                $patientTimezone = $consultation->patient->timezone ?? config('app.timezone');
                $consultation->appointmentRequest->requested_time = TimezoneService::toUserTimezone(
                    $consultation->appointmentRequest->requested_time,
                    $patientTimezone,
                    'Y-m-d H:i'
                );
                $consultation->appointmentRequest->confirmed_end_time = TimezoneService::toUserTimezone(
                    $consultation->appointmentRequest->confirmed_end_time,
                    $patientTimezone,
                    'Y-m-d H:i'
                );
                $consultation->created_at = TimezoneService::toUserTimezone(
                    $consultation->created_at,
                    $patientTimezone,
                    'Y-m-d H:i'
                );

                $consultation->updated_at = TimezoneService::toUserTimezone(
                    $consultation->updated_at,
                    $patientTimezone,
                    'Y-m-d H:i'
                );
            } else {
                throw new Exception('Invalid consultation type');
            }

            $message = __('messages.new_consultation_notify', [
                'name' => $consultation->patient->full_name
            ]);
            event(new ConsultationRequested($consultation, $message, 'requested'));
            return $consultation;
        });
    }


    public function getAllConsultations(int $userId, string $userType, ?string $status = null, int $limit = 10): LengthAwarePaginator
    {
        // استعلامات كل نوع
        $chatQuery = ConsultationChatRequest::query();
        $videoQuery = ConsultationVideoRequest::query();

        // حسب نوع المستخدم
        if ($userType === 'patient') {
            $chatQuery->where('patient_id', $userId);
            $videoQuery->where('patient_id', $userId);
        } else {
            $chatQuery->where('consultant_id', $userId);
            $videoQuery->where('consultant_id', $userId);
        }

        // فلترة حسب الحالة إذا وجدت
        if (!empty($status)) {
            $chatQuery->where('status', $status);
            $videoQuery->where('status', $status);
        }

        // تحميل العلاقات
        $chatQuery->with(['patient', 'consultant']);
        $chatQuery->withCount(['unreadMessages']);
        $videoQuery->with(['patient', 'consultant', 'appointmentRequest']);

        // جلب البيانات
        $chats = $chatQuery->get()->map(function ($item) {
            $item->consultation_type = 'chat';
            return $item;
        });

        $videos = $videoQuery->get()->map(function ($item) use ($userId) {
            $item->consultation_type = 'video';
            $userTimezone = null;
            if ($item->consultant->id == $userId) {
                $userTimezone = $item->consultant->timezone ?? config('app.timezone');
            }
            if ($item->patient->id == $userId) {

                $userTimezone = $item->patient->timezone ?? config('app.timezone');
            }

            if ($userTimezone) {
                $item->appointmentRequest->requested_time = TimezoneService::toUserTimezone(
                    $item->appointmentRequest->requested_time,
                    $userTimezone,
                    'Y-m-d H:i'
                );

                $item->appointmentRequest->confirmed_end_time = TimezoneService::toUserTimezone(
                    $item->appointmentRequest->confirmed_end_time,
                    $userTimezone,
                    'Y-m-d H:i'
                );
                $item->created_at = TimezoneService::toUserTimezone(
                    $item->created_at,
                    $userTimezone,
                    'Y-m-d H:i'
                );
                $item->updated_at = TimezoneService::toUserTimezone(
                    $item->updated_at,
                    $userTimezone,
                    'Y-m-d H:i'
                );

            }

            return $item;
        });

        // دمج الاثنين
        $allConsultations = $chats->concat($videos)
            ->sortByDesc('created_at') // ترتيب حسب ID
            ->values(); // إعادة ترتيب المفاتيح

        // تحويل Collection إلى LengthAwarePaginator
        $page = request()->query('page', 1);
        $paginated = new LengthAwarePaginator(
            $allConsultations->forPage($page, $limit),
            $allConsultations->count(),
            $limit,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginated;
    }

    public function handleChatActivation(ConsultationChatRequest $consultation, array &$data): ?array
    {
        if (!$this->canActivateChat($consultation, $data)) {
            return null;
        }

        $data['status'] = 'active';
        $data['started_at'] = now();

        return $this->prepareNotificationData($consultation, $data);
    }

    private function canActivateChat(ConsultationChatRequest $consultation, array $data): bool
    {
        return $consultation->status === 'accepted'
            && (
                !is_null($data['first_patient_message_at']) ||
                !is_null($data['first_consultant_message_at'])
            );
    }

    private function prepareNotificationData(
        ConsultationChatRequest $consultation,
        array                   $data
    ): array
    {

        $patientName = $consultation->patient->name;
        $consultantName = $consultation->consultant->name;

        // أول رسالة من المريض → إشعار للدكتور
        if (!is_null($data['first_patient_message_at'])) {
            return [
                'message' => sprintf(
                    'الدكتور %s، أصبحت جلسة الاستشارة مع المريض %s نشطة الآن، يمكنك البدء بالتفاعل للاستفادة من الاستشارة.',
                    $consultantName,
                    $patientName
                ),
                'event_type' => 'active_by_patient',
            ];
        }

        // أول رسالة من الدكتور → إشعار للمريض
        return [
            'message' => sprintf(
                'عزيزي %s، أصبحت جلسة الاستشارة مع الدكتور %s نشطة الآن، يمكنك التفاعل والاستفادة من الاستشارة.',
                $patientName,
                $consultantName
            ),
            'event_type' => 'active_by_consultant',
        ];
    }


}




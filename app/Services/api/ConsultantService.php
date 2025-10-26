<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\AppointmentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\IAppointmentRequestRepositories;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Repositories\ICustomerRepositories;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConsultantService
{
    protected IConsultationChatRequestRepositories $chatRepo;
    protected IConsultationVideoRequestRepositories $videoRepo;
    protected IAppointmentRequestRepositories $appointmentRepo;

    public function __construct(IAppointmentRequestRepositories $appointmentRepo, IConsultationChatRequestRepositories $chatRepo, IConsultationVideoRequestRepositories $videoRepo) {
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
            }
            else {
                throw new Exception('Invalid consultation type');
            }
            $message = __('messages.new_consultation_notify', [
                'name' => $consultation->patient->full_name
            ]);

            event(new ConsultationRequested($consultation, $message, 'requested'));
            return $consultation;
        });
    }
    }

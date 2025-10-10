<?php

namespace App\Repositories\Eloquent;

use App\Models\ConsultationChatRequest;
use App\Repositories\IConsultationChatRequestRepositories;


class ConsultationChatRequestRepository  extends BaseRepository implements IConsultationChatRequestRepositories
{
    public function __construct()
    {
        $this->model = new ConsultationChatRequest();
    }
    public function getConsultationRequests($userId, $userType, $status = null, $limit = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = ConsultationChatRequest::query();

        if ($userType === 'patient') {
            $query->where('patient_id', $userId);
        } else {
            $query->where('consultant_id', $userId);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

         $query->with([
            'patient',
            'consultant',
//            'consultant.specialty' ,
//            'medicalSpecialties'
        ]);

        return $query->orderByDesc('id')->paginate($limit);
    }

}

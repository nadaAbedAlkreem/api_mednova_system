<?php

namespace App\Repositories\Eloquent;

use App\Models\ConsultationVideoRequest;
use App\Models\Customer;
use App\Repositories\IConsultationVideoRequestRepositories;


class ConsultationVideoRequestRepository  extends BaseRepository implements IConsultationVideoRequestRepositories
{
     public function __construct()
    {
        $this->model = new ConsultationVideoRequest();
    }
    public function checkPendingApprovals(): ?\Illuminate\Database\Eloquent\Collection
    {
        $user = auth('api')->user();
        if (!$user instanceof Customer) {
            return null;
        }
        $query = ConsultationVideoRequest::with(['consultant', 'patient', 'appointmentRequest'])
            ->where('status', 'end')
            ->where(function ($q) use ($user) {
                $q->where('patient_id', $user->id)
                    ->orWhere('consultant_id', $user->id);
            });
        if ($user->type === 'patient') {
            $query->where('patient_approved', null);
        }

        if (in_array($user->type, ['therapist', 'rehabilitation_center'])) {
            $query->where('consultant_approved', null);
        }

        return $query->get();

    }

}

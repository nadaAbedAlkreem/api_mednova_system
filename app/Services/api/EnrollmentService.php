<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\ProgramEnrollment;
use App\Models\Rating;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{

    public function handle($request)
    {
        $limit = $request->get('limit', config('app.pagination_limit')) ;

        $topEnrolled = ProgramEnrollment::with('program')
            ->select('program_id')
            ->selectRaw('COUNT(*) as enrollments_count')
            ->groupBy('program_id')
            ->orderByDesc('enrollments_count')
            ->paginate($limit);

        $data = $topEnrolled->map(function ($enrolled) {
            $program = $enrolled->program;
            $program->enrollments_count = $enrolled->enrollments_count;
            return $program;
        });
        return $data;

    }





}

<?php
namespace App\Services\Api\Program;


use App\Enums\ProgramStatus;
use App\Models\ProgramEnrollment;


class EnrollmentService
{

    public function handle($request)
    {
        $limit = $request->get('limit', config('app.pagination_limit')) ;

        $topEnrolled = ProgramEnrollment::query()
            ->whereHas('program', function ($query) {
                $query->where('status', ProgramStatus::Approved );
            })
            ->with([
                'program' => function ($query) {
                    $query
                        ->withAvg('ratings', 'rating')
                        ->withCount('ratings');
                }
            ])
            ->select('program_id')
            ->selectRaw('COUNT(*) as enrollments_count')
            ->groupBy('program_id')
            ->orderByDesc('enrollments_count')
            ->paginate($limit);

        $data = $topEnrolled->getCollection()->map(function ($enrolled) {
            $program = $enrolled->program;
            $program->enrollments_count = $enrolled->enrollments_count;
            return $program;
        });

        $topEnrolled->setCollection($data);

        return $topEnrolled;

    }





}

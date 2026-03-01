<?php

namespace App\Http\Controllers\Api\Program;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramEnrollmentRequest;
use App\Http\Requests\UpdateProgramEnrollmentRequest;
use App\Http\Resources\Api\Program\ProgramResource;
use App\Models\ProgramEnrollment;
use App\Services\Api\Program\EnrollmentService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ProgramEnrollmentController extends Controller
{
    use ResponseTrait;

    protected EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function getTopEnrolledProgram(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $reviewees = $this->enrollmentService->handle($request);
            return $this->successResponse('DATA_RETRIEVED_SUCCESSFULLY', ProgramResource::collection($reviewees) , 202);
        }catch (\Exception $exception){
            return $this->errorResponse('ERROR_OCCURRED', ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProgramEnrollmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProgramEnrollment $programEnrollment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProgramEnrollment $programEnrollment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramEnrollmentRequest $request, ProgramEnrollment $programEnrollment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgramEnrollment $programEnrollment)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\UpdateConsultationStatusRequest;
use App\Http\Requests\StoreConsultationVideoRequestRequest;
use App\Http\Requests\UpdateConsultationVideoRequestRequest;
use App\Models\ConsultationVideoRequest;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\api\ConsultantService;
use App\Services\api\ConsultationStatusService;
use App\Traits\ResponseTrait;

class ConsultationVideoRequestController extends Controller
{
    use ResponseTrait;
    protected ConsultationStatusService $statusService;
    protected ConsultantService $consultantService;
    protected IConsultationVideoRequestRepositories $consultationVideoRequestRepositories;

    public function __construct(ConsultantService $consultantService , IConsultationVideoRequestRepositories $consultationVideoRequestRepositories ,IConsultationChatRequestRepositories $consultationChatRequestRepositories ,ConsultationStatusService $statusService)
    {
        $this->statusService = $statusService;
        $this->consultationVideoRequestRepositories = $consultationVideoRequestRepositories;
        $this->consultantService = $consultantService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreConsultationVideoRequestRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsultationVideoRequest $consultationVideoRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ConsultationVideoRequest $consultationVideoRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatusRequest(UpdateConsultationStatusRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $consultation = $this->consultationVideoRequestRepositories->updateAndReturn($request->getData(), $request['id']);
            $consultation->load('appointmentRequest');
            $message = $this->statusService->handleStatusChange(
                $consultation,
                $request->status,
                'video',
                $request->action_by
            );

            return $this->successResponse($message, [], 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConsultationVideoRequest $consultationVideoRequest)
    {
        //
    }
}

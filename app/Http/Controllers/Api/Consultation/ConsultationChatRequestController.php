<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\StoreConsultationChatRequestRequest;
use App\Http\Requests\api\consultation\updateChattingRequest;
use App\Http\Requests\api\consultation\UpdateConsultationStatusRequest;
use App\Http\Requests\UpdateConsultationChatRequestRequest;
use App\Http\Resources\Api\Consultation\ConsultationChatRequestResource;
use App\Http\Resources\Api\Consultation\ConsultationResource;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\api\ConsultantService;
use App\Services\api\ConsultationStatusService;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class ConsultationChatRequestController extends Controller
{
    use ResponseTrait;
    protected ConsultationStatusService $statusService;
    protected ConsultantService $consultantService;
    protected IConsultationChatRequestRepositories $consultationChatRequestRepositories;
    protected IConsultationVideoRequestRepositories $consultationVideoRequestRepositories;

    public function __construct(ConsultantService $consultantService , IConsultationVideoRequestRepositories $consultationVideoRequestRepositories ,IConsultationChatRequestRepositories $consultationChatRequestRepositories ,ConsultationStatusService $statusService)
    {
        $this->consultationChatRequestRepositories = $consultationChatRequestRepositories;
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
    public function store(StoreConsultationChatRequestRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $type = $request['consultant_nature'];
            $consultation = $this->consultantService->createConsultationByType($request->getData(), $type);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ConsultationResource($consultation), 201,);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function getStatusRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            if(!$user instanceof Customer){
                throw new \Exception('Get Current User  Failed');
            }
            $status = $request->query('status');
            $limit = $request->query('limit', 10);
            $consultations = $this->consultationChatRequestRepositories->getConsultationRequests($user['id'], $user['type_account'], $status, $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ConsultationChatRequestResource::collection($consultations), 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }


    public function updateStatusRequest(UpdateConsultationStatusRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $consultation = $this->consultationChatRequestRepositories->updateAndReturn(
                $request->getData(),
                $request['id']
            );
            $message = $this->statusService->handleStatusChange(
                $consultation,
                $request->status
                ,'chat',
                $request->action_by
            );

            return $this->successResponse($message, [], 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function updateChatting(updateChattingRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
           $consultation = $this->consultationChatRequestRepositories->update($request->getData(), $request['chat_request_id']);
            return $this->successResponse(__('messages.UPDATE_CHATTING_INFO'));
        }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()]);
        }
    }





    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConsultationChatRequestRequest $request, ConsultationChatRequest $consultationChatRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConsultationChatRequest $consultationChatRequest)
    {
        //
    }
}

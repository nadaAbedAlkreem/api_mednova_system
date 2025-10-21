<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Events\ConsultationRequested;
use App\Http\Controllers\Controller;

use App\Http\Requests\api\consultation\StoreConsultationChatRequestRequest;
use App\Http\Requests\api\consultation\updateChattingRequest;
use App\Http\Requests\api\consultation\UpdateConsultationStatusRequest;
use App\Http\Requests\UpdateConsultationChatRequestRequest;
use App\Http\Resources\ConsultationChatRequestResource;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Services\api\ConsultationStatusService;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsultationChatRequestController extends Controller
{
    use ResponseTrait;
    protected ConsultationStatusService $statusService;

    protected IConsultationChatRequestRepositories $consultationChatRequestRepositories;

    public function __construct(IConsultationChatRequestRepositories $consultationChatRequestRepositories ,ConsultationStatusService $statusService)
    {
        $this->consultationChatRequestRepositories = $consultationChatRequestRepositories;
        $this->statusService = $statusService;

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
             $consultation = $this->consultationChatRequestRepositories->create($request->getData());
             $consultation->load(['patient','consultant']);
             $message = __('messages.new_consultation_notify', ['name' => $consultation->patient->full_name]);
             event(new ConsultationRequested($consultation , $message, 'requested'));
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ConsultationChatRequestResource($consultation), 201,);
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
                $request->status,
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
           $consultation = $this->consultationChatRequestRepositories->findOne($request['chat_request_id']);
           if($consultation->status == 'accepted' )
           {
               $consultation->status = 'active';
               $consultation->started_at  = now();
           }
            foreach (['first_patient_message_at', 'first_consultant_message_at', 'patient_message_count', 'consultant_message_count'] as $field) {
                if ($request->filled($field)) {
                    $consultation->$field = $request->$field;
                }
            }
            $consultation->save();

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

<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\checkConsultationStatusRequest;
use App\Http\Requests\api\consultation\StoreConsultationChatRequestRequest;
use App\Http\Requests\api\consultation\UpdateConsultationStatusRequest;
use App\Http\Requests\UpdateConsultationChatRequestRequest;
use App\Http\Resources\ConsultationChatRequestResource;
use App\Http\Resources\CustomerResource;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ConsultationChatRequestController extends Controller
{
    use ResponseTrait;

    protected IConsultationChatRequestRepositories $consultationChatRequestRepositories;

    public function __construct(IConsultationChatRequestRepositories $consultationChatRequestRepositories)
    {
        $this->consultationChatRequestRepositories = $consultationChatRequestRepositories;
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
            $consultation = $this->consultationChatRequestRepositories->create($request->validated());
            $consultation->load(['patient','consultant']);
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
            $this->consultationChatRequestRepositories->update($request->getData(), $request['id']);
            $message = $request->status === 'accepted' ? __('messages.ACCEPTED_REQUEST') : __('messages.CANCEL_REQUEST');
            return $this->successResponse($message, [], 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
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

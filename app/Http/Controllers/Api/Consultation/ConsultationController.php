<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\CheckDependenciesDataRequest;
use App\Http\Requests\api\consultation\StoreConsultationRequest;
use App\Http\Requests\api\consultation\UpdateConsultationStatusRequest;
use App\Http\Resources\Api\Consultation\ConsultationResource;
use App\Models\Customer;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\Api\Consultation\ConsultantService;
use App\Services\Api\Consultation\ConsultationStatusService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;


class ConsultationController extends Controller
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

    public function store(StoreConsultationRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
             $type = $request['consultant_nature'];
             $consultation = $this->consultantService->createConsultationByType($request->getData(), $type);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ConsultationResource($consultation), 201);
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
            $consultations = $this->consultantService->getAllConsultations($user['id'], $user['type_account'], $status, $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ConsultationResource::collection($consultations), 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function updateStatusRequest(UpdateConsultationStatusRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $consultantNature = $request->input('consultant_nature');
            $consultation = match($consultantNature) {
                'chat' => $this->consultationChatRequestRepositories->updateAndReturn($request->getData(),$request['id']),
                'video' => $this->consultationVideoRequestRepositories->updateAndReturn($request->getData(),$request['id']),
                default => throw new \Exception('Invalid consultation nature')
            };
            if ($consultantNature === 'video') {
                $consultation->load('appointmentRequest');
                if($request['status'] == 'cancelled' || $consultation->status == 'completed' || $consultation->status == 'approved')
                {
                  $consultation->appointmentRequest->update(['status'=>$request->status]);
                }
            }
            $message = $this->statusService->handleStatusChange(
                $consultation,
                $request->status,
                $consultantNature,
                $request->action_by
            );

            return $this->successResponse($message, [], 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function approvedConsultationBetweenCustomer(CheckDependenciesDataRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $typeAccount =($request['customer_type'] =='patient' )? 'patient_approved' : 'consultant_approved' ;
            $this->consultationVideoRequestRepositories->update([$typeAccount => $request['is_approved']] , $request['consultation_id']);
            return $this->successResponse(__('messages.approved_consultation_success'),[], 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
  public function hasPendingApprovedConsultations(): \Illuminate\Http\JsonResponse
  {
      try {
          $pending = $this->consultationVideoRequestRepositories->checkPendingApprovals();
          if ($pending) {return $this->successResponse('PENDING_APPROVAL',ConsultationResource::collection($pending));}
          return $this->successResponse('NO_PENDING_APPROVAL', []);
      }catch (\Exception $exception){
          return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
      }

  }




//    public function start($token): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
//    {
//        try{
//            // Find the activity by internal_token
//            $activity = ConsultationVideoActivity::with('consultationVideoRequest')->where('token', $token)->first();
//            if (!$activity) {throw new \Exception('Invalid Token');}
//            $consultation = $activity->consultationVideoRequest; // ensure relation exists
//            if (!$consultation || !$consultation->zoom_meeting_id) {
//                return response()->json(['message' => 'Meeting not available'], 400);
//            }
//            $videoLink = $activity->consultationVideoRequest->video_room_link ;
//            if (!$videoLink)
//            {throw new \Exception('Invalid Link');}
//            return redirect()->away($videoLink);
//
//        } catch (\Exception $e) {
//            Log::error('Registrant creation failed: ' . $e->getMessage(), ['activity_id' => $activity->id ?? null] );
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);        }
//    }

}

<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Enums\AccountStatus;
use App\Enums\CardType;
use App\Enums\ConsultantType;
use App\Enums\ConsultationStatus;
use App\Enums\ConsultationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Consultation\CheckDependenciesDataRequest;
use App\Http\Requests\Api\Consultation\ShowConsultationRequest;
use App\Http\Requests\Api\Consultation\StoreConsultationRequest;
use App\Http\Requests\Api\Consultation\UpdateConsultationStatusRequest;
use App\Http\Resources\Api\Consultation\ConsultationResource;
use App\Models\Customer;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\Api\Consultation\ConsultantService;
use App\Services\Api\Consultation\ConsultationStatusService;
use App\Services\Api\Payment\PaymentFeeCalculator;
use App\Traits\ResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ConsultationController extends Controller
{
    use ResponseTrait;
    use AuthorizesRequests;

    protected ConsultationStatusService $statusService;
    protected ConsultantService $consultantService;
    protected IConsultationChatRequestRepositories $consultationChatRequestRepositories;
    protected IConsultationVideoRequestRepositories $consultationVideoRequestRepositories;

    public function __construct( ConsultantService $consultantService, IConsultationVideoRequestRepositories $consultationVideoRequestRepositories, IConsultationChatRequestRepositories $consultationChatRequestRepositories, ConsultationStatusService $statusService)
    {
        $this->consultationChatRequestRepositories = $consultationChatRequestRepositories;
        $this->statusService = $statusService;
        $this->consultationVideoRequestRepositories = $consultationVideoRequestRepositories;
        $this->consultantService = $consultantService;
    }

    public function store(StoreConsultationRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $type = $request['consultant_nature'];
            $relation = ($request['consultant_type'] == ConsultantType::THERAPIST->value ) ? 'therapist' : 'rehabilitationCenter'  ;
            $price    = ($request['consultant_nature'] == ConsultationType::CHAT->value ) ? 'chat_consultation_price'  : 'video_consultation_price' ;
            $consultant = \App\Models\Customer::with($relation)->find($request['consultant_id']);
            $breakdown = PaymentFeeCalculator::calculateTotal(consultationPrice: $consultant->$relation->$price, cardType: CardType::DOMESTIC->value);
            $consultation = $this->consultantService->createConsultationByType($request->getData(), $type,$breakdown);
            DB::commit();
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ConsultationResource($consultation), 201);
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $e->getMessage(),
            ], 403);

        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
    public function show(ShowConsultationRequest $request, int $id): JsonResponse
    {
        try {
            $type = ConsultationType::from($request->validated('type'));
            $consultation = $this->consultantService->findConsultation($id, $type);
            $this->authorize('view', $consultation);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new ConsultationResource($consultation), 201);
        }catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }

    }
    public function getStatusRequest(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user instanceof Customer) {
                throw new \Exception('Get Current User  Failed');
            }
            $status = $request->query('status');
            $currentTimeZone = $request->query('current_time_zone');
            $limit = $request->query('limit', 10);
            $consultations = $this->consultantService->getAllConsultations($user['id'], $user['type_account'], $currentTimeZone, $status, $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ConsultationResource::collection($consultations), 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function updateStatusRequest(UpdateConsultationStatusRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $consultantNature = $request->input('consultant_nature');

            $consultation = match ($consultantNature) {
                'chat'  => $this->consultationChatRequestRepositories->updateAndReturn($request->getData(), $request['id']),
                'video' => $this->consultationVideoRequestRepositories->updateAndReturn($request->getData(), $request['id']),
                default => throw new \Exception('Invalid consultation nature'),
            };
            $message = $this->statusService->handleStatusChange(
                $consultation,
                $request->status,
                $consultantNature,
                $request->action_by
            );
            return $this->successResponse($message, [], 200);

        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
    public function approvedConsultationBetweenCustomer(CheckDependenciesDataRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $typeAccount = ($request['customer_type'] == 'patient') ? 'patient_approved' : 'consultant_approved';
            $this->consultationVideoRequestRepositories->update([$typeAccount => $request['is_approved']], $request['consultation_id']);
            return $this->successResponse(__('messages.approved_consultation_success'), [], 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function hasPendingApprovedConsultations(): \Illuminate\Http\JsonResponse
    {
        try {
            $pending = $this->consultationVideoRequestRepositories->checkPendingApprovals();
            if ($pending) {
                return $this->successResponse('PENDING_APPROVAL', ConsultationResource::collection($pending));
            }
            return $this->successResponse('NO_PENDING_APPROVAL', []);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }

    }






}

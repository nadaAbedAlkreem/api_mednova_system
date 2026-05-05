<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\consultation\StoreDisputeRequest;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IConsultationVideoRequestRepositories;
use App\Services\Api\Financial\Dispute\DisputeService;
use App\Traits\ResponseTrait;
use DomainException;
use Exception;

class DisputeController extends Controller
{
    use ResponseTrait;
    public function __construct(
        protected DisputeService $disputeService,
        protected IConsultationVideoRequestRepositories $consultationVideoRequestRepositories,
        protected IConsultationChatRequestRepositories $consultationChatRequestRepositories,
    ) {}

    public function openDispute(StoreDisputeRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->validated();
            $consultation = match ($data['type']) {
                'chat' =>  $this->consultationChatRequestRepositories->findOrFail($id),
                'video' => $this->consultationVideoRequestRepositories->findOrFail($id),
            };
            $this->disputeService->execute(
                consultation: $consultation,
                patient: $request->user('api'),
                reason: $data['reason_dispute']
            );
            return $this->successResponse(__('messages.DISPUTE_OPENED_SUCCESSFULLY'), [], 200);
        } catch (DomainException $exception) {
            return $this->errorResponse($exception->getMessage(), [], 422);

        } catch (Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}

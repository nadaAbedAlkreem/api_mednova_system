<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Financial\Transaction\ConsultantTransactionResource;
use App\Http\Resources\Api\Financial\Transaction\PatientTransactionResource;
use App\Services\Api\Financial\ConsultantFinancialService;
use App\Services\Api\Financial\PatientFinancialService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly PatientFinancialService $patientFinancialService ,
        private readonly ConsultantFinancialService $consultantFinancialService
    )
    {
    }


    public function consultantTransactions(Request $request): JsonResponse
    {
        try {
            $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],]);
            $consultant = $request->user('api');
            $perPage    = (int) $request->query('per_page', 15);
            $transactions = $this->consultantFinancialService->getTransactions($consultant, $perPage);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ConsultantTransactionResource::collection($transactions), 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    public function patientTransactions(Request $request): JsonResponse
    {
        try {
            $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],]);
            $patient = $request->user('api');
            $perPage = (int) $request->query('per_page', 15);
            $transactions = $this->patientFinancialService->getTransactions($patient, $perPage);
            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                PatientTransactionResource::collection($transactions),
                200
            );
        } catch (\Exception $exception) {
            return $this->errorResponse(
                __('messages.ERROR_OCCURRED'),
                ['error' => $exception->getMessage()],
                500
            );
        }
    }
}

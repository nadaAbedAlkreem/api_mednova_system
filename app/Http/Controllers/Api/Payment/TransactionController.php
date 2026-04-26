<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\Api\Financial\ConsultantTransactionResource;
use App\Models\Transaction;
use App\Services\Api\Financial\ConsultantFinancialService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly ConsultantFinancialService $financialService
    )
    {
    }


    public function consultantTransactions(Request $request): JsonResponse
    {
        try {
            $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],]);
            $consultant = $request->user('api');
            $perPage    = (int) $request->query('per_page', 15);
            $transactions = $this->financialService->getTransactions($consultant, $perPage);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ConsultantTransactionResource::collection($transactions), 200);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
}

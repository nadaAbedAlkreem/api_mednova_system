<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Financial\CancelWithdrawalRequest;
use App\Http\Requests\Api\Financial\StoreWithdrawalRequest;
use App\Http\Resources\Api\Financial\WithdrawalResource;
use App\Services\Api\Financial\Withdrawal\WithdrawalService;
use App\Traits\ResponseTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected WithdrawalService $withdrawalService,
    ) {}

    /**
     * Request withdrawal
     *
     * Submit a withdrawal request to transfer funds from the consultant's available balance
     * to their registered bank account. Requires a verified bank account.
     * Minimum and maximum amounts are configured in `config/financial.php`.
     *
     * @tags Financial — Withdrawals
     * @response 201 scenario="Requested" {"success":true,"message":"تم تقديم طلب السحب بنجاح","data":{"id":1,"amount":"10.000","currency":"OMR","status":"pending","status_label":"قيد المراجعة","bank_account":{"bank_name":"Bank Muscat","account_holder_name":"Ahmed Al-Balushi","account_number":"****5678","iban":"****123456"},"admin_note":null,"processed_at":null,"created_at":"2026-05-09T12:00:00+00:00"}}
     * @response 422 scenario="Insufficient Balance / No Bank Account" {"success":false,"message":"رصيدك غير كافٍ لإتمام السحب","data":[]}
     */
    public function store(StoreWithdrawalRequest $request): JsonResponse
    {
        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                $request->user('api'),
                (float) $request->validated()['amount']
            );

            return $this->successResponse(
                __('messages.WITHDRAWAL_REQUESTED_SUCCESSFULLY'),
                new WithdrawalResource($withdrawal->load('bankAccount')),
                201
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * List withdrawals
     *
     * Retrieve a paginated list of the authenticated patient and consultant's withdrawal requests,
     * ordered from newest to oldest.
     *
     * @tags Financial — Withdrawals
     * @queryParam per_page integer Number of results per page (max 50, default 15). Example: 15
     * @queryParam page integer Page number. Example: 1
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":[{"id":1,"amount":"10.000","currency":"OMR","status":"pending","status_label":"قيد المراجعة","bank_account":{"bank_name":"Bank Muscat","account_holder_name":"Ahmed Al-Balushi","account_number":"****5678","iban":"****123456"},"admin_note":null,"processed_at":null,"created_at":"2026-05-09T12:00:00+00:00"}],"pagination":{"current_page":1,"per_page":15,"total":1,"last_page":1,"has_more_pages":false}}
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage      = min((int) $request->query('per_page', 15), 50);
            $withdrawals  = $this->withdrawalService->getUserWithdrawals(
                $request->user('api'),
                $perPage
            );

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                WithdrawalResource::collection($withdrawals),
                200,
                [
                    'current_page'   => $withdrawals->currentPage(),
                    'per_page'       => $withdrawals->perPage(),
                    'total'          => $withdrawals->total(),
                    'last_page'      => $withdrawals->lastPage(),
                    'has_more_pages' => $withdrawals->hasMorePages(),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel withdrawal
     *
     * Cancel a pending withdrawal request. Only withdrawals with status **pending** can be cancelled.
     * The amount is returned to the consultant's available balance immediately.
     *
     * @tags Financial — Withdrawals
     * @response 200 scenario="Cancelled" {"success":true,"message":"تم إلغاء طلب السحب بنجاح","data":[]}
     * @response 422 scenario="Cannot Cancel" {"success":false,"message":"لا يمكن إلغاء هذا الطلب","data":[]}
     * @response 404 scenario="Not Found" {"success":false,"message":"الطلب غير موجود","data":[]}
     */
    public function cancel(CancelWithdrawalRequest $request, int $id): JsonResponse
    {
        try {
            $this->withdrawalService->cancelWithdrawal($request->user('api'), $id);

            return $this->successResponse(__('messages.WITHDRAWAL_CANCELLED_SUCCESSFULLY'), [], 200);
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}

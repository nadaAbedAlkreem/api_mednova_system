<?php

namespace App\Http\Controllers\Api\ControlPanel\FinancialDepartment;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ControlPanel\AdminEscrowResource;
use App\Http\Resources\Api\ControlPanel\AdminRevenueResource;
use App\Http\Resources\Api\ControlPanel\AdminTransactionResource;
use App\Services\Api\Financial\Admin\AdminDashboardService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFinancialController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected AdminDashboardService $dashboardService,
    )
    {
    }

    /**
     * Financial dashboard summary
     *
     * Returns a high-level snapshot of platform finances:
     * total revenue, active escrow balance, pending withdrawals, and dispute count.
     *
     * @tags Admin — Financial
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"total_revenue":"1500.000","total_escrow":"320.000","pending_withdrawals_count":4,"open_disputes_count":2,"currency":"OMR"}}
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $summary = $this->dashboardService->getSummary();
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), $summary, 200);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Platform revenue list
     *
     * Paginated list of completed consultations showing the platform commission earned per booking.
     * Optionally filter by month using `YYYY-MM` format.
     *
     * @tags Admin — Financial
     * @queryParam per_page integer Results per page (max 50, default 15). Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam month string Filter by month in YYYY-MM format. Example: 2026-05
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"data":[{"consultation_id":10,"patient_name":"Sara","consultant_name":"Dr. Ahmed","consultation_price":"20.000","platform_commission_amount":"4.000","net_to_consultant":"16.000","currency":"OMR","completed_at":"2026-05-08T09:00:00+00:00"}],"summary":{"total_revenue":"4.000","currency":"OMR"}},"pagination":{"current_page":1,"per_page":15,"total":1,"last_page":1,"has_more_pages":false}}
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            $perPage = min((int)$request->query('per_page', 15), 50);
            $month = $request->query('month') ?: null;

            $result = $this->dashboardService->getRevenue($perPage, $month);
            $paginator = $result['paginator'];

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                [
                    'data' => AdminRevenueResource::collection($paginator),
                    'summary' => $result['summary'],
                ],
                200,
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Escrow (held funds) list
     *
     * Lists consultations whose payment is currently held in platform escrow (pending balance).
     * Filter by `status`: `held` (awaiting completion), `review_window` (within dispute window), or `all`.
     *
     * @tags Admin — Financial
     * @queryParam per_page integer Results per page (max 50, default 15). Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam status string Filter by escrow status. Allowed: held, review_window, all. Example: held
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"data":[{"consultation_id":5,"patient_name":"Khalid","consultant_name":"Dr. Mona","amount":"25.000","escrow_status":"held","currency":"OMR","created_at":"2026-05-07T08:00:00+00:00"}],"summary":{"total_held":"25.000","currency":"OMR"}},"pagination":{"current_page":1,"per_page":15,"total":1,"last_page":1,"has_more_pages":false}}
     */
    public function escrow(Request $request): JsonResponse
    {
        try {
            $perPage = min((int)$request->query('per_page', 15), 50);
            $page = max((int)$request->query('page', 1), 1);
            $status = in_array($request->query('status'), ['held', 'review_window'])
                ? $request->query('status')
                : 'all';

            $result = $this->dashboardService->getEscrow($perPage, $page, $status);
            $paginator = $result['paginator'];

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                [
                    'data' => AdminEscrowResource::collection($paginator),
                    'summary' => $result['summary'],
                ],
                200,
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * All platform transactions
     *
     * Paginated ledger of all platform transactions. Filterable by transaction type and date range.
     * Transaction types: `payment_record`, `consultation_hold`, `consultation_credit`,
     * `platform_fee`, `refund`, `dispute_freeze`, `dispute_release`, `withdrawal`.
     *
     * @tags Admin — Financial
     * @queryParam per_page integer Results per page (max 50, default 15). Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam type string Filter by transaction type, or `all`. Example: platform_fee
     * @queryParam date_from string Filter from date (YYYY-MM-DD). Example: 2026-05-01
     * @queryParam date_to string Filter to date (YYYY-MM-DD). Example: 2026-05-31
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":[{"id":1,"type":"platform_fee","amount":"4.000","currency":"OMR","description":"Platform commission for consultation #10","created_at":"2026-05-08T09:05:00+00:00"}],"pagination":{"current_page":1,"per_page":15,"total":1,"last_page":1,"has_more_pages":false}}
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $perPage = min((int)$request->query('per_page', 15), 50);
            $dateFrom = $request->query('date_from') ?: null;
            $dateTo = $request->query('date_to') ?: null;

            $allowedTypes = array_map(fn($t) => $t->value, TransactionType::cases());
            $type = in_array($request->query('type'), $allowedTypes)
                ? $request->query('type')
                : 'all';

            $result = $this->dashboardService->getTransactions($perPage, $type, $dateFrom, $dateTo);
            $paginator = $result['paginator'];

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                AdminTransactionResource::collection($paginator),
                200,
                [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}

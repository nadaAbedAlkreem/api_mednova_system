<?php

namespace App\Http\Controllers\Api\ControlPanel\FinancialDepartment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ControlPanel\Financial\ProcessWithdrawalRequest;
use App\Http\Resources\Api\ControlPanel\AdminWithdrawalDetailResource;
use App\Http\Resources\Api\ControlPanel\AdminWithdrawalListResource;
use App\Models\WithdrawalRequest;
use App\Repositories\IWithdrawalRepositories;
use App\Services\Api\Financial\Withdrawal\AdminWithdrawalService;
use App\Traits\ResponseTrait;
use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AdminWithdrawalController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected AdminWithdrawalService $adminWithdrawalService,
        protected IWithdrawalRepositories $withdrawals,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $status  = $request->query('status', 'pending_review');
            $perPage = min((int) $request->query('per_page', 15), 50);

            $withdrawals = WithdrawalRequest::query()
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->with(['owner:id,full_name,type_account,email,phone', 'bankAccount'])
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                ['data' => AdminWithdrawalListResource::collection($withdrawals)],
                200,
                [
                    'current_page'   => $withdrawals->currentPage(),
                    'per_page'       => $withdrawals->perPage(),
                    'total'          => $withdrawals->total(),
                    'last_page'      => $withdrawals->lastPage(),
                    'has_more_pages' => $withdrawals->hasMorePages(),
                ]
            );
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $withdrawal = WithdrawalRequest::with([
                'owner:id,full_name,type_account,email,phone',
                'bankAccount',
                'wallet',
                'processedBy',
            ])->findOrFail($id);

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                new AdminWithdrawalDetailResource($withdrawal),
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function process(ProcessWithdrawalRequest $request, int $id): JsonResponse
    {
        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);
            $data       = $request->validated();

            if ($request->hasFile('transfer_proof')) {
                $data['transfer_proof'] = $request->file('transfer_proof');
            }

            $this->adminWithdrawalService->process(
                $withdrawal,
                $request->user('admin'),
                $data
            );

            $message = $data['action'] === 'approve'
                ? __('messages.WITHDRAWAL_APPROVED_SUCCESSFULLY')
                : __('messages.WITHDRAWAL_REJECTED_SUCCESSFULLY');

            return $this->successResponse($message, [], 200);

        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function downloadProof(int $id): Response|JsonResponse
    {
        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);

            if (!$withdrawal->transfer_proof_path) {
                return $this->errorResponse(__('messages.TRANSFER_PROOF_NOT_FOUND'), [], 404);
            }

            $fullPath = Storage::disk('local')->path($withdrawal->transfer_proof_path);

            if (!file_exists($fullPath)) {
                return $this->errorResponse(__('messages.TRANSFER_PROOF_NOT_FOUND'), [], 404);
            }

            return response()->file($fullPath);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}

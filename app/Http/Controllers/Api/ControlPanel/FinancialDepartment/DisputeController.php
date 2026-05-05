<?php

namespace App\Http\Controllers\Api\ControlPanel\FinancialDepartment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ControlPanel\DisputeDetailResource;
use App\Models\Dispute;
use App\Services\Api\Financial\Dispute\DisputeResolutionService;
use App\Traits\ResponseTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DisputeController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected DisputeResolutionService $resolutionService,
    ) {}

    public function show(int $id): JsonResponse
    {
        try {
            $dispute = Dispute::with([
                'reference.patient:id,full_name,email,phone',
                'reference.consultant:id,full_name,email,phone',
                'openedBy:id,full_name',
            ])->findOrFail($id);
            if ($dispute->reference instanceof \App\Models\ConsultationVideoRequest) {$dispute->reference->load('activities');}
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new DisputeDetailResource($dispute), 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'resolution' => 'required|in:refund,release',
                'admin_note' => 'nullable|string|max:1000',
            ]);
            $dispute = Dispute::findOrFail($id);
            $admin   = Auth::guard('admin')->user();
            $note    = $validated['admin_note'] ?? null;
            match ($validated['resolution'])
            {
                'refund'  => $this->resolutionService->resolveForPatient($dispute, $admin, $note),
                'release' => $this->resolutionService->resolveForConsultant($dispute, $admin, $note),
            };
            return $this->successResponse(__('messages.DISPUTE_RESOLVED_SUCCESSFULLY'), [], 200);
        } catch (ValidationException $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), $e->errors(), 422);
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}

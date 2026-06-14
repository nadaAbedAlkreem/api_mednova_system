<?php

namespace App\Http\Controllers\Api\ControlPanel\FinancialDepartment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ControlPanel\Financial\ResolveDisputeRequest;
use App\Http\Resources\Api\ControlPanel\AdminDisputeListResource;
use App\Http\Resources\Api\ControlPanel\DisputeDetailResource;
use App\Models\ConsultationVideoRequest;
use App\Models\Dispute;
use App\Services\Api\Financial\Dispute\DisputeResolutionService;
use App\Traits\ResponseTrait;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    use ResponseTrait;

    public function __construct(
        protected DisputeResolutionService $resolutionService,
    ) {}

    /**
     * List disputes
     *
     * Paginated list of all patient-initiated disputes across the platform.
     * Filterable by status: `opened`, `under_review`, `resolved`, or `all`.
     * Includes a summary of total frozen amount and open/resolved counts.
     *
     * @tags Admin — Disputes
     * @queryParam per_page integer Results per page (max 50, default 15). Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam status string Filter by status. Allowed: opened, under_review, resolved, all. Example: opened
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"data":[{"id":1,"status":"opened","amount":"20.000","currency":"OMR","patient_name":"Sara","consultant_name":"Dr. Ahmed","opened_at":"2026-05-09T10:00:00+00:00"}],"summary":{"total_frozen_amount":"20.000","total_open":1,"total_resolved":3,"currency":"OMR"}},"pagination":{"current_page":1,"per_page":15,"total":1,"last_page":1,"has_more_pages":false}}
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->query('per_page', 15), 50);
            $status  = in_array($request->query('status'), ['opened', 'under_review', 'resolved'])
                ? $request->query('status')
                : 'all';

            $disputes = Dispute::query()
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->with([
                    'reference.patient:id,full_name',
                    'reference.consultant:id,full_name',
                    'openedBy:id,full_name',
                    'reference' => function ($morphTo) {
                        $morphTo->morphWith([
                            ConsultationVideoRequest::class => ['activities'],
                        ]);
                    },
                ])
                ->orderByDesc('opened_at')
                ->paginate($perPage);
//
//            $disputes->each(function ($dispute) {
//                if ($dispute->reference instanceof ConsultationVideoRequest) {
//                    $dispute->reference->loadMissing('activities');
//                }
//            });

            $summary = [
                'total_frozen_amount' => number_format(
                    (float) Dispute::whereIn('status', ['opened', 'under_review'])->sum('amount'),
                    3, '.', ''
                ),
                'total_open'     => Dispute::where('status', 'opened')->count(),
                'total_resolved' => Dispute::where('status', 'resolved')->count(),
                'currency'       => 'OMR',
            ];

            return $this->successResponse(
                __('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                [
                    'data'    => AdminDisputeListResource::collection($disputes),
                    'summary' => $summary,
                ],
                200,
                [
                    'current_page'   => $disputes->currentPage(),
                    'per_page'       => $disputes->perPage(),
                    'total'          => $disputes->total(),
                    'last_page'      => $disputes->lastPage(),
                    'has_more_pages' => $disputes->hasMorePages(),
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get dispute details
     *
     * Returns full details of a single dispute including patient/consultant info,
     * the related consultation, and all timeline activities.
     *
     * @tags Admin — Disputes
     * @response 200 scenario="Success" {"success":true,"message":"تم جلب البيانات بنجاح","data":{"id":1,"status":"opened","amount":"20.000","currency":"OMR","patient":{"id":5,"full_name":"Sara","email":"sara@example.com","phone":"+968 91234567"},"consultant":{"id":3,"full_name":"Dr. Ahmed","email":"ahmed@example.com","phone":"+968 98765432"},"admin_note":null,"opened_at":"2026-05-09T10:00:00+00:00","resolved_at":null}}
     * @response 404 scenario="Not Found" {"success":false,"message":"حدث خطأ","data":[]}
     */
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

    /**
     * Resolve dispute
     *
     * Admin resolves a dispute by choosing `refund` (patient wins) or `release` (consultant wins).
     * - `refund`: returns `consultation_price` from platform pending balance → patient available balance.
     * - `release`: moves `consultation_price` from frozen → consultant available balance.
     * An optional `admin_note` is stored and shown to the losing party.
     *
     * @tags Admin — Disputes
     * @response 200 scenario="Resolved" {"success":true,"message":"تم حل النزاع بنجاح","data":[]}
     * @response 422 scenario="Validation / Domain Error" {"success":false,"message":"حدث خطأ","data":{"resolution":"required"}}
     * @response 404 scenario="Dispute Not Found" {"success":false,"message":"حدث خطأ","data":[]}
     */
    public function resolve(ResolveDisputeRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $dispute   = Dispute::findOrFail($id);
            $admin     = Auth::guard('admin')->user();
            $note      = $validated['admin_note'] ?? null;
            match ($validated['resolution'])
            {
                'refund'  => $this->resolutionService->resolveForPatient($dispute, $admin, $note),
                'release' => $this->resolutionService->resolveForConsultant($dispute, $admin, $note),
            };
            return $this->successResponse(__('messages.DISPUTE_RESOLVED_SUCCESSFULLY'), [], 200);
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [], 404);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
}

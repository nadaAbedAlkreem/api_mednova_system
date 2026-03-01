<?php

namespace App\Http\Controllers\Api\ControlPanel\UserDepartment;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Repositories\ICustomerRepositories;
use App\Services\Api\Customer\CustomerService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ResponseTrait;

    protected ICustomerRepositories $customerRepositories;
    protected CustomerService $customerService;


    public function __construct(ICustomerRepositories $customerRepositories, CustomerService $customerService)
    {
        $this->customerRepositories = $customerRepositories;
        $this->customerService = $customerService;
    }

    public function getAll(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = $request->query('limit') ?? 10;
            $filters = $request->only(['search', 'type_account', 'approval_status', 'verified']);
            $customers = $this->customerService->getAll($filters, $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), CustomerResource::collection($customers)  , 202 ,  [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'has_more_pages' => $customers->hasMorePages()]);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function getById($id): \Illuminate\Http\JsonResponse
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                return $this->errorResponse(__('messages.INVALID_ID'), [], 422);
            }
            $customer = $this->customerRepositories->findWith($id, ['location', 'patient', 'therapist', 'therapist.specialty', 'rehabilitationCenter', 'medicalSpecialties', 'schedules']);
            if (!$customer instanceof Customer) {
                throw new \Exception('Get Customer Failed');
            }
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new CustomerResource($customer), 201);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

//    public function toggleBlock($id): \Illuminate\Http\JsonResponse
//    {
//        try {
//            $customer = $this->customerRepositories->findOne($id);
//            if (!$customer) {
//                return $this->errorResponse(__('messages.CUSTOMER_NOT_FOUND'), [], 404);
//            }
//            $customer->is_banned = !$customer->is_banned;
//            $customer->save();
//            return $this->successResponse(
//                $customer->is_banned ? __('messages.CUSTOMER_BLOCKED') : __('messages.CUSTOMER_UNBLOCKED'), ['is_banned' => $customer->is_banned],
//                200
//            );
//        } catch (\Exception $e) {
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
//        }
//    }

    public function updateApprovalStatus(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'approval_status' => 'required|in:pending,approved,rejected',
                'reason' => 'required_if:approval_status,rejected|nullable|string|max:1000',
            ], [
                    'approval_status.required' => 'يرجى اختيار حالة الطلب.',
                    'approval_status.in' => 'حالة الطلب غير صحيحة.',
                    'reason.required_if' => 'يرجى إدخال سبب الرفض قبل المتابعة.',
                    'reason.max' => 'سبب الرفض يجب ألا يتجاوز 1000 حرف.',
                ]);
            $statusEnum = StatusType::from($request->input('approval_status'));
            $customer = $this->customerRepositories->findWith($id , ['patient' , 'rehabilitationCenter' , 'therapist']);
            if (!$customer) {
                return $this->errorResponse(__('messages.CUSTOMER_NOT_FOUND'), [], 404);
            }
            $updatedCustomer = $this->customerService->updateApprovalStatus($customer, $statusEnum, $request->input('reason') ?? ' ');
            return $this->successResponse(__('messages.UPDATE_STATUS_USER_ACTIVE'), ['approval_status' => $updatedCustomer->approval_status], 200);
        } catch (ValidationException $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
    public function updateAccountStatus(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'account_status' => 'required|in:active,suspended,inactive,deleted,under_review',
                'reason' => 'required_if:account_status,suspended|nullable|string|max:1000',

            ], [
                'account_status.required' => 'يرجى اختيار حالة الحساب .',
                'account_status.in' => 'حالة الحساب غير صحيحة.',
                'reason.required_if' => 'يرجى إدخال سبب تعليق قبل المتابعة.',
                'reason.max' => 'سبب الرفض يجب ألا يتجاوز 1000 حرف.',
            ]);
            $statusEnum =AccountStatus::from($request->input('account_status'));
            $customer = $this->customerRepositories->findOne($id);
            if (!$customer) {
                return $this->errorResponse(__('messages.CUSTOMER_NOT_FOUND'), [], 404);
            }
            $updatedCustomer = $this->customerService->updateAccountStatus($customer, $statusEnum, $request->input('reason') ?? ' ');
            return $this->successResponse(__('messages.UPDATE_STATUS_USER_ACTIVE'), ['approval_status' => $updatedCustomer->account_status], 200);
        } catch (ValidationException $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            $customer = $this->customerRepositories->findOne($id);
            if (!$customer) {
                return $this->errorResponse(__('messages.CUSTOMER_NOT_FOUND'), [], 404);
            }
            if ($customer->receivedConsultations()->exists() ||
                $customer->consultationRequests()->exists() ||
                $customer->consultationVideoRequests()->exists() ||
                $customer->consultationVideoRequestsForConsultant()->exists() ||
                $customer->appointmentRequests()->exists() ||
                $customer->schedules()->exists() ||
                $customer->userPackages()->exists() ||
                $customer->programEnrollments()->exists() ||
                $customer->accountReviews()->exists() ||
                $customer->complainantReport()->exists() ||
                $customer->reported()->exists()) {
                return $this->errorResponse(
                    __('messages.CUSTOMER_CANNOT_BE_DELETED_HAS_ACTIVE_RELATIONS'),
                    [],
                    403
                );
            }
            $customer->delete();
            return $this->successResponse(__('messages.CUSTOMER_DELETED_SUCCESSFULLY'), [], 200);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

}

<?php

namespace App\Http\Controllers\Api\ControlPanel\UserDepartment;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Events\TemporaryPackageAssigned;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ControlPanel\Subscribtion\UserPackageResource;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Customer;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IUserPackageRepositories;
use App\Services\Api\Customer\CustomerService;
use App\Services\Api\Package\PackageService;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ResponseTrait;

    protected ICustomerRepositories $customerRepositories;
    protected IUserPackageRepositories $userPackageRepositories;
    protected CustomerService $customerService;
    protected PackageService $packageService;


    public function __construct(PackageService $packageService ,ICustomerRepositories $customerRepositories, CustomerService $customerService , IUserPackageRepositories $userPackageRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->customerService = $customerService;
        $this->userPackageRepositories = $userPackageRepositories;
        $this->packageService = $packageService;
    }

    public function getAll(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = $request->query('limit') ?? 10;
            $filters = $request->only(['search', 'type_account', 'approval_status', 'verified' , 'account_status']);
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



    public function updateApprovalStatus(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
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
             DB::commit();
            return $this->successResponse(__('messages.UPDATE_STATUS_USER_ACTIVE'), ['approval_status' => $updatedCustomer->approval_status], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }
    public function updateAccountStatus(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
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
            DB::commit();
            return $this->successResponse(__('messages.UPDATE_STATUS_USER_ACTIVE'), ['approval_status' => $updatedCustomer->account_status], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function assignTemporaryPackage(int $userId): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $userPackage = $this->packageService->assignTemporaryPackage($userId);
            DB::commit();
            return $this->successResponse(__('messages.Successful_Subscriber'), new UserPackageResource($userPackage), 200);
        } catch (ModelNotFoundException) {
            DB::rollBack();return $this->errorResponse(__('messages.USER_NOT_FOUND'), [], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }


//    function assignTemporaryPackage($userId): \Illuminate\Http\JsonResponse
//    {
//        try {
//            db::beginTransaction();
//            $now = Carbon::now();
//            $endsAt = $now->copy()->addMonth();
//            $customer = $this->customerRepositories->findOrFail($userId);
//            if (!$customer->isProfileCompleted() || !$customer->email_verified_at || !($customer->approval_status == StatusType::APPROVED->value)) {
//                throw new \Exception(__('messages.Subscription_Restrictions'));
//            }
//            $preSubscribedConsultant  = $this->userPackageRepositories->getWhere(['is_active' =>  1 , 'customer_id' =>$userId ]);
//            if ($preSubscribedConsultant->isNotEmpty()) {
//                throw new \Exception(__('messages.Pre_Subscription_Restrictions'));
//            }
//            $userPackage = $this->userPackageRepositories->create([
//                    'customer_id' => $userId,
//                    'package_id' => 1,
//                    'starts_at' => $now,
//                    'ends_at' => $endsAt,
//                    'is_active' => 1,
//                ]);
//            $userPackage->customer->update(['account_status' => AccountStatus::ACTIVE->value]);
//            $url = url("https://mednovacare.com/profile");
//            event(new TemporaryPackageAssigned($customer, $url));
//            db::commit();
//            return $this->successResponse(__('messages.Successful_Subscriber'), new UserPackageResource($userPackage), 200);
//        }catch (ModelNotFoundException $e) {
//            db::rollBack();
//            return $this->errorResponse(__('messages.USER_NOT_FOUND'), [], 404);
//        } catch (\Exception $e) {
//            db::rollBack();
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
//        }
//    }
//    public function subscribedUsers(): \Illuminate\Http\JsonResponse
//    {
//        try {
//            $today = Carbon::now();
//            $subscribedUsers = Customer::whereHas('userPackages', function($query) use ($today) {
//                $query->where('is_active', 1)
//                    ->where('starts_at', '<=', $today)
//                    ->where('ends_at', '>=', $today);
//            })->get();
//            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), CustomerResource::collection($subscribedUsers), 201);
//        } catch (\Exception $e) {
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
//        }
//    }
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

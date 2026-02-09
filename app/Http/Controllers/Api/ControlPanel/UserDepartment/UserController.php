<?php

namespace App\Http\Controllers\Api\ControlPanel\UserDepartment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Models\Customer;
use App\Repositories\ICustomerRepositories;
use App\Services\Api\Customer\CustomerService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ResponseTrait;

    protected  ICustomerRepositories $customerRepositories;
    protected  CustomerService $customerService;


    public function __construct(ICustomerRepositories $customerRepositories  , CustomerService $customerService)
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
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),
                CustomerResource::collection($customers),
                202);
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
            $customer = $this->customerRepositories->findWith($id , ['location','patient','therapist' ,'therapist.specialty','rehabilitationCenter' ,'medicalSpecialties','schedules']);
            if(!$customer instanceof  Customer){
                throw new \Exception('Get Customer Failed');
            }
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new CustomerResource($customer), 201);
        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    public function toggleBlock($id): \Illuminate\Http\JsonResponse
    {
        try {
            $customer = $this->customerRepositories->findOne($id);
            if(!$customer) {
                return $this->errorResponse(__('messages.CUSTOMER_NOT_FOUND'), [], 404);
            }
            $customer->is_banned = !$customer->is_banned;
            $customer->save();
            return $this->successResponse(
                $customer->is_banned ? __('messages.CUSTOMER_BLOCKED') : __('messages.CUSTOMER_UNBLOCKED'), ['is_banned' => $customer->is_banned],
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }


}

<?php

namespace App\Http\Controllers\Api\ControlPanel\UserDepartment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Repositories\ICustomerRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ResponseTrait;

    protected  ICustomerRepositories $customerRepositories;


    public function __construct(ICustomerRepositories $customerRepositories )
    {
        $this->customerRepositories = $customerRepositories;
    }

    public function getAll(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = $request->query('limit') ?? 10;
            $customers = $this->customerRepositories->paginate($limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new CustomerResource($customers), 202);
        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }

    }


//    public function getById($id): \Illuminate\Http\JsonResponse
//    {
//        try {
//            $customer = $this->customerRepositories->findWith($id , ['location','patient','therapist' ,'therapist.specialty','rehabilitationCenter' ,'medicalSpecialties','schedules']);
//            if(!$customer instanceof  Customer){
//                throw new \Exception('Get Customer Failed');
//            }
//            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new CustomerResource($customer), 201);
//        }catch (\Exception $e){
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
//        }
//
//    }


}

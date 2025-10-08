<?php

namespace App\Http\Controllers\Api\Auth;

use App;
use App\Events\CustomerRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\auth\RegisterUserRequest;
use App\Http\Resources\CustomerResource;
use App\Services\api\CustomerService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{

    use ResponseTrait ;
     protected CustomerService $customerService ;

    public function __construct(CustomerService $customerService)
    {
         $this->customerService = $customerService;
    }


    public function register(RegisterUserRequest $request): JsonResponse
    {
        try {
            $response = $this->customerService->register($request->getData());
            if(!$response['customer'] instanceof App\Models\Customer){
                throw new \Exception('User Registration Failed');
            }
            return $this->successResponse('CREATE_USER_SUCCESSFULLY',
               ['access_token' =>  $response['access_token'], 'user' => new CustomerResource($response['customer']),], 201, app()->getLocale());
        } catch (\Exception $e) {
             return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
        }
     }
}

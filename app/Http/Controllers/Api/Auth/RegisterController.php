<?php

namespace App\Http\Controllers\Api\Auth;

use App;
use App\Events\CustomerRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\auth\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Services\api\CustomerService;
use App\Traits\ResponseTrait;

class RegisterController extends Controller
{

    use ResponseTrait ;
     protected $customerService ;

    public function __construct(CustomerService $customerService)
    {
         $this->customerService = $customerService;
    }


    public function register(RegisterUserRequest $request)
    {
        try {
            $user = $this->customerService->register($request->getData());
            event(new CustomerRegistered($user['user']));
            return $this->successResponse('CREATE_USER_SUCCESSFULLY',
               [
                   'access_token' => $user['access_token'],
                   'token_type' => 'Bearer',
                   'user' => new UserResource($user['user']),
               ], 201, app()->getLocale());
        } catch (\Exception $e) {
             return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
        }
     }
}

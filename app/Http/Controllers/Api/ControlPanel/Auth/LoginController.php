<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\auth\LoginUserRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Services\Auth\CustomerAuthService;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ResponseTrait ;
    protected CustomerAuthService $authService;
    public function __construct(CustomerAuthService $authService)
    {
        $this->authService = $authService;
    }


    public function login(LoginUserRequest $request): JsonResponse
    {
           try {
              $credentials = $request->only('email', 'password');
              $token = $this->authService->login($credentials);
              $customer = Auth::guard('api')->user();
              if(!$customer['email_verified_at'])
              {
                  throw new \Exception(__('messages.EMAIL_NOT_VERIFIED'));
              }
              $customer->load(['location','patient','therapist' ,'therapist.specialty','rehabilitationCenter' ,'medicalSpecialties','schedules']);
              return $this->successResponse('LOGGED_IN_SUCCESSFULLY',
                   [
                   'access_token' =>'Bearer ' . $token,
                   'user' => new CustomerResource($customer),
               ], 202,app()->getLocale());
          } catch (\Exception $e) {
              return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());
          }
    }


    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(__('messages.LOGGED_OUT_SUCCESSFULLY') ,[] ,200, app()->getLocale());
    }
}

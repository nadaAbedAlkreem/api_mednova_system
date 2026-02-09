<?php

namespace App\Http\Controllers\Api\ControlPanel\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\controlPanel\Auth\LoginUserRequest;
use App\Http\Resources\Api\Customer\AdminResource;
use App\Repositories\IAdminRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ResponseTrait ;
    protected  IAdminRepositories $adminRepo;
    public function __construct(IAdminRepositories $adminRepo)
    {
        $this->adminRepo = $adminRepo;
    }


    public function login(LoginUserRequest $request): JsonResponse
    {
           try {
              $credentials = $request->only('email', 'password');
              $token = $this->adminRepo->login($credentials);
              $admin = Auth::guard('admin')->user();
              return $this->successResponse('LOGGED_IN_SUCCESSFULLY',
                   [
                   'access_token' =>'Bearer ' . $token,
                   'admin' => new  AdminResource($admin),
               ], 202,app()->getLocale());
          } catch (\Exception $e) {
              return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500, app()->getLocale());
          }
    }


    public function logout(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $admin->currentAccessToken()->delete();
        return $this->successResponse(__('messages.LOGGED_OUT_SUCCESSFULLY') ,[] ,200, app()->getLocale());
    }
}

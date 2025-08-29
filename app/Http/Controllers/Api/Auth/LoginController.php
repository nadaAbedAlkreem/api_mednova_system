<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AdminAuthService;
use App\Services\CustomerAuthService;
use App\Traits\ResponseTrait;
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

    public function index()
    {
    return view('auth.login');
    }


    public function login(LoginRequest $request)
    {
          try {
              $credentials = $request->only('email', 'password');
              $token = $this->authService->login($credentials);
               return $this->successResponse('LOGGED_IN_SUCCESSFULLY',
                   [
                   'access_token' => $token,
                   'token_type' => 'Bearer',
                   'user' => new UserResource(Auth::guard('api')->user()),
               ], 202,app()->getLocale());
          } catch (\Exception $e) {
              return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
          }
    }


    public function logout()
    {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'You have been logged out.');

    }
}

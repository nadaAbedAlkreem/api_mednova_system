<?php

namespace App\Http\Controllers\Dashborad\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AdminAuthService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ResponseTrait ;
    protected AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
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
              $this->authService->login($credentials);
               return $this->successResponse('LOGGED_IN_SUCCESSFULLY', [], 200, app()->getLocale());
          } catch (\Exception $e) {
              return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
          }
    }


    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'You have been logged out.');

    }
}

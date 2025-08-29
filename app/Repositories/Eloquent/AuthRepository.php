<?php

namespace App\Repositories\Eloquent;


use App\Models\Customer;
use App\Repositories\IAuthRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


abstract class AuthRepository  extends BaseRepository implements IAuthRepositories
{
    use ResponseTrait ;
    protected string $guard;
    public function login(array $credentials): ?string
    {
        if ($this->guard === 'web') {
            if (!Auth::guard($this->guard)->attempt($credentials)) {
                return false;
            }
         }
        if ($this->guard === 'api') {
             $user = Customer::where('email', $credentials['email'])->first();
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return false;
            }
            Auth::guard($this->guard)->setUser($user);
        }
             return $this->afterLogin(Auth::guard($this->guard)->user());
    }
    abstract protected function afterLogin($user): ?string;
}

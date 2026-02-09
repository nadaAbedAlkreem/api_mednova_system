<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Repositories\IAdminRepositories;
use App\Repositories\IAuthRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class AdminRepository extends AuthRepository implements IAdminRepositories
{
    use ResponseTrait ;
    protected string $guard = 'admin';
    public function __construct()
    {
    $this->model = new Admin();
    }
    protected function afterLogin($user): ?string
    {
         return $user->createToken('api-token')->plainTextToken;
    }


}

<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\ICustomerRepositories;
use App\Traits\ResponseTrait;


class CustomerRepository extends AuthRepository implements ICustomerRepositories
{
    use ResponseTrait ;
    protected string $guard = 'api';
    public function __construct()
    {
        $this->model = new Customer();
     }
    protected function afterLogin($user): ?string
    {
          return $user->createToken('api-token')->plainTextToken;
    }
}

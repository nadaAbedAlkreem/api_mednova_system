<?php

namespace App\Services\Auth;


use App\Repositories\ICustomerRepositories;

class CustomerAuthService
{

    protected ICustomerRepositories $authRepository;

    public function __construct(ICustomerRepositories $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(array $credentials)
    {
         return $this->authRepository->login($credentials);
    }
}

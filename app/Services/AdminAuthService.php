<?php

namespace App\Services;


use App\Repositories\IAdminRepositories;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{

    protected IAdminRepositories $authRepository;

    public function __construct(IAdminRepositories $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function login(array $credentials): bool
    {
        $token = $this->authRepository->login($credentials);
        if ($token === false) {
              throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.']
            ]);
        }

        return $token;
    }
}

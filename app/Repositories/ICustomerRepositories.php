<?php

namespace App\Repositories;

interface ICustomerRepositories
{
    public function login(array $credentials): ?string;



}

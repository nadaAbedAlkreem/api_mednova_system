<?php

namespace App\Repositories;

interface IAdminRepositories
{

    public function login(array $credentials): ?string;


}

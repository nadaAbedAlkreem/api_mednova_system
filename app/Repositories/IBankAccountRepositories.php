<?php

namespace App\Repositories;

interface IBankAccountRepositories
{
    public function storeFromGateway($owner, array $bankData);



}

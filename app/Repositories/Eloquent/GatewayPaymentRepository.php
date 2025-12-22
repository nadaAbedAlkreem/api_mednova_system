<?php

namespace App\Repositories\Eloquent;


use App\Models\BankAccount;
use App\Repositories\IGatewayPaymentRepositories;


class GatewayPaymentRepository extends BaseRepository implements  IGatewayPaymentRepositories
{
    public function __construct()
    {
        $this->model = new BankAccount();
    }
}

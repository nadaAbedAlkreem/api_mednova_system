<?php

namespace App\Repositories\Eloquent;


use App\Models\BankAccount;
use App\Models\GatewayPayment;
use App\Repositories\IGatewayPaymentRepositories;


class GatewayPaymentRepository extends BaseRepository implements  IGatewayPaymentRepositories
{
    public function __construct()
    {
        $this->model = new GatewayPayment();
    }

    public function findByReference(string $reference): ?GatewayPayment
    {
        return $this->model
            ->where('gateway_reference', $reference)
            ->lockForUpdate()
            ->first();
    }
}

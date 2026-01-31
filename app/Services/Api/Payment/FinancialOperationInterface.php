<?php

namespace App\Services\Api\Payment;

interface FinancialOperationInterface
{
    public function execute(array $data);

}

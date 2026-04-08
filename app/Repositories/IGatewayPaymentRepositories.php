<?php

namespace App\Repositories;

use App\Models\GatewayPayment;
use App\Models\Transaction;

interface IGatewayPaymentRepositories
{
    public function findByReference(string $reference): ?GatewayPayment;
    public function findInitiatedForReference(
        string $referenceType,
        int    $referenceId
    ): ?GatewayPayment;

    public function  existsBySystemReference(string $systemReference): bool;

}

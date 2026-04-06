<?php

namespace App\Repositories;

use App\Models\GatewayPayment;

interface IGatewayPaymentRepositories
{
    public function findByReference(string $reference): ?GatewayPayment;
    public function findInitiatedForReference(
        string $referenceType,
        int    $referenceId
    ): ?GatewayPayment;
}

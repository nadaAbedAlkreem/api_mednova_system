<?php

namespace App\Repositories\Eloquent;


use App\Models\BankAccount;
use App\Models\GatewayPayment;
use App\Models\Transaction;
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

    public function findInitiatedForReference(
        string $referenceType,
        int    $referenceId
    ): ?GatewayPayment {
        return GatewayPayment::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'initiated')
            ->latest()
            ->first();
    }

    public function existsBySystemReference(string $systemReference): bool
    {
        return $this->model
            ->where('gateway_transaction_id', $systemReference)
            ->exists();
    }

}

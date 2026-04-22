<?php

namespace App\Services\Api\Financial;

use App\Enums\ConsultantType;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\ITransactionRepositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class FinancialTransactionService
{
    public function __construct(
        protected ITransactionRepositories $transactionRepository
    ) {}

    public function createWalletEntry(
        Model $reference,
        ?int $gatewayPaymentId,
        string $transactionType,
        string $entryType,
        ?int $walletId,
        float $grossAmount,
        float $netAmount,
        string $currency,
        string $status,
        array $meta = [],
        float $platformCommission = 0,
        float $vatAmount = 0
    ) {
        return $this->transactionRepository->create([
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->id,
            'gateway_payment_id' => $gatewayPaymentId,
            'transaction_type' => $transactionType,
            'entry_type' => $entryType,
            'wallet_id' => $walletId,
            'gross_amount' => $grossAmount,
            'platform_commission' => $platformCommission,
            'vat_amount' => $vatAmount,
            'net_amount' => $netAmount,
            'currency' => $currency,
            'status' => $status,
            'meta' => $meta,
        ]);
    }
}

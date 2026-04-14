<?php

namespace App\Services\Financial;

use App\Models\Transaction;

class FinancialTransactionService
{
    public function createWalletEntry(
        string $referenceType,
        int $referenceId,
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
    ): Transaction {
        return Transaction::create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
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

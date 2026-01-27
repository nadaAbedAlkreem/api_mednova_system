<?php

namespace App\Services\api;

use App\Models\GatewayPayment;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use Illuminate\Support\Facades\DB;

class WalletTopUpService  implements FinancialOperationInterface
{
    public function __construct(
        private IWalletRepositories $wallets,
        private ITransactionRepositories $transactions,
        private IGatewayPaymentRepositories $gatewayPayments
    ) {}

    public function execute(array $data)
    {
        $gatewayPayment = $data['gateway_payment'];
        $payload = $data['payload'];
        DB::transaction(function () use ($gatewayPayment, $payload) {
//            if ($gatewayPayment->purpose === 'wallet_top_up') {
            $amountPayload = $payload['amount'];
            if ($amountPayload !== $gatewayPayment->amount) {
                // سجل الفرق في GatewayPayment
                $this->gatewayPayments->update([
                    'amount' => $amountPayload,  // تحديث المبلغ
                    'response_message' => "Amount mismatch: expected {$gatewayPayment->amount}, got {$amountPayload}",
                ], $gatewayPayment->id);
            }
            $wallet = $this->wallets->getByOwner($gatewayPayment->reference_id);
            $transaction = $this->transactions->create([
                'reference_type' => $gatewayPayment->reference_type,
                'reference_id' => $gatewayPayment->reference_id,
                'transaction_type' => 'wallet_top_up',
                'entry_type' => 'credit',
                'wallet_id' => $wallet->id,
                'gross_amount' => $amountPayload,
                'net_amount' => $amountPayload ,
                'currency' => 'OMR',
                'status' => 'available',
            ]);

            $wallet->increment('balance', $amountPayload);
            $wallet->increment('available_balance', $amountPayload);
            $this->gatewayPayments->update([
                'transaction_id' => $transaction->id,
                'gateway_transaction_id' => $payload['SystemReference'] ?? null,
                'status' => 'captured',
                'payload' => $payload,
            ], $gatewayPayment->id);

//            }
        });
    }

}

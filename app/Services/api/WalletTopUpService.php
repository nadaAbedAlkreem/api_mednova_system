<?php

namespace App\Services\api;


use App\Models\User;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Eloquent\CardRepository;
use App\Repositories\Eloquent\GatewayPaymentRepository;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\Eloquent\WalletRepository;
use Illuminate\Support\Facades\DB;


class WalletTopUpService
{

    public function __construct(
        private TransactionRepository    $transactions,
        private GatewayPaymentRepository $gatewayPayments,
        private WalletRepository         $wallets,
        private AmwalPayService          $amwalPay,
        CardRepository                   $cards,
        BaseRepository                   $bank
    )
    {
    }

    public function execute(User $patient, array $data)
    {
        return DB::transaction(function () use ($patient, $data) {

            $wallet = $this->wallets->getByOwner($patient);

            // 1️⃣ Create financial transaction
            $transaction = $this->transactions->create([
                'reference_type' => User::class,
                'reference_id' => $patient->id,
                'transaction_type' => 'wallet_top_up',
                'entry_type' => 'credit',
                'wallet_id' => $wallet->id,
                'gross_amount' => $data['amount'],
                'net_amount' => $data['amount'],
                'currency' => 'OMR',
                'status' => 'pending',
            ]);

            // 2️⃣ Create gateway payment (initiated)
            $gatewayPayment = $this->gatewayPayments->create([
                'transaction_id' => $transaction->id,
                'gateway' => 'amwal',
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'currency' => 'OMR',
                'status' => 'initiated',
            ]);

            // 3️⃣ Call Amwal Pay
            $amwalResponse = $this->amwalPay->createCheckoutSession(
                $gatewayPayment,
                $patient
            );

            // 4️⃣ Update gateway payment with response
            $this->gatewayPayments->update($gatewayPayment, [
                'gateway_transaction_id' => $amwalResponse->transaction_id,
                'gateway_reference' => $amwalResponse->reference,
                'payload' => $amwalResponse->raw,
            ]);

            return [
                'checkout_url' => $amwalResponse->checkout_url,
            ];
        });
    }
}

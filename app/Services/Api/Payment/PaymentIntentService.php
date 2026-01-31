<?php

namespace App\Services\Api\Payment;

use App\Models\Customer;
use App\Repositories\IGatewayPaymentRepositories;

class PaymentIntentService
{


    public function __construct(
        private AmwalPayService $gateway,
        private IGatewayPaymentRepositories $gatewayPayments
    ) {}

    public function create(
        Customer $owner,
        float $amount,
        string $paymentMethod,
        string $purpose
    ) {
        $paymentLink = $this->gateway->createPaymentLinkByAmwalPay([
            'customer' => $owner,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);

        $this->gatewayPayments->create([
            'reference_type' => Customer::class,
            'reference_id' => $owner->id,
            'gateway' => 'amwal',
            'gateway_reference' => $paymentLink->raw['billerRefNumber'],
            'amount' => $amount,
            'currency' => 'OMR',
            'status' => 'initiated',
            'purpose' => $purpose,
            'payload' => $paymentLink->raw,
        ]);

        return $paymentLink;
    }


}

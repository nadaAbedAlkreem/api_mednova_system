<?php

namespace App\Services\Api\Payment;

use App\Exceptions\ConsultationNotPayableException;
use App\Models\Customer;
use App\Repositories\IGatewayPaymentRepositories;
use Illuminate\Support\Facades\Log;

class ConsultationPaymentIntentService
{


    public function __construct(
        private AmwalPayService $gateway,
        private IGatewayPaymentRepositories $gatewayPayments
    ) {}

    public function create(object $consultation, Customer $patient ,String $purpose  )
    {
        // ── Guard 1: الاستشارة يجب أن تكون unpaid ──────────────────────────
        if ($consultation->financial_status !== 'unpaid') {
            Log::channel('financial')->warning('payment_intent.not_payable', [
                'consultation_id'   => $consultation->id,
                'financial_status'  => $consultation->financial_status,
                'patient_id'        => $patient->id,
            ]);
            throw new ConsultationNotPayableException(
                "Consultation {$consultation->id} status is '{$consultation->financial_status}', expected 'unpaid'"
            );
        }

        // ✅ Logging
        $existing = $this->gatewayPayments->findInitiatedForReference(
            get_class($consultation),
            $consultation->id
        );

        if ($existing) {
            if ($existing->created_at->diffInMinutes(now()) < 30) {
                return [
                    'checkout_url'       => $existing->payload['checkout_url'],
                    'gateway_payment_id' => $existing->id,
                    'biller_ref'         => $existing->gateway_reference,
                ];
            }
            $this->gatewayPayments->update([
                'status'         => 'expired',
                'initiated_lock' => null,
            ], $existing->id);
        }

        // ── Step 1: توليد biller_ref فريد ───────────────────────────────────
        // Format: CONS-{TYPE}-{id}-{timestamp}
        // مثال:   CONS-CHAT-42-1737123456
        $billerRef = $this->generateBillerRef($consultation);

        // ── Step 2: إنشاء gateway_payment بـ status = initiated ─────────────
        // مهم: يُنشأ قبل استدعاء البوابة — إذا فشلت البوابة يبقى سجل للـ audit
        $gatewayPayment = $this->gatewayPayments->create([
            'reference_type'    => get_class($consultation),
            'reference_id'      => $consultation->id,
            'gateway'           => 'amwal',
            'gateway_reference' => $billerRef,
            'payment_method'    => 'card',
            'purpose'           => $purpose,
            'amount'            => $consultation['net_amount'],
            'currency'          => 'OMR',
            'status'            => 'initiated',
            'initiated_lock'    => get_class($consultation) .'-'. $consultation->id,
         ]);



//        $paymentLink = $this->gateway->createPaymentLink([
//            'customer' => $owner,
//            'amount' => $amount,
//            'payment_method' => $paymentMethod,
//        ]);

        // ── Step 3: استدعاء Amwal Pay ────────────────────────────────────────
        $response = $this->gateway->createPaymentLink([
            'biller_ref'     => $billerRef,
            'payer_name'     => $patient->full_name,
            'amount'         => $consultation['net_amount'],
            'currency'       => 512, // OMR code
            'email'          => $patient->email,
            'redirect_url'   => 'https://mednovacare.com/',
            'payment_method' => 1,
        ]);

        // ── Step 4: حفظ checkout_url في gateway_payment ──────────────────────
        $this->gatewayPayments->update( [
            'payload' => array_merge(
                ['checkout_url' => $response->checkoutUrl],
                $response->raw
            )] ,$gatewayPayment->id );


//        $this->gatewayPayments->create([
//            'reference_type' => Customer::class,
//            'reference_id' => $owner->id,
//            'gateway' => 'amwal',
//            'gateway_reference' =>$paymentLink->billerRef,
//            'amount' => $amount,
//            'currency' => 'OMR',
//            'status' => 'initiated',
//            'purpose' => $purpose,
//            'payload' => array_merge(
//                ['checkout_url' => $paymentLink->checkoutUrl],
//                $paymentLink->raw
//            ),
//            ]);

        Log::channel('financial')->info('payment_intent.created', [
            'gateway_payment_id' => $gatewayPayment->id,
            'consultation_id'    => $consultation->id,
            'consultation_type'  => get_class($consultation),
            'patient_id'         => $patient->id,
            'biller_ref'         => $billerRef,
            'amount'             => $consultation->net_amount,
        ]);

        return [
            'checkout_url'       => $response->checkoutUrl,
            'gateway_payment_id' => $gatewayPayment->id,
            'biller_ref'         => $billerRef,
        ];
    }
    private function generateBillerRef(object $consultation): string
    {
        $type = str_contains(get_class($consultation), 'Chat') ? 'CHAT' : 'VIDEO';
        return "CONS-{$type}-{$consultation->id}-" . now()->timestamp;
    }

}

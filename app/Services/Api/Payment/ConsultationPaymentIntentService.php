<?php

namespace App\Services\Api\Payment;

use App\Enums\FinancialStatus;
use App\Enums\GatewayPaymentStatus;
use App\Enums\PaymentMethodType;
use App\Enums\StatusType;
use App\Exceptions\ConsultationNotPayableException;
use App\Models\Customer;
use App\Repositories\IGatewayPaymentRepositories;
use HttpException;
use Illuminate\Support\Facades\Log;

class ConsultationPaymentIntentService
{


    public function __construct(
        private AmwalPayService             $gateway,
        private IGatewayPaymentRepositories $gatewayPayments
    )
    {
    }

    public function create(object $consultation,string $type ,  Customer $patient, string $purpose)
    {
        if (!in_array($consultation->status, [StatusType::PENDING->value])) {
            throw new HttpException(422, 'Invalid consultation state');
        }
        // ── Guard 1: الاستشارة يجب أن تكون unpaid ──────────────────────────
        if ($consultation->financial_status !== 'unpaid') {
            if ($consultation->financial_status === FinancialStatus::PAYMENT_SUSPENDED->value) {
                $remainingMinutes = now()->diffInMinutes($consultation->suspended_until, false);
                throw new ConsultationNotPayableException(
                    "تم تعليق الدفع مؤقتاً. يمكنك المحاولة بعد {$remainingMinutes} دقيقة."
                );
            }
            Log::channel('financial')->warning('payment_intent.not_payable', [
                'consultation_id' => $consultation->id,
                'financial_status' => $consultation->financial_status,
                'patient_id' => $patient->id,
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
                    'checkout_url' => $existing->payload['checkout_url'],
                    'gateway_payment_id' => $existing->id,
                    'biller_ref' => $existing->gateway_reference,
                ];
            }
            $this->gatewayPayments->update([
                'status' => 'expired',
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
            'reference_type' => get_class($consultation),
            'reference_id' => $consultation->id,
            'gateway' => 'amwal',
            'gateway_reference' => $billerRef,
            'payment_method' => PaymentMethodType::METHOD_CARD->value ?? 'card',
            'purpose' => $purpose,
            'amount' => $consultation->gross_amount,
            'net_received_amount' => $consultation->consultation_price,
            'currency' => config('amwal.currency_en') ?? 'OMR',
            'status' => GatewayPaymentStatus::INITIATED->value ?? 'initiated',
            'initiated_lock' => get_class($consultation) . '-' . $consultation->id,
        ]);
         $url = config('amwal.redirectUrl') . app()->getLocale() . '/profile/consultations/' . $type . '/' . $consultation->id ;
          Log::channel('financial')->warning('payment_intent.initiated', ['url' => $url]);
        // ── Step 3: استدعاء Amwal Pay ────────────────────────────────────────
        try {
            $response = $this->gateway->createPaymentLink([
                'biller_ref' => $billerRef,
                'payer_name' => $patient->full_name,
                'amount' => $consultation->gross_amount,
                'currency' => 512, // OMR code
                'email' => $patient->email,
                'redirect_url' => config('amwal.redirectUrl')
                    . app()->getLocale()
                    . '/payment?'
                    . http_build_query([
                        'consultation_id' => $consultation->id,
                        'type'            => $type,
                        'payment_return'  => 1,
                    ]),
                'payment_method' => 0,
            ]);

            // ── Step 4: حفظ checkout_url في gateway_payment ──────────────────────
            $this->gatewayPayments->update([
                'payload' => array_merge(
                    ['checkout_url' => $response->checkoutUrl],
                    $response->raw
                )], $gatewayPayment->id);

            Log::channel('financial')->info('payment_intent.created', [
                'gateway_payment_id' => $gatewayPayment->id,
                'consultation_id' => $consultation->id,
                'consultation_type' => get_class($consultation),
                'patient_id' => $patient->id,
                'biller_ref' => $billerRef,
                'amount' => $consultation->gross_amount,
            ]);

            return [
                'checkout_url' => $response->checkoutUrl,
                'gateway_payment_id' => $gatewayPayment->id,
                'biller_ref' => $billerRef,
            ];
        } catch (\Exception $e) {
            $this->gatewayPayments->update([
                'status' => 'failed',
                'initiated_lock' => null,
            ], $gatewayPayment->id);

            Log::channel('financial')->error('payment_intent.failed', [
                'gateway_payment_id' => $gatewayPayment->id,
                'consultation_id' => $consultation->id,
                'consultation_type' => get_class($consultation),
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            // إعادة رمي الاستثناء للتعامل معه في الطبقة العليا
            throw $e;
        }
    }

    private function generateBillerRef(object $consultation): string
    {
        $type = str_contains(get_class($consultation), 'Chat') ? 'CHAT' : 'VIDEO';
        return "CONS-{$type}-{$consultation->id}-" . now()->timestamp;
    }

}

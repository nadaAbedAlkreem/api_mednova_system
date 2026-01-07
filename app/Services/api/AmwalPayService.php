<?php

namespace App\Services\api;

use App\Models\Customer;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AmwalPayService
{
    use ResponseTrait;

//    public function __construct(
//        private TransactionRepository    $transactions,
//        private GatewayPaymentRepository $gatewayPayments,
//        private WalletRepository         $wallets,
//        CardRepository                   $cards,
//        BaseRepository                   $bank)
//    {
//    }
    protected IWalletRepositories $walletRepository;
    protected ITransactionRepositories $transactionRepository;
    protected IGatewayPaymentRepositories $gatewayPaymentRepository;

    public function __construct(IWalletRepositories $walletRepository, TransactionRepository $transactionRepository, IGatewayPaymentRepositories $gatewayPaymentRepository)
    {
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
        $this->gatewayPaymentRepository = $gatewayPaymentRepository;
    }

    public function createPaymentLinkByAmwalPay($data): object
    {
        try {
            $tid = config('amwal.tid');
            $mid = config('amwal.mid');
            $currency = config('amwal.currency.OMR');
            $url = config('amwal.redirectUrl');
            $amwalBaseUrl = config('amwal.base_url');
            $paymentMethod = config('amwal.payment_methods')[$data['payment_method']] ?? null;
            if (!$paymentMethod) {
                throw new \Exception('Invalid payment method');
            }

            $billerRef = Str::uuid()->toString();

            $payload = [
                'billerRefNumber' => $billerRef,
                'payerName' => $data['customer']->full_name,
                'amount' => number_format($data['amount'], 3, '.', ''),
                'currency' => $currency,
                'paymentMethod' => $paymentMethod,

                'notificationMethod' => 1,
                'emailNotificationValue' => $data['customer']->email,
                'smsNotificationValue' => '',

                'terminalId' => $tid,
                'merchantId' => $mid,

                'expireDateTime' => '',
                'maxNumberOfPayment' => 1,
                'paymentViewType' => 1,

                'redirectUrl' => $url . '/profile/consultations',
            ];

            // Generate secure hash
            $payload['secureHashValue'] = $this->generateSecureHash($payload);
            Log::error('secureHashValue in  payment link : ' .  $payload['secureHashValue']);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
                $amwalBaseUrl . '/MerchantOrder/CreatePaymentLink',
                $payload
            );
            if (!($response->successful() && ($response->json('success') === true))) {
                throw new \Exception(__('messages.failed_to_initialize'));
            }
            $this->gatewayPaymentRepository->create([
                'transaction_id' => null,
                'reference_type' => Customer::class,
                'reference_id' => $data['customer']->id,
                'gateway' => 'amwal',
                'gateway_transaction_id' => null,
                'gateway_reference' => $billerRef,
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'currency' => 'OMR',
                'status' => 'initiated',
                'response_message' => $response['message'] ?? null,
                'payload' => $response, // JSON column ممتاز
            ]);

            return (object)[
                'success' => $response->successful() && ($response->json('success') === true),
                'payment_url' => $response->json('data'),
                'raw' => $response->json(),
            ];
        } catch (Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }


    public function handleWebhook($request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();

        // ⃣ سجل البيانات القادمة للتحقق
        Log::info('AmwalPay Webhook:', $payload);

        // 2⃣ تحقق من الـ secure hash
        $receivedHash = $payload['SecureHash'] ?? null;
        $calculatedHash = $this->generateSecureHashForWebhook($payload);

        if ($receivedHash !== $calculatedHash) {
            Log::warning('Invalid secure hash on webhook', $payload);
            return response()->json(['message' => 'Invalid secure hash'], 401);
        }

        //⃣ 3️ استخرج المرجع والمبلغ والحالة
        $gatewayReference = $payload['MerchantReference'] ?? null;
        $amountOMR = $payload['AmountOMR'] ?? null;
        $statusMessage = $payload['Message'] ?? null;
        $responseCode = $payload['ResponseCode'] ?? null;
        $systemReference = $payload['SystemReference'] ?? null;

        if (!$gatewayReference) {
            Log::error('Missing gateway reference in webhook', $payload);
            return response()->json(['message' => 'Missing gateway reference'], 400);
        }

        // 4   احصل على GatewayPayment المرتبط
        $gatewayPayment = $this->gatewayPaymentRepository->findByReference($gatewayReference);

        if (!$gatewayPayment) {
            Log::error('Gateway payment not found for reference: ' . $gatewayReference, $payload);
            return response()->json(['message' => 'Gateway payment not found'], 404);
        }

        // ️⃣ حدد حالة الدفع
        $paymentStatus = ($responseCode === '00' && $statusMessage === 'AUTHORIZED') ? 'captured' : 'failed';

        try {
            DB::transaction(function () use (
                $gatewayPayment,
                $payload,
                $paymentStatus,
                $amountOMR,
                $systemReference) {
                // 7️⃣ إذا الدفع ناجح، أنشئ Transaction وحدث الـ Wallet
                if ($paymentStatus === 'captured') {

                    // تأكد من Idempotency: هل Transaction موجود مسبقًا لهذا GatewayPayment؟
                    if ($gatewayPayment->transaction_id !== null) {
                        Log::info('Webhook already processed for this gateway payment', [
                            'gateway_payment_id' => $gatewayPayment->id,
                            'transaction_id' => $gatewayPayment->transaction_id,
                        ]);
                        return;
                    }
                    $wallet = $this->walletRepository->getByOwner($gatewayPayment->reference_id);
                    if (!$wallet) {
                        throw new \Exception('Invalid wallet id');
                    }
                    if ((float)$amountOMR !== (float)$gatewayPayment->amount) {
                        throw new \Exception('Amount mismatch');
                    }


                    $transaction = $this->transactionRepository->create([
                        'reference_type' => $gatewayPayment->reference_type,
                        'reference_id' => $gatewayPayment->reference_id,
                        'transaction_type' => 'wallet_top_up',
                        'entry_type' => 'credit',
                        'wallet_id' => $wallet->id,
                        'gross_amount' => $amountOMR,
                        'net_amount' => $amountOMR,
                        'currency' => 'OMR',
                        'status' => 'available',
//                            'meta' => [
////                                'gateway_payment_id' => $gatewayPayment->id,
////                                'gateway' => $gatewayPayment->gateway,
//                            ],
                    ]);
                    // 6️⃣ حدّث GatewayPayment
                    $this->gatewayPaymentRepository->update($gatewayPayment->id, [
                        'transaction_id' => $transaction->id,
                        'gateway_transaction_id' => $systemReference,
                        'status' => $paymentStatus,
                        'payload' => $payload,
                    ]);
                    // تحديث رصيد الـ Wallet
                    $wallet->increment('balance', $amountOMR);
                    $wallet->increment('available_balance', $amountOMR);

                }
            });
        } catch (\Exception $e) {
            Log::error('Error processing AmwalPay webhook', [
                'message' => $e->getMessage(),
                'payload' => $payload
            ]);

            return response()->json(['message' => 'Error processing webhook'], 500);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }

    private function generateSecureHash(array $payload): string
    {
        unset($payload['SecureHash'], $payload['secureHashValue']);

        // ترتيب المفتاح alphabetically
        ksort($payload);

        // تحويل كل القيمة إلى string صريح
        $baseString = collect($payload)
            ->map(fn($value, $key) => $value === null ? "{$key}=" : "{$key}={$value}")
            ->implode('&');

        $binaryKey = hex2bin(config('amwal.secure_key'));

        return strtoupper(hash_hmac('sha256', $baseString, $binaryKey));
    }

    private function generateSecureHashForWebhook(array $payload): string
    {
        $allowedKeys = [
            'MerchantId',
            'TerminalId',
            'AuthorizationDateTime',
            'DateTimeLocalTrxn',
            'ResponseCode',
            'TxnType',
            'PaidThrough',
            'SystemReference',
            'Message',
            'MerchantReference',
            'Amount',
            'CurrencyId',
        ];

        // 1️⃣ فلترة
        $filtered = array_intersect_key($payload, array_flip($allowedKeys));

        // 2️⃣ null → empty string
        $filtered = array_map(fn ($v) => $v === null ? '' : (string)$v, $filtered);

        // 3️⃣ ترتيب أبجدي
        ksort($filtered);

        // 4️⃣ base string
        $baseString = collect($filtered)
            ->map(fn($v, $k) => "{$k}={$v}")
            ->implode('&');

        Log::info('Webhook baseString: ' . $baseString);

        // 5️⃣ HMAC SHA256 (IMPORTANT)
        $binaryKey = hex2bin(config('amwal.secure_key'));

        return strtoupper(hash_hmac('sha256', $baseString, $binaryKey));
    }





}

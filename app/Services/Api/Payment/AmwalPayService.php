<?php

namespace App\Services\Api\Payment;

use App\Exceptions\GatewayException;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function createPaymentLink($data): object
    {
        try {
            $redirectUrl     = $data['redirect_url'];
            $billerRef     = $data['biller_ref'];
            $amount        = number_format($data['amount'], 3, '.', '');
            $currency      = config('amwal.currency.OMR'); // 512
            $tid           = config('amwal.tid');
            $mid           = config('amwal.mid');
            $amwalBaseUrl  = config('amwal.base_url');
            $paymentMethod = config('amwal.payment_methods')['card']; // 1

            $payload = [
                'billerRefNumber'        => $billerRef,
                'payerName'              => $data['payer_name'],
                'amount'                 => $amount,
                'currency'               => $currency,
                'paymentMethod'          => $paymentMethod,
                'notificationMethod'     => 1,
                'emailNotificationValue' => $data['email'],
                'smsNotificationValue'   => '',
                'terminalId'             => $tid,
                'merchantId'             => $mid,
                'expireDateTime'         => '',
                'maxNumberOfPayment'     => 1,
                'paymentViewType'        => 1,
                'redirectUrl'            => $redirectUrl,
            ];
            Log::channel('financial')->warning('payment_intent.$payload', ['$payload' => $payload]);


            // ✅ نفس طريقة الكود القديم: hash على كل الـ payload
            $payload['secureHashValue'] = $this->generateSecureHash($payload);

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($amwalBaseUrl . '/MerchantOrder/CreatePaymentLink', $payload);

            if ($response->failed()) {
                throw new \Exception('HTTP error: ' . $response->status());
            }

            $body = $response->json();

            if (!($body['success'] ?? false)) {
                Log::channel('financial')->warning('payment_intent.Amwal Pay ', ['$payload' => $body['message']]);

                throw new \Exception('Amwal Pay error: ' . ($body['message'] ?? 'Unknown'));
            }


            return (object)[
                'success'     => true,
                'checkoutUrl' => $body['data'],
                'billerRef'   => $billerRef,
                'raw'         => $body,
            ];

        } catch (\Exception $exception) {
            throw new GatewayException($exception->getMessage());

        }
    }




    private function generateSecureHash(array $payload): string
    {
        unset($payload['SecureHash'], $payload['secureHashValue']);

        ksort($payload);

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
        $filtered = array_map(fn($v) => $v === null ? '' : (string)$v, $filtered);

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

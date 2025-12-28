<?php
namespace App\Services\api;

use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\ITransactionRepositories;
use App\Repositories\IWalletRepositories;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

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
    protected ITransactionRepositories   $transactionRepository;
    protected IGatewayPaymentRepositories $gatewayPaymentRepository;

    public function __construct(IWalletRepositories $walletRepository , TransactionRepository $transactionRepository , IGatewayPaymentRepositories $gatewayPaymentRepository)
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
           $amwalBaseUrl   = config('amwal.base_url');
           $paymentMethod = config('amwal.payment_methods')[$data['payment_method']] ?? null;
           if (!$paymentMethod) {
               throw new \Exception('Invalid payment method');
           }



           $payload = [
               'billerRefNumber' => uniqid('wallet_'),
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

           $response = Http::withHeaders([
               'Content-Type' => 'application/json',
           ])->post(
               $amwalBaseUrl. '/MerchantOrder/CreatePaymentLink',
               $payload
           );
           if (!($response->successful() && ($response->json('success') === true))) {
               throw new \Exception(__('messages.failed_to_initialize'));
           }

           $this->gatewayPaymentRepository->create([
               'transaction_id'         => null,
               'gateway'                => 'amwal',
               'gateway_transaction_id' => null,
               'gateway_reference'      => $response['billerRefNumber'] ?? null,
               'payment_method'         => $data['payment_method'],
               'amount'                 => $data['amount'],
               'currency'               => 'OMR',
               'status'                 => 'initiated',
               'response_message'       => $response['message'] ?? null,
               'payload'                => $response, // JSON column ممتاز
           ]);

           return (object)[
               'success' => $response->successful() && ($response->json('success') === true),
               'payment_url' => $response->json('data'),
               'raw' => $response->json(),
           ];
       }catch (Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
       }
    }

    private function generateSecureHash(array $payload): string
    {
        unset($payload['secureHashValue']);

        ksort($payload);

        $baseString = collect($payload)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode('&');

        $binaryKey = hex2bin(config('amwal.secure_key'));

        return hash_hmac('sha256', $baseString, $binaryKey);
    }
    public function handleWebhook(Request $request ): \Illuminate\Http\JsonResponse
    {
        // 1. سجل البيانات القادمة للتحقق
        Log::info('AmwalPay Webhook:', $request->all());
//
//         2. تحقق من الـ secure hash (للتأكد من أن الطلب أصلي)
//        $payload = $request->all();
//        $receivedHash = $payload['secureHashValue'] ?? null;
//
//        // استخدم نفس الدالة generateSecureHash لتأكيد صحة البيانات
//        $calculatedHash = $this->generateSecureHash($payload);
//
//        if ($receivedHash !== $calculatedHash) {
//            return response()->json(['message' => 'Invalid secure hash'], 401);
//        }
//
//        // 3. معالجة الدفع حسب نوع الحدث
//        // مثال: تحديث حالة الدفع في جدول المدفوعات
//        $transactionId = $payload['billerRefNumber'] ?? null;
//        $status = $payload['status'] ?? null; // تحقق من الوثائق الدقيقة للحقول
//        if ($transactionId && $status) {
//            $wallet = $this->wallets->getByOwner($customer);
//            if(!$wallet)
//            {
//                throw new \Exception('Invalid wallent id');
//            }
//            $this->transactions->create([
//                'reference_type' => User::class,
//                'reference_id' => $customer->id,
//                'transaction_type' => 'wallet_top_up',
//                'entry_type' => 'credit',
//                'wallet_id' => $wallet->id,
//                'gross_amount' => $payload['amount'],
//                'net_amount' => $payload['amount'],
//                'currency' => 'OMR',
//                'status' => 'pending',
//            ]);
//            $this->gatewayPayments->updateWhere(['gateway_reference' ,$payload['billerRefNumber']], [
//                'gateway_transaction_id' => $payload['transaction_id'],
//                'gateway_reference' => $payload['billerRefNumber'],
//                'payload' => $payload['raw'],
//            ]);


//         }    باقي تحديث قيمة  البلانس

        return response()->json(['message' => 'Webhook received'], 200);
    }


 }

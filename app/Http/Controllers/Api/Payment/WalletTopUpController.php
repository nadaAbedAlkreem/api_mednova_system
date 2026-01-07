<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\payment\WalletTopUpRequest;
use App\Models\Customer;
use App\Repositories\IWalletRepositories;
use App\Services\api\AmwalPayService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class WalletTopUpController extends Controller
{
    use ResponseTrait;
    protected IWalletRepositories $walletRepository;
    protected AmwalPayService $amwalPayService;

    public function __construct(IWalletRepositories $walletRepository , AmwalPayService $amwalPayService)
    {
        $this->walletRepository = $walletRepository;
        $this->amwalPayService = $amwalPayService;
    }

    /**
     * Wallet Top-up (Patient)
     */
    public function store(WalletTopUpRequest $request)
    {
         try {
            $customer = $request->user();
            if (!$customer instanceof Customer) {
                throw new \Exception('Get Current Customer  Failed');
            }
             /** @var \App\Models\Wallet $wallet */
            $wallet = $this->walletRepository->getByOwner($customer);
            /**
             * 1️⃣ Create checkout session with Amwal Pay
             * (NO DB RECORDS YET)
             */
             Log::info('AmwalPay Webhook test  in store:');

             $paymentLink = $this->amwalPayService->createPaymentLinkByAmwalPay(['amount' => $request->amount, 'currency' => 'OMR', 'payment_method' => $request->payment_method, 'customer' => $customer]);

             return $this->successResponse(__('messages.successful_create_payment_link'),$paymentLink, 202);
        } catch (\Exception $exception) {
             return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }



    public function captureDataViaWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            Log::info('AmwalPay Webhook test :');
            $paymentLink = $this->amwalPayService->handleWebhook($request);
            return $this->successResponse(__('messages.successful_create_payment_link'),$paymentLink, 202);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }



    /**
         * 2️⃣ Payment succeeded immediately (as per your rule)
         * Now we open DB transaction
         */
//        DB::transaction(function () use ($request, $wallet, $checkout) {
//
//            /**
//             * A️⃣ Create Transaction
//             */
//            $transaction = $this->transactions->create([
//                'reference_type' => get_class($wallet->owner),
//                'reference_id' => $wallet->owner->id,
//
//                'transaction_type' => 'wallet_top_up',
//                'entry_type' => 'credit',
//                'wallet_id' => $wallet->id,
//
//                'gross_amount' => $request->amount,
//                'platform_commission' => 0,
//                'vat_amount' => 0,
//                'net_amount' => $request->amount,
//
//                'currency' => 'OMR',
//
//                // Amount is AVAILABLE immediately
//                'status' => 'available',
//
//                'meta' => [
//                    'gateway' => 'amwal',
//                ],
//            ]);
//
//            /**
//             * B️⃣ Update Wallet Balances
//             */
//            $this->wallets->increaseAvailableBalance(
//                $wallet,
//                $transaction->net_amount
//            );
//
//            /**
//             * C️⃣ Save Card / Bank (if needed)
//             */
//            $cardId = null;
//            $bankAccountId = null;
//
//            if ($request->payment_method === 'card') {
//                $card = $this->cards->storeFromGateway(
//                    $wallet->owner,
//                    $checkout->card
//                );
//                $cardId = $card->id;
//            }
//
//            if ($request->payment_method === 'bank') {
//                $bank = $this->bankAccounts->storeFromGateway(
//                    $wallet->owner,
//                    $checkout->bank
//                );
//                $bankAccountId = $bank->id;
//            }
//
//            /**
//             * D️⃣ Create Gateway Payment Record
//             */
//            $this->gatewayPayments->create([
//                'transaction_id' => $transaction->id,
//                'gateway' => 'amwal',
//
//                'gateway_transaction_id' => $checkout->transaction_id,
//                'gateway_reference' => $checkout->reference,
//
//                'payment_method' => $request->payment_method,
//                'card_id' => $cardId,
//                'bank_account_id' => $bankAccountId,
//
//                'amount' => $transaction->gross_amount,
//                'currency' => 'OMR',
//                'country' => $checkout->country ?? 'OM',
//
//                // Gateway status only
//                'status' => 'captured',
//
//                'response_code' => $checkout->response_code,
//                'response_message' => $checkout->response_message,
//                'payload' => $checkout->raw(),
//            ]);
//        });
//
//        /**
//         * 3️⃣ Return success
//         */
//        return response()->json([
//            'message' => __('wallet.top_up_success'),
//            'redirect_url' => $checkout->redirect_url,
//        ]);
//    }
}

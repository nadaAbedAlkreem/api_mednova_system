<?php

namespace App\Http\Controllers\Api\Payment;

use App\Exceptions\ConsultationNotPayableException;
use App\Exceptions\GatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\payment\PaymentIntentRequest;
use App\Http\Requests\api\payment\StoreGatewayPaymentRequest;
use App\Http\Requests\api\payment\UpdateGatewayPaymentRequest;
use App\Http\Resources\Api\Financial\ConsultantTransactionResource;
use App\Http\Resources\Api\Financial\PatientPaymentResource;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\GatewayPayment;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\IWalletRepositories;
use App\Services\Api\Financial\PatientFinancialService;
use App\Services\Api\Payment\AmwalPayService;
use App\Services\Api\Payment\ConsultationPaymentIntentService;
use App\Services\Api\Payment\FinancialOperationFactory;
use App\Services\Api\Payment\PaymentFeeCalculator;
use App\Services\Api\Payment\WalletTopUpService;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayPaymentController extends Controller
{
    use ResponseTrait;
    use AuthorizesRequests;

    protected AmwalPayService $amwalPayService;
    protected ConsultationPaymentIntentService $consultationPaymentIntentService;
    protected IGatewayPaymentRepositories $gatewayPaymentRepositories;
    protected WalletTopUpService $financialTransactionService;


    public function __construct(private readonly PatientFinancialService $financialService
        , WalletTopUpService  $financialTransactionService, IGatewayPaymentRepositories $gatewayPaymentRepositories,  IWalletRepositories  $walletRepository, AmwalPayService $amwalPayService, ConsultationPaymentIntentService $consultationPaymentIntentService)
    {
        $this->amwalPayService = $amwalPayService;
        $this->consultationPaymentIntentService = $consultationPaymentIntentService;
        $this->gatewayPaymentRepositories = $gatewayPaymentRepositories;
        $this->financialTransactionService = $financialTransactionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function __invoke(
        StoreGatewayPaymentRequest $request,
        string                     $type,
        int                        $id
    )
    {
        try {
            $consultation = match ($type) {
                'chat' => ConsultationChatRequest::findOrFail($id),
                'video' => ConsultationVideoRequest::findOrFail($id),
                default => throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('نوع الطلب غير موجود'),
            };
            $this->authorize('pay', $consultation);
            $result = $this->consultationPaymentIntentService->create(
                consultation: $consultation,
                patient: $request->user(),
                purpose: 'payment_consultation',
//                cardType:      $request->input('card_type', 'domestic')
            );

            return $this->successResponse(__('messages.payment_link_created_successfully'), [
                'checkout_url' => $result['checkout_url'],
                'gateway_payment_id' => $result['gateway_payment_id'],
                'biller_ref' => $result['biller_ref'],
                'expires_in_minutes' => 30,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => 'الاستشارة المطلوبة غير موجودة'
            ], 404);

        } catch (AuthorizationException $e) { // ✅ قبل Exception
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $e->getMessage(),
            ], 403);

        } catch (ConsultationNotPayableException) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => 'This consultation cannot be paid at this time'], 422);
        } catch (GatewayException $e) {
            Log::channel('financial')->error('payment_intent.gateway_error', [
                'consultation_id' => $id,
                'consultation_type' => $type,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => 'Payment gateway error'], 502);
        } catch (Exception $e) {
            Log::channel('ERROR_OCCURRED')->error('ERROR_OCCURRED', [
                'consultation_id' => $id,
                'consultation_type' => $type,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }


    public function payments(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            ]);

            $patient = $request->user();
            $perPage = (int) $request->input('per_page', 15);
            $payments = $this->financialService->getPaymentHistory($patient, $perPage);
            $paymentIds    = $payments->getCollection()->pluck('id')->all();
            $refundedIds   = $this->financialService->getRefundedPaymentIds($patient, $paymentIds);
            $resources = $payments->getCollection()->map(
                fn ($payment) => PatientPaymentResource::make($payment)
                    ->withRefund($refundedIds->contains($payment->id))
            );
            $payments->setCollection($resources);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), PatientPaymentResource::collection($payments), 202);
        }catch (Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }

    }


}

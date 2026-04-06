<?php

namespace App\Http\Controllers\Api\Payment;

use App\Exceptions\ConsultationNotPayableException;
use App\Exceptions\GatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\payment\PaymentIntentRequest;
use App\Http\Requests\api\payment\StoreGatewayPaymentRequest;
use App\Http\Requests\api\payment\UpdateGatewayPaymentRequest;
use App\Models\ConsultationChatRequest;
use App\Models\ConsultationVideoRequest;
use App\Models\GatewayPayment;
use App\Repositories\IGatewayPaymentRepositories;
use App\Repositories\IWalletRepositories;
use App\Services\Api\Payment\AmwalPayService;
use App\Services\Api\Payment\ConsultationPaymentIntentService;
use App\Services\Api\Payment\PaymentFeeCalculator;
use App\Services\Api\Payment\WalletTopUpService;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GatewayPaymentController extends Controller
{
    use ResponseTrait;
    use AuthorizesRequests;
    protected AmwalPayService $amwalPayService;
    protected ConsultationPaymentIntentService $consultationPaymentIntentService;
    protected IGatewayPaymentRepositories $gatewayPaymentRepositories;
    protected WalletTopUpService $financialTransactionService;


    public function __construct(WalletTopUpService  $financialTransactionService, IGatewayPaymentRepositories $gatewayPaymentRepositories,
                                IWalletRepositories $walletRepository, AmwalPayService $amwalPayService, ConsultationPaymentIntentService $consultationPaymentIntentService)
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
                'chat'  => ConsultationChatRequest::findOrFail($id),
                'video' => ConsultationVideoRequest::findOrFail($id),
                default => throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('نوع الطلب غير موجود'),
            };
            $this->authorize('pay', $consultation);
            $result = $this->consultationPaymentIntentService->create(
                consultation: $consultation,
                patient: $request->user(),
                purpose: 'payment_consultation',
//                cardType:      $request->input('card_type', 'domestic')
            )
            ;

            return $this->successResponse(__('messages.payment_link_created_successfully'), [
                    'checkout_url' => $result['checkout_url'],
                    'gateway_payment_id' => $result['gateway_payment_id'],
                    'biller_ref' => $result['biller_ref'],
                    'expires_in_minutes' => 30,
                ], 201);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => 'الاستشارة المطلوبة غير موجودة'
            ], 404);

        }
        catch (AuthorizationException $e) { // ✅ قبل Exception
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $e->getMessage(),
            ], 403);

        }
        catch (ConsultationNotPayableException) {
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
             return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' =>  $e->getMessage()], 500);
        }
    }

    /**
     * جلب الاستشارة مع التحقق أنها تخص المريض الحالي
     * يمنع IDOR — مريض لا يستطيع الدفع لاستشارة مريض آخر
     */
//    private function resolveConsultation(string $type, int $id): object
//    {
//        if (request()->user()->type_account !== 'patient') {
//            abort(403, 'Only patients can create consultations');
//        }
//        $patientId = request()->user()->id;
//        return match ($type) {
//            'chat' => ConsultationChatRequest::where('id', $id)
//                ->where('patient_id', $patientId)
//                ->firstOrFail(),
//
//            'video' => ConsultationVideoRequest::where('id', $id)
//                ->where('patient_id', $patientId)
//                ->firstOrFail(),
//
//            default =>abort(404),
//        };
//    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
//    public function store(StoreGatewayPaymentRequest $request): \Illuminate\Http\JsonResponse
//    {
//        try {
//            $customer = $request->user();
//            $result =  $this->consultationPaymentIntentService->create(
//                owner: $customer,
//                amount: $request->amount,
//                paymentMethod: $request->payment_method,
//                purpose: 'payment_for_consultation'
//            );
//            return response()->json(['success' => true, 'message' => 'Payment link created successfully', 'data' => $result]);
//        }catch (\Exception $exception){
//            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
//        }
//
//     }

    /**
     * Display the specified resource.
     */
    public function show(GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGatewayPaymentRequest $request, GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GatewayPayment $gatewayPayment)
    {
        //
    }
}

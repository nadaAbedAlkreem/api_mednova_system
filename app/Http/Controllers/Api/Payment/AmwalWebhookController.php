<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Api\Payment\AmwalWebhookService;
use App\Services\Api\Payment\ConsultationWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AmwalWebhookController extends Controller
{
    public function __construct(
        protected AmwalWebhookService $service,
        protected ConsultationWebhookService $consultationWebhookService,
    ) {}
//
//    public function handle(Request $request): JsonResponse
//    {
//        $this->service->handleWebhook($request->all());
//
//        return response()->json(['message' => 'Webhook processed successfully']);
//    }

    public function handleConsultation(Request $request): JsonResponse
    {
        try {
            $this->consultationWebhookService->processWebhook($request->only([
                'MerchantReference',
                'ResponseCode',
                'SystemReference',
                'Amount',
                'CurrencyId',
                'SecureHash',
            ]));
            return response()->json(['message' => 'Consultation webhook processed successfully.'], 200);
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unexpected error while processing webhook.',
            ], 500);
        }
    }
}

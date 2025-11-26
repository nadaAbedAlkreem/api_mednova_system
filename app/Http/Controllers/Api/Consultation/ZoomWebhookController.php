<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Models\ConsultationVideoRequest;
use App\Services\api\ZoomMeetingService;
use App\Traits\ResponseTrait;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    use ResponseTrait;
    protected ZoomMeetingService $zoomWebhookService;

    public function __construct(ZoomMeetingService $zoomWebhookService)
    {
        $this->zoomWebhookService = $zoomWebhookService;
    }

//    public function handle(Request $request): \Illuminate\Http\JsonResponse
//    {
//        Log::info('ZoomWebhook handled' . $request);
//        try {
//            if ($request->has('payload') && $request->input('payload.plainToken')) {
//                Log::info('ZoomWebhook payload' . $request);
//                $encryptedToken = hash_hmac('sha256', $request->input('payload.plainToken'), config('services.zoom.secret_token_webhook')); // Replace with your actual secret token
//                Log::info('ZoomWebhook encryptedToken' . $encryptedToken);
//            }
////            $this->zoomWebhookService->handleEvent($request->all());
//          return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), [], 200);
//        }catch (\Exception $exception){
//          return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
//
//        }
//    }
    public function handle(Request $request)
    {
        Log::info('ZoomWebhook Request:', $request->all());

        // URL Validation Step
        if ($request->event === 'endpoint.url_validation') {

            $plainToken = $request->input('payload.plainToken');

            $encryptedToken = hash_hmac(
                'sha256',
                $plainToken,
                config('services.zoom.secret_token_webhook')
            );

            Log::info('Returning Zoom Validation Response', [
                'plainToken' => $plainToken,
                'encryptedToken' => $encryptedToken
            ]);

            return response()->json([
                "plainToken" => $plainToken,
                "encryptedToken" => $encryptedToken
            ], 200);
        }

        // After validation: handle real events
       $this->zoomWebhookService->handleEvent($request->all());

        Log::info('ZoomWebhook Event:', $request->all());

        return response("OK", 200);
    }


}

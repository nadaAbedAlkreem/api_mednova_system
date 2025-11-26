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

    public function validateWebhook(Request $request)
    {
        if ($request->has('payload.plainToken')) {
            return response()->json([
                "plainToken" => $request->input('payload.plainToken'),
                "encryptedToken" => hash_hmac(
                    'sha256',
                    $request->input('payload.plainToken'),
                    config('services.zoom.secret_token_webhook')
                )
            ]);
        }

        // If Zoom sends empty validation request, just return 200 OK
        return response("OK", 200);
    }
    public function handleEvents(Request $request)
    {
        Log::info('Zoom Event Received', $request->all());

        try {
            $this->zoomWebhookService->handleEvent($request->all());
            return response("success", 200);

        } catch (\Exception $exception) {
            Log::error('Zoom Webhook Error', ['error' => $exception->getMessage()]);
            return response("error", 500);
        }
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
//            $this->zoomWebhookService->handleEvent($request->all());
//          return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), [], 200);
//        }catch (\Exception $exception){
//          return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
//
//        }
//    }
//    public function test()
//    {
//
//        $apiKey = 'kL_CnudqTvKhu4V1PvIfEQ';
//        $apiSecret = 'zGnX01M8kjHJClBeVPk9goCKhjOuWk1w';
//
//        $payload = [
//            'iss' => $apiKey,
//            'exp' => time() + 60 // صلاحية 1 دقيقة
//        ];
//
//        $jwt = JWT::encode($payload, $apiSecret, 'HS256');
//        $userId = '16781312';
//        $curl = curl_init();
//        curl_setopt_array($curl, [
//            CURLOPT_URL => "https://api.zoom.us/v2/users/{$userId}",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_HTTPHEADER => [
//                "Authorization: Bearer {$jwt}",
//                "Content-Type: application/json"
//            ]
//        ]);
//
//        $response = curl_exec($curl);
//        curl_close($curl);
//
//        $userData = json_decode($response, true);
//         return $userData;
//    }
}

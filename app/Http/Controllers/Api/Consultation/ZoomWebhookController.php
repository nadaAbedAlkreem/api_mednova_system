<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Services\Api\Consultation\ZoomMeetingService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
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
        Log::warning('Zoom webhook: ');

        // Handle Zoom URL validation challenge
        if ($request->input('event') === 'endpoint.url_validation') {
            $plainToken = $request->input('payload.plainToken');
            $encryptedToken = hash_hmac('sha256', $plainToken, config('services.zoom.secret_token_webhook'));

            Log::info('Zoom webhook URL validation', ['plainToken' => $plainToken]);

            return response()->json([
                'plainToken'     => $plainToken,
                'encryptedToken' => $encryptedToken,
            ]);
        }

        // Verify Zoom signature for all other events
        $signature = $request->header('x-zm-signature');
        $timestamp  = $request->header('x-zm-request-timestamp');

        if (!$signature || !$timestamp) {
            Log::warning('Zoom webhook: missing signature headers');
            return response('Unauthorized', 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('Zoom webhook: stale request timestamp');
            return response('Unauthorized', 401);
        }

        $expected = 'v0=' . hash_hmac(
            'sha256',
            "v0:{$timestamp}:{$request->getContent()}",
            config('services.zoom.secret_token_webhook')
        );

        if (!hash_equals($expected, $signature)) {
            Log::warning('Zoom webhook: invalid signature');
            return response('Unauthorized', 401);
        }

        Log::info('ZoomWebhook Request:', $request->all());

        $this->zoomWebhookService->handleEvent($request->all());

        Log::info('ZoomWebhook Event processed:', ['event' => $request->input('event')]);

        return response('OK', 200);
    }


}

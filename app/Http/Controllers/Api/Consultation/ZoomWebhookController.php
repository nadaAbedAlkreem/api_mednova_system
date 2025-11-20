<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Models\ConsultationVideoRequest;
use App\Services\api\ZoomMeetingService;
use App\Traits\ResponseTrait;
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

    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('ZoomWebhook handled' . $request);
        try {
            if ($request->has('payload') && $request->input('payload.plainToken')) {
                Log::info('ZoomWebhook payload' . $request);

                // Construct the response for Zoom's validation
                $encryptedToken = hash_hmac('sha256', $request->input('payload.plainToken'), config('services.zoom.secret_token')); // Replace with your actual secret token
                Log::info('ZoomWebhook encryptedToken' . $encryptedToken);

                return response()->json([
                    'plainToken' => $request->input('payload.plainToken'),
                    'encryptedToken' => $encryptedToken,
                ]);
            }
            $this->zoomWebhookService->handleEvent($request->all());
          return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), [], 200);
        }catch (\Exception $exception){
          return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }
    }
}

<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Controller;
use App\Models\ConsultationVideoRequest;
use App\Services\api\ZoomMeetingService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
        try {
            $this->zoomWebhookService->handleEvent($request->all());
          return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), [], 200);
        }catch (\Exception $exception){
          return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }
    }
}

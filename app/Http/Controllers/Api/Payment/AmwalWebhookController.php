<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Api\Payment\AmwalWebhookService;
use Illuminate\Http\Request;

class AmwalWebhookController extends Controller
{
    protected AmwalWebhookService $service;

    public function __construct(AmwalWebhookService $service)
    {
        $this->service = $service;
    }

    public function handle(Request $request)
    {
        $this->service->handleWebhook($request->all());

        return response()->json(['message' => 'Webhook processed successfully']);
    }

}

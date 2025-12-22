<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\GatewayPayment;
use App\Services\api\AmwalWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

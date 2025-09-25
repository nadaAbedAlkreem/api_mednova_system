<?php

namespace App\Services\Omnix;


use App\Models\Customer;
use App\Models\OmnixLog;
use App\Models\Order;

class OmnixLogService
{
    public function record(Customer $customer,Order $order = null, string $event, string $status, $response): void
    {
        OmnixLog::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'event'       => $event,
            'status'      => $status,
            'response'    => is_string($response) ? $response : json_encode($response),
        ]);
    }
}

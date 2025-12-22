<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\GatewayPayment;
use App\Models\Rating;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;
use Illuminate\Support\Facades\DB;

class AmwalWebhookService
{

    public function handleWebhook(array $payload)
    {
        DB::transaction(function () use ($payload) {

            $gatewayPayment = GatewayPayment::where(
                'gateway_transaction_id',
                $payload['transaction_id']
            )->firstOrFail();

            if ($payload['status'] !== 'success') {
                $gatewayPayment->update(['status' => 'failed']);
                return;
            }

            // 1️⃣ Update gateway payment
            $gatewayPayment->update([
                'status' => 'captured',
                'response_code' => $payload['code'] ?? null,
                'response_message' => $payload['message'] ?? null,
                'payload' => $payload,
            ]);

            $transaction = $gatewayPayment->transaction;
            $wallet = $transaction->wallet;

            // 2️⃣ Update transaction (amount control)
            $transaction->update(['status' => 'available']);

            // 3️⃣ Update wallet balance
            $wallet->increment('available_balance', $transaction->net_amount);

            // 4️⃣ Save card / bank if exists
            // هذا يمكن أن يتم عبر Repositories
            // if($gatewayPayment->payment_method === 'card'){ ... }
            // if($gatewayPayment->payment_method === 'bank'){ ... }
        });
    }

}

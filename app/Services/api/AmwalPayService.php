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

class AmwalPayService
{


    public function createCheckoutSession($payment,  $user): object
    {
        // placeholder – يعتمد على Docs الرسمية
        return (object)[
            'transaction_id' => 'AMW123456',
            'reference' => 'INV-1001',
            'checkout_url' => 'https://checkout.amwalpay.om/...',
            'raw' => [],
        ];
    }



}

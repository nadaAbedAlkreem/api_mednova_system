<?php
namespace App\Services\Api\Payment;

use App\Enums\AccountStatus;
use App\Enums\StatusType;
use App\Events\CustomerApprovalStatusChanged;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\Customer;
use App\Repositories\IAccountReviewRepositories;
use App\Repositories\ICustomerRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaymentFeeCalculator
{
    private const FEES = [
        'domestic'      => 0.009, // 0.9%  - داخل البلد
        'international' => 0.018, // 1.8%  - خارج البلد
    ];
    public static function calculateTotal(float $consultationPrice, string $cardType = 'domestic'): array
    {
        $feeRate   = self::FEES[$cardType] ?? self::FEES['domestic'];
        $feeAmount = round($consultationPrice * $feeRate, 3);
        $total     = round($consultationPrice + $feeAmount, 3);


        return [
            'consultation_price'      => $consultationPrice,
            'gateway_commission_rate'   => $feeRate * 100,   // 0.9 (للعرض كنسبة مئوية)
            'gateway_commission_amount' => $feeAmount,        // المبلغ الفعلي
            'net_amount'              => $total,
            'currency'                => 'OMR',
        ];
    }




}

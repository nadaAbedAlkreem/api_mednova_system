<?php

namespace App\Services\Api\Payment;

use App\Enums\AccountStatus;
use App\Enums\CardType;
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
//    private const FEES = [
//        'domestic'      => 0.009, // 0.9%  - داخل البلد
//        'international' => 0.018, // 1.8%  - خارج البلد
//    ];
    public static function calculateTotal(
        float  $consultationPrice,
        string $cardType = CardType::DOMESTIC->value
    ): array
    {
        $platformRate = config('amwal.platform_commission.default_rate');
        $gateFees = $cardType === CardType::DOMESTIC->value
            ? config('amwal.gateway_fees.domestic')
            : config('amwal.gateway_fees.international');

        Log::info('fee Rate gateway: ' . $gateFees);

        // رسوم بوابة الدفع التي يدفعها المريض
        $gatewayFeeAmount = round($consultationPrice * $gateFees, 3);

        // إجمالي ما يدفعه المريض = سعر الاستشارة + رسوم البوابة
        $grossAmount = round($consultationPrice + $gatewayFeeAmount, 3);

        // عمولة المنصة تُحسب من سعر الاستشارة نفسه
        $platformCommissionAmount = round($consultationPrice * $platformRate, 3);

        // ربح المستشار = سعر الاستشارة - عمولة المنصة
        $consultantEarningAmount = round($consultationPrice - $platformCommissionAmount, 3);

        return [
            'consultation_price' => round($consultationPrice, 3),
            'gateway_commission_rate' => round($gateFees * 100, 2),
            'gateway_commission_amount' => $gatewayFeeAmount,
            'gross_amount' => $grossAmount,
            'net_received_amount' => round($consultationPrice, 3),
            'currency' => config('amwal.currency_en'),
            'platform_commission_rate' => round($platformRate * 100, 2),
            'platform_commission_amount' => $platformCommissionAmount,
            'consultant_earning_amount' => $consultantEarningAmount,
        ];
   }

}

<?php

namespace App\Services\Api\Payment;
class FinancialOperationFactory
{
    public static function make(string $type): FinancialOperationInterface {

        return match ($type) {
//            'course_purchase' => app(CoursePurchaseService::class),
            'wallet_top_up'    => app(WalletTopUpService::class),
//            'withdraw'        => app(WithdrawService::class),
//            'refund'          => app(RefundService::class),
        };
    }
}

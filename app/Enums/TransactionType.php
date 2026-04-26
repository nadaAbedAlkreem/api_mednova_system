<?php

namespace App\Enums;

enum TransactionType : string
{
    case PAYMENT_RECORD = 'payment_record';
    case CONSULTATION_CREDIT = 'consultation_credit';
    case WITHDRAWAL = 'withdrawal';
    case ADJUSTMENT = 'adjustment';
    case CONSULTATION_HOLD = 'consultation_hold';
    case CONSULTATION_RELEASE = 'consultation_release';
    case DISPUTE_FREEZE = 'dispute_freeze';
    case DISPUTE_RELEASE = 'dispute_release';
    case WALLET_TOP_UP = 'wallet_top_up';
    case SUBSCRIPTION_CHARGE = 'subscription_charge';
    case PLATFORM_FEE = 'platform_fee';
    case REFUND = 'refund';

    public static function visibleForConsultant(): array
    {
        return [
            self::CONSULTATION_CREDIT, // اضافة على محفظة المستشار عند نجاخ العملية
            self::WITHDRAWAL,// سحب المستشار لمبلغ معين
         ];
    }
}

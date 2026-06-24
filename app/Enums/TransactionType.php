<?php

namespace App\Enums;

enum TransactionType : string
{
    case PAYMENT_RECORD = 'payment_record';
    case CONSULTATION_CREDIT = 'consultation_credit';
    case WITHDRAWAL = 'withdrawal';              // kept for backward compat with pre-fix records
    case WITHDRAWAL_REQUEST = 'withdrawal_request';
    case WITHDRAWAL_TRANSFER = 'withdrawal_transfer';
    case ADJUSTMENT = 'adjustment';
    case CONSULTATION_HOLD = 'consultation_hold';
    case CONSULTATION_RELEASE = 'consultation_release';
    case DISPUTE_FREEZE = 'dispute_freeze';
    case DISPUTE_RELEASE = 'dispute_release';
    case WALLET_TOP_UP = 'wallet_top_up';
    case SUBSCRIPTION_CHARGE = 'subscription_charge';
    case PLATFORM_FEE = 'platform_fee';
    case REFUND = 'refund';
    case WITHDRAWAL_REVERSAL = 'withdrawal_reversal';

    public static function visibleForConsultant(): array
    {
        return [
            self::CONSULTATION_CREDIT,
            self::WITHDRAWAL,
            self::WITHDRAWAL_REQUEST,
            self::WITHDRAWAL_TRANSFER,
            self::WITHDRAWAL_REVERSAL,
            self::DISPUTE_FREEZE,
            self::DISPUTE_RELEASE,
        ];
    }
    public static function visibleForPatient(): array
    {
        return [
            self::REFUND,
            self::DISPUTE_FREEZE,
            self::DISPUTE_RELEASE,
            self::WITHDRAWAL,
            self::WITHDRAWAL_REQUEST,
            self::WITHDRAWAL_TRANSFER,
            self::WITHDRAWAL_REVERSAL,
        ];
    }
}


<?php

namespace App\Enums;

enum FinancialStatus: string
{
    case UNPAID = 'unpaid';
    case HELD = 'held';
    case WITHDRAWABLE = 'withdrawable';
    case REVIEW_WINDOW = 'review_window';
    case WITHDRAWN = 'withdrawn';
    case REFUNDED = 'refunded';
    case REFUNDED_INTERNAL = 'refunded_internal';
    case FROZEN = 'frozen';
    case PAYMENT_SUSPENDED  = 'payment_suspended';




}

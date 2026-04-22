<?php

namespace App\Enums;

enum PaymentMethodType : String
{
    case METHOD_CARD      = 'card';
    case METHOD_APPLE_PAY = 'apple_pay';
    case METHOD_BANK      = 'bank';
}

<?php

namespace App\Enums;

enum GatewayPaymentStatus : String
{
    case INITIATED = 'initiated';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}

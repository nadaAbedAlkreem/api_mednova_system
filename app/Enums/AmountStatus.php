<?php

namespace App\Enums;

enum AmountStatus :String
{
    case STATUS_PENDING   = 'pending';
    case STATUS_AVAILABLE = 'available';
    case  STATUS_FROZEN    = 'frozen';
}

<?php

namespace App\Enums;

enum ConsultationStatus : string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

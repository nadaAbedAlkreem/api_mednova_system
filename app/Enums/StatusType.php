<?php

namespace App\Enums;

enum StatusType : string
{
    case  APPROVED = 'approved';
    case  PENDING = 'pending';
    case  REJECTED = 'rejected';
}


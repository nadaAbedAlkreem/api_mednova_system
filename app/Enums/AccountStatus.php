<?php

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
    case UNDER_REVIEW = 'under_review';
}

<?php

namespace App\Exceptions;

use Exception;

class ConsultantWalletNotFoundException extends Exception
{
    public function __construct(int $consultantId)
    {
        parent::__construct(
            "Consultant wallet not found for owner ID: {$consultantId}"
        );
    }
}

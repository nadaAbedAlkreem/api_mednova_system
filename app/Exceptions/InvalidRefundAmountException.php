<?php

namespace App\Exceptions;

use Exception;

class InvalidRefundAmountException extends Exception
{
    public function __construct(int $consultationId, mixed $amount)
    {
        parent::__construct(
            "Invalid refund amount for consultation ID: {$consultationId}. Got: " . var_export($amount, true)
        );
    }
}

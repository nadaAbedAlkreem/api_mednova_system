<?php

namespace App\Exceptions;

use Exception;

class InsufficientWalletBalanceException extends Exception
{
    public function __construct(int $ownerId, float $requiredAmount)
    {
        parent::__construct(
            "Insufficient pending balance for owner ID: {$ownerId}. Required: {$requiredAmount}"
        );
    }

}

<?php

namespace App\Exceptions;

use Exception;

class GatewayException extends Exception
{
    public function __construct($message = "'Payment gateway error. Please try again", $code = 400)
    {
        parent::__construct($message, $code);
    }
}

<?php

namespace App\Exceptions;

use Exception;

class ConsultationNotPayableException extends Exception
{
    public function __construct($message = "Consultation is not payable", $code = 400)
    {
        parent::__construct($message, $code);
    }
}

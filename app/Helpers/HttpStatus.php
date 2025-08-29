<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class HttpStatus
{
    private static $HttpStatus = [
        "429" => "TooManyRequest."  ,
        "404" => "NotFound."  ,
        "200" => "Ok."  ,
        "201" => "successfully created." ,
        "202" => "successfully " ,
        "204" => "No Content"  ,
        "400" => "Not Found ."  ,
        "405" => "Method Not Allowed . "    ,
        "422" => "Unprocessable Entity"  ,
        "500" => "Internal Server Error"  ,
        "503" => "Service Unavailable . "  ,
        "401" => "Invalid credentials . "  ,
        "403" => "Forbidden error"  ,
    ];

    public static function getHttpStatus($status)
    {
        return self::$HttpStatus[$status]  ;
    }
}

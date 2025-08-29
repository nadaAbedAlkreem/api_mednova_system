<?php

namespace App\Traits;

use App\Helpers\Messages;
use App\Helpers\HttpStatus;

trait ResponseTrait
{
    /**
     * Generate a success response with a message.
     *
     * @param string $messageCode
     * @param mixed $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($messageCode,  $data = [], $statusCode = 200  , $lang = 'ar')
    {
        $message = Messages::getMessage($messageCode   , $lang);
        $status = HttpStatus::getHttpStatus($statusCode);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status' => $status
        ], $statusCode);
    }

    /**
     * Generate an error response with a message.
     *
     * @param string $messageCode
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($messageCode  ,   $data = [], $statusCode = 400 , $lang = 'ar')
    {
        $message = Messages::getMessage($messageCode , $lang);
        $status = HttpStatus::getHttpStatus($statusCode);

        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'status' => $status
        ], $statusCode);
    }
}

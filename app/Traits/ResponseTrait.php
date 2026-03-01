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
    public function successResponse($messageCode,  $data = [],  $statusCode = 200  , $pagination = null, $lang = 'ar'): \Illuminate\Http\JsonResponse
    {
//        $message = Messages::getMessage($messageCode   , $lang);
        $status = HttpStatus::getHttpStatus($statusCode);

        return response()->json([
            'success' => true,
            'message' => $messageCode,
            'data' => $data,
            'pagination' => $pagination,
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
//        $message = Messages::getMessage($messageCode , $lang);
        $status = HttpStatus::getHttpStatus($statusCode);

        return response()->json([
            'success' => false,
            'message' => $messageCode,
            'data' => $data,
            'status' => $status
        ], $statusCode);
    }
}

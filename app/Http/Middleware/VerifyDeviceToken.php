<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
//        $authHeader = $request->header('Authorization');
//
//        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
//            return response()->json(['message' => 'Missing or invalid token'], 401);
//        }
//
//        $token = substr($authHeader, 7);
//
//        $device = Device::where('device_token', $token)->first();
//
//        if (!$device) {
//            return response()->json(['message' => 'Invalid device token'], 403);
//        }
//
//        // تمرير الجهاز إلى الطلب
//        $request->merge(['device' => $device]);

        return $next($request);
    }
}

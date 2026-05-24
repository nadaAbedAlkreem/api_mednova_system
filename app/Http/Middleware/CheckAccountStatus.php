<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
        public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        if ($user && (
                $user['account_status'] === 'inactive' ||
                $user['approval_status'] === 'pending')) {
            return response()->json([
                'success' => false,
                'message' => 'Your account must be fully completed and approved by management to perform this action.',
                'status' => 'FORBIDDEN'
            ], 403);
        }

        return $next($request);
    }
}

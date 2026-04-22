<?php

namespace App\Http\Middleware;

use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountType
{
    use ResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {

        $user = $request->user('api');
        // Check account type
        if (! in_array($user->type_account, $allowedTypes, true)) {
            return $this->errorResponse(
                __('messages.UNAUTHORISED_ACCOUNT_TYPE'),
                [
                    'expected_types' => $allowedTypes,
                    'your_type'      => $user->type_account,
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Check account is active
        if ($user->account_status !== 'active') {
            return $this->errorResponse(
                __('messages.ACCOUNT_NOT_ACTIVE'),
                [
                    'status' => $user->account_status,
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        // Consultants must also be approved
        $consultantTypes = ['therapist', 'rehabilitation_center'];
        if (in_array($user->type_account, $consultantTypes, true) && $user->approval_status !== 'approved') {
            return $this->errorResponse(
                __('messages.ACCOUNT_PENDING_APPROVAL'),
                [],
                Response::HTTP_FORBIDDEN,
                app()->getLocale()
            );
        }

        return $next($request);
    }
}

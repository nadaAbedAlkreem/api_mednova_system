<?php

namespace App\Http\Middleware;

use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate
{
    use ResponseTrait  ;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */

    public function handle(Request $request, Closure $next, string ...$guards): ?Response
    {
         $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                 Auth::shouldUse($guard);
                return $next($request);
            }
        }

         if ($request->expectsJson() || $request->is('api/*')) {
             return $this->errorResponse(
                 'UNAUTHORISED',
                 [],
                 401,
                 app()->getLocale()
             );
        }

         if ($request->is('admin') || $request->is('admin/*')) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }

}

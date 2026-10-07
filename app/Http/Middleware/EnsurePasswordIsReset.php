<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsReset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $isResetRoute = $request->routeIs('password.force_reset', 'password.force_reset.update');
            $isLogoutRoute = $request->routeIs('logout');

            if ($user->must_reset_password) {
                if (! $isResetRoute && ! $isLogoutRoute) {
                    return redirect()->route('password.force_reset')
                        ->with('warning', 'Action Required: You are using a temporary password. You must set a new permanent password to access the system.');
                }
            } elseif ($isResetRoute) {
                return redirect()->route('executors.index');
            }
        }

        return $next($request);
    }
}

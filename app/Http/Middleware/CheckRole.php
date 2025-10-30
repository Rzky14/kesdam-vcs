<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * 
 * Checks if the authenticated user has any of the required roles.
 * Usage: Route::middleware('role:admin_sistem,pimpinan')
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to access this page.');
        }

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'You do not have the required role to access this page.');
        }

        return $next($request);
    }
}

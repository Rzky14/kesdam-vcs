<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPermission Middleware
 *
 * Memeriksa apakah pengguna yang terautentikasi memiliki izin yang diperlukan.
 * Penggunaan: Route::middleware('permission:create_users')
 */
class CheckPermission
{
    /**
     * Menangani permintaan masuk.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk mengakses halaman ini.');
        }

        if (!$request->user()->hasPermission($permission)) {
            abort(403, 'Anda tidak memiliki izin yang diperlukan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

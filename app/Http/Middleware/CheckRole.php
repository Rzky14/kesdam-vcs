<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 *
 * Memeriksa apakah pengguna yang terautentikasi memiliki salah satu peran yang diperlukan.
 * Penggunaan: Route::middleware('role:admin_sistem,pimpinan')
 */
class CheckRole
{
    /**
     * Menangani permintaan masuk.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login untuk mengakses halaman ini.');
        }

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki peran yang diperlukan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

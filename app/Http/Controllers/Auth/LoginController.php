<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            
            // Update last login timestamp
            if ($user instanceof \App\Models\User) {
                $user->last_login_at = now();
                $user->save();
            }

            // Log successful login
            AuditLog::log(
                event: 'login',
                model: $user,
                description: 'User logged in successfully'
            );

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Login berhasil! Selamat datang, ' . $user->name);
        }

        // Log failed login attempt
        AuditLog::create([
            'user_id' => null,
            'event' => 'login_failed',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => [],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => 'Failed login attempt for email: ' . $request->email,
        ]);

        throw ValidationException::withMessages([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * Show dashboard.
     */
    public function dashboard()
    {
        return view('dashboard');
    }
}




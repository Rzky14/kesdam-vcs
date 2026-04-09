<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', 'unique:users'],
            'rank' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'nrp' => $validated['nrp'],
            'rank' => $validated['rank'],
            'position' => $validated['position'],
            'unit' => $validated['unit'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Assign default role (Batih)
        $user->berikanPeran('batih');

        // Log registration
        AuditLog::log(
            event: 'user_registered',
            model: $user,
            description: 'New user registered: ' . $user->name . ' (NRP: ' . $user->nrp . ')'
        );

        // Fire registered event
        event(new Registered($user));

        // Auto login
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Registrasi berhasil! Selamat datang, ' . $user->name);
    }
}




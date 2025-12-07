<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * ProfileController
 * 
 * Handle user profile management for authenticated users.
 * Users can view and update their own profile and password.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('roles.permissions');
        
        // Get recent audit logs for this user's actions
        $recentActivities = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('profile.show', compact('user', 'recentActivities'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'rank' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $user->toArray();

        // Update profile
        $user->update($validated);

        // Log profile update
        AuditLog::log(
            event: 'profile_updated',
            model: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
            description: 'User updated their profile'
        );

        return redirect()->route('profile.show')
            ->with('success', 'Profile berhasil diupdate.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log password change
        AuditLog::log(
            event: 'password_changed',
            model: $user,
            description: 'User changed their password'
        );

        return redirect()->route('profile.show')
            ->with('success', 'Password berhasil diubah.');
    }
}

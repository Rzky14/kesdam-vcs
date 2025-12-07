<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * UserController
 * 
 * Handle CRUD operations for user management.
 * Only accessible by users with 'users.view', 'users.create', 'users.edit', 'users.delete' permissions.
 */
class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Authorization check
        if (!$user->hasPermission('view_users')) {
            abort(403, 'Unauthorized action.');
        }

        $query = User::with('roles');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nrp', 'like', "%{$search}%")
                  ->orWhere('rank', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Authorization check
        if (!$user->hasPermission('create_users')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Authorization check
        if (!$authUser->hasPermission('create_users')) {
            abort(403, 'Unauthorized action.');
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
            'is_active' => ['boolean'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,name'],
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
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Assign roles
        foreach ($validated['roles'] as $roleName) {
            $user->assignRole($roleName);
        }

        // Log creation
        AuditLog::log(
            event: 'user_created',
            model: $user,
            newValues: $user->toArray(),
            description: 'User created by ' . $authUser->name
        );

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Authorization check
        if (!$authUser->hasPermission('view_users')) {
            abort(403, 'Unauthorized action.');
        }

        $user->load('roles.permissions');
        
        // Get audit logs for this user
        $auditLogs = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('users.show', compact('user', 'auditLogs'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Authorization check
        if (!$authUser->hasPermission('edit_users')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Authorization check
        if (!$authUser->hasPermission('edit_users')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'rank' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $oldValues = $user->toArray();

        // Update user
        $user->update([
            'name' => $validated['name'],
            'nrp' => $validated['nrp'],
            'rank' => $validated['rank'],
            'position' => $validated['position'],
            'unit' => $validated['unit'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Sync roles
        $user->roles()->detach();
        foreach ($validated['roles'] as $roleName) {
            $user->assignRole($roleName);
        }

        // Log update
        AuditLog::log(
            event: 'user_updated',
            model: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
            description: 'User updated by ' . $authUser->name
        );

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diupdate.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Authorization check
        if (!$authUser->hasPermission('delete_users')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        // Log deletion before actually deleting
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'user_deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $user->toArray(),
            'new_values' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "User {$userName} ({$userEmail}) deleted by " . $authUser->name,
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}

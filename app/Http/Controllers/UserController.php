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
 * Mengelola operasi CRUD manajemen pengguna.
 * Hanya dapat diakses oleh pengguna dengan izin 'view_users', 'create_users', 'edit_users', 'delete_users'.
 */
class UserController extends Controller
{
    /**
     * Tampilkan daftar pengguna.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('view_users')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $query = User::with('roles');

        // Fitur pencarian
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

        // Filter berdasarkan peran
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Tampilkan formulir pembuatan pengguna baru.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('create_users')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Simpan pengguna baru.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('create_users')) {
            abort(403, 'Aksi tidak diizinkan.');
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

        // Buat pengguna
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

        // Tetapkan peran
        foreach ($validated['roles'] as $roleName) {
            $user->berikanPeran($roleName);
        }

        // Catat pembuatan
        AuditLog::log(
            event: 'user_created',
            model: $user,
            newValues: $user->toArray(),
            description: 'Pengguna dibuat oleh ' . $authUser->name
        );

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berhasil dibuat.');
    }

    /**
     * Tampilkan detail pengguna.
     */
    public function show(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('view_users')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $user->load('roles.permissions');
        
        // Ambil log audit untuk pengguna ini
        $auditLogs = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('users.show', compact('user', 'auditLogs'));
    }

    /**
     * Tampilkan formulir untuk mengedit pengguna.
     */
    public function edit(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('edit_users')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Perbarui pengguna yang dipilih.
     */
    public function update(Request $request, User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('edit_users')) {
            abort(403, 'Aksi tidak diizinkan.');
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

        // Perbarui pengguna
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

        // Perbarui password jika disediakan
        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Sinkronisasi peran
        $user->roles()->detach();
        foreach ($validated['roles'] as $roleName) {
            $user->berikanPeran($roleName);
        }

        // Catat pembaruan
        AuditLog::log(
            event: 'user_updated',
            model: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
            description: 'Pengguna diperbarui oleh ' . $authUser->name
        );

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('delete_users')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        // Cegah penghapusan diri sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        // Catat penghapusan sebelum eksekusi delete
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'user_deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $user->toArray(),
            'new_values' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "Pengguna {$userName} ({$userEmail}) dihapus oleh " . $authUser->name,
        ]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}




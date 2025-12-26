<?php

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy
 * 
 * Menentukan otorisasi untuk operasi pada model User (Pengguna).
 */
class UserPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar pengguna.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_users');
    }

    /**
     * Menentukan apakah pengguna dapat melihat pengguna tertentu.
     */
    public function view(User $user, User $model): bool
    {
        // Pengguna dapat melihat diri sendiri atau jika memiliki izin
        return $user->id === $model->id || $user->hasPermission('view_users');
    }

    /**
     * Menentukan apakah pengguna dapat membuat pengguna baru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_users');
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui pengguna.
     */
    public function update(User $user, User $model): bool
    {
        // Pengguna dapat memperbarui diri sendiri atau jika memiliki izin
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('update_users');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus pengguna.
     */
    public function delete(User $user, User $model): bool
    {
        // Tidak dapat menghapus diri sendiri
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('delete_users');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan pengguna.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('admin_sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus permanen pengguna.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('admin_sistem');
    }
}

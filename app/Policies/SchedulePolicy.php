<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

/**
 * SchedulePolicy
 * 
 * Menentukan otorisasi untuk operasi pada model Schedule (Jadwal).
 */
class SchedulePolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar jadwal.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_schedules');
    }

    /**
     * Menentukan apakah pengguna dapat melihat jadwal.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->hasPermission('view_schedules');
    }

    /**
     * Menentukan apakah pengguna dapat membuat jadwal.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_schedules');
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui jadwal.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        // Admin dapat memperbarui jadwal apapun
        if ($user->hasRole('admin_sistem')) {
            return true;
        }

        // Pengguna hanya dapat memperbarui jadwal mereka sendiri jika memiliki izin
        if ($schedule->created_by === $user->id && $user->hasPermission('update_schedules')) {
            return true;
        }

        // Pimpinan dan Kasi/Kaur dapat memperbarui jadwal untuk persetujuan
        if ($user->hasAnyRole(['pimpinan', 'kasi_kaur']) && $user->hasPermission('approve_schedules')) {
            return true;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus jadwal.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        // Admin dapat menghapus jadwal draft apapun
        if ($user->hasRole('admin_sistem')) {
            return $schedule->status === 'draft';
        }

        // Pengguna hanya dapat menghapus jadwal draft mereka sendiri
        return $schedule->created_by === $user->id 
            && $schedule->status === 'draft'
            && $user->hasPermission('delete_schedules');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan jadwal.
     */
    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin_sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus permanen jadwal.
     */
    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin_sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menyetujui jadwal.
     */
    public function approve(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['pimpinan', 'kasi_kaur']) 
            && $user->hasPermission('approve_schedules')
            && $schedule->status === 'pending';
    }

    /**
     * Menentukan apakah pengguna dapat mempublikasikan jadwal.
     */
    public function publish(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['pimpinan', 'kasi_kaur']) 
            && $user->hasPermission('publish_schedules')
            && $schedule->status === 'approved';
    }
}




<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_schedules');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->hasPermission('view_schedules');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_schedules');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        // Admin can update any schedule
        if ($user->hasRole('admin_sistem')) {
            return true;
        }

        // Users can only update their own schedules or if they have permission
        if ($schedule->created_by === $user->id && $user->hasPermission('update_schedules')) {
            return true;
        }

        // Pimpinan and Kasi/Kaur can update schedules for approval
        if ($user->hasAnyRole(['pimpinan', 'kasi_kaur']) && $user->hasPermission('approve_schedules')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        // Admin can delete any draft schedule
        if ($user->hasRole('admin_sistem')) {
            return $schedule->status === 'draft';
        }

        // Users can only delete their own draft schedules
        return $schedule->created_by === $user->id 
            && $schedule->status === 'draft'
            && $user->hasPermission('delete_schedules');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin_sistem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin_sistem');
    }

    /**
     * Determine whether the user can approve the schedule.
     */
    public function approve(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['pimpinan', 'kasi_kaur']) 
            && $user->hasPermission('approve_schedules')
            && $schedule->status === 'pending';
    }

    /**
     * Determine whether the user can publish the schedule.
     */
    public function publish(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['pimpinan', 'kasi_kaur']) 
            && $user->hasPermission('publish_schedules')
            && $schedule->status === 'approved';
    }
}

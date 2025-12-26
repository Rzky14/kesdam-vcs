<?php

namespace App\Policies;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * DokumenPolicy
 * 
 * Menentukan otorisasi untuk operasi pada model Dokumen.
 * Mengikuti SOLID Principles dan Laravel Policy pattern.
 */
class DokumenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view documents list
        return $user->hasPermission('view_documents');
    }

    /**
     * Menentukan apakah pengguna dapat melihat model.
     */
    public function view(User $user, Dokumen $dokumen): bool
    {
        // Users can view documents if they have permission
        // Classified documents require special permission
        if ($dokumen->adalahRahasia()) {
            return $user->hasPermission('view_classified_documents');
        }

        return $user->hasPermission('view_documents');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Batih/Staf and above can create documents
        return $user->hasPermission('create_documents');
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui model.
     */
    public function update(User $user, Dokumen $dokumen): bool
    {
        // Admin can update any draft or rejected document
        if ($user->hasRole('Admin Sistem')) {
            return in_array($dokumen->status, ['draft', 'rejected']);
        }

        // Users can only update their own draft or rejected documents
        if ($dokumen->created_by === $user->id && in_array($dokumen->status, ['draft', 'rejected'])) {
            return $user->hasPermission('edit_documents'); // Fixed: was 'update_documents'
        }

        // Pimpinan and Kasi/Kaur can update draft or rejected documents for workflow purposes
        if ($user->hasAnyRole(['Pimpinan/Pejabat Tinggi', 'Kasi/Kaur']) && in_array($dokumen->status, ['draft', 'rejected'])) {
            return $user->hasPermission('approve_documents');
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus model.
     */
    public function delete(User $user, Dokumen $dokumen): bool
    {
        // Admin can delete any draft document
        if ($user->hasRole('Admin Sistem')) {
            return $dokumen->adalahDraf();
        }

        // Users can only delete their own draft documents
        return $dokumen->created_by === $user->id 
            && $dokumen->adalahDraf() 
            && $user->hasPermission('delete_documents');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan model.
     */
    public function restore(User $user, Dokumen $dokumen): bool
    {
        // Only admin can restore deleted documents
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus permanen model.
     */
    public function forceDelete(User $user, Dokumen $dokumen): bool
    {
        // Only admin can permanently delete documents
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menyetujui dokumen.
     * Pengguna harus memiliki role yang sesuai untuk level persetujuan saat ini.
     */
    public function approve(User $user, Dokumen $dokumen): bool
    {
        // Document must be pending approval
        if (!$dokumen->adalahMenungguPersetujuan()) {
            return false;
        }

        // User must have approve permission
        if (!$user->hasPermission('approve_documents')) {
            return false;
        }

        // Check if user has the required role for current approval level
        $currentLevel = $dokumen->ambilLevelPersetujuanSaatIni();
        
        return match($currentLevel) {
            0 => $user->hasRole('kaur'),        // Level 1: KAUR
            1 => $user->hasRole('kasi'),        // Level 2: KASI  
            2 => $user->hasRole('pimpinan'),    // Level 3: PIMPINAN
            default => false,
        };
    }

    /**
     * Menentukan apakah pengguna dapat menolak dokumen.
     */
    public function reject(User $user, Dokumen $dokumen): bool
    {
        // Same authorization as approve
        return $this->approve($user, $dokumen);
    }

    /**
     * Menentukan apakah pengguna dapat meminta koreksi.
     */
    public function requestCorrection(User $user, Dokumen $dokumen): bool
    {
        // Same authorization as approve
        return $this->approve($user, $dokumen);
    }

    /**
     * Menentukan apakah pengguna dapat mengarsipkan dokumen.
     */
    public function archive(User $user, Dokumen $dokumen): bool
    {
        // Users with archive permission can archive approved documents
        return $user->hasPermission('archive_documents') && $dokumen->adalahDisetujui();
    }
}

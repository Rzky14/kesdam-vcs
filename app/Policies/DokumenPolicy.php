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
     * Menentukan apakah pengguna dapat melihat daftar dokumen.
     */
    public function viewAny(User $user): bool
    {
        // Semua pengguna terautentikasi dapat melihat daftar dokumen
        return $user->hasPermission('view_documents');
    }

    /**
     * Menentukan apakah pengguna dapat melihat dokumen.
     */
    public function view(User $user, Dokumen $dokumen): bool
    {
        // Pengguna dapat melihat dokumen jika memiliki izin
        // Dokumen rahasia memerlukan izin khusus
        if ($dokumen->adalahRahasia()) {
            return $user->hasPermission('view_classified_documents');
        }

        return $user->hasPermission('view_documents');
    }

    /**
     * Menentukan apakah pengguna dapat membuat dokumen.
     */
    public function create(User $user): bool
    {
        // Batih/Staf dan level di atasnya dapat membuat dokumen
        return $user->hasPermission('create_documents');
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui dokumen.
     */
    public function update(User $user, Dokumen $dokumen): bool
    {
        // Admin dapat memperbarui dokumen draft atau ditolak apapun
        if ($user->hasRole('Admin Sistem')) {
            return in_array($dokumen->status, ['draft', 'rejected']);
        }

        // Pengguna hanya dapat memperbarui dokumen draft atau ditolak miliknya sendiri
        if ($dokumen->created_by === $user->id && in_array($dokumen->status, ['draft', 'rejected'])) {
            return $user->hasPermission('edit_documents');
        }

        // Pimpinan dan Kasi/Kaur dapat memperbarui dokumen draft atau ditolak untuk keperluan alur kerja
        if ($user->hasAnyRole(['Pimpinan/Pejabat Tinggi', 'Kasi/Kaur']) && in_array($dokumen->status, ['draft', 'rejected'])) {
            return $user->hasPermission('approve_documents');
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus dokumen.
     */
    public function delete(User $user, Dokumen $dokumen): bool
    {
        // Admin dapat menghapus dokumen draft apapun
        if ($user->hasRole('Admin Sistem')) {
            return $dokumen->adalahDraf();
        }

        // Pengguna hanya dapat menghapus dokumen draft miliknya sendiri
        return $dokumen->created_by === $user->id 
            && $dokumen->adalahDraf() 
            && $user->hasPermission('delete_documents');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan dokumen.
     */
    public function restore(User $user, Dokumen $dokumen): bool
    {
        // Hanya admin yang dapat memulihkan dokumen yang dihapus
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus permanen dokumen.
     */
    public function forceDelete(User $user, Dokumen $dokumen): bool
    {
        // Hanya admin yang dapat menghapus permanen dokumen
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menyetujui dokumen.
     * Pengguna harus memiliki role yang sesuai untuk level persetujuan saat ini.
     */
    public function approve(User $user, Dokumen $dokumen): bool
    {
        // Dokumen harus dalam status menunggu persetujuan
        if (!$dokumen->adalahMenungguPersetujuan()) {
            return false;
        }

        // Pengguna harus memiliki izin menyetujui
        if (!$user->hasPermission('approve_documents')) {
            return false;
        }

        // Periksa apakah pengguna memiliki peran yang sesuai untuk level persetujuan saat ini
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
        // Otorisasi sama dengan menyetujui
        return $this->approve($user, $dokumen);
    }

    /**
     * Menentukan apakah pengguna dapat meminta koreksi.
     */
    public function requestCorrection(User $user, Dokumen $dokumen): bool
    {
        // Otorisasi sama dengan menyetujui
        return $this->approve($user, $dokumen);
    }

    /**
     * Menentukan apakah pengguna dapat mengarsipkan dokumen.
     */
    public function archive(User $user, Dokumen $dokumen): bool
    {
        // Pengguna dengan izin arsip dapat mengarsipkan dokumen yang sudah disetujui
        return $user->hasPermission('archive_documents') && $dokumen->adalahDisetujui();
    }
}

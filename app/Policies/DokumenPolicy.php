<?php

namespace App\Policies;

use App\Models\Surat;
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
    public function view(User $user, Surat $surat): bool
    {
        // Pengguna dapat melihat dokumen jika memiliki izin
        // Dokumen rahasia memerlukan izin khusus
        if ($surat->adalahRahasia()) {
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
    public function update(User $user, Surat $surat): bool
    {
        // Admin dapat memperbarui dokumen draft atau ditolak apapun
        if ($user->hasRole('Admin Sistem')) {
            return in_array($surat->status, ['draft', 'rejected']);
        }

        // Pengguna hanya dapat memperbarui dokumen draft atau ditolak miliknya sendiri
        if ($surat->created_by === $user->id && in_array($surat->status, ['draft', 'rejected'])) {
            return $user->hasPermission('edit_documents');
        }

        // Pimpinan dan Kasi/Kaur dapat memperbarui dokumen draft atau ditolak untuk keperluan alur kerja
        if ($user->hasAnyRole(['Pimpinan/Pejabat Tinggi', 'Kasi/Kaur']) && in_array($surat->status, ['draft', 'rejected'])) {
            return $user->hasPermission('approve_documents');
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus dokumen.
     */
    public function delete(User $user, Surat $surat): bool
    {
        // Admin dapat menghapus dokumen draft apapun
        if ($user->hasRole('Admin Sistem')) {
            return $surat->adalahDraf();
        }

        // Pengguna hanya dapat menghapus dokumen draft miliknya sendiri
        return $surat->created_by === $user->id 
            && $surat->adalahDraf() 
            && $user->hasPermission('delete_documents');
    }

    /**
     * Menentukan apakah pengguna dapat memulihkan dokumen.
     */
    public function restore(User $user, Surat $surat): bool
    {
        // Hanya admin yang dapat memulihkan dokumen yang dihapus
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus permanen dokumen.
     */
    public function forceDelete(User $user, Surat $surat): bool
    {
        // Hanya admin yang dapat menghapus permanen dokumen
        return $user->hasRole('Admin Sistem');
    }

    /**
     * Menentukan apakah pengguna dapat menyetujui dokumen.
     * Pengguna harus memiliki role yang sesuai untuk level persetujuan saat ini.
     */
    public function approve(User $user, Surat $surat): bool
    {
        // Dokumen harus dalam status menunggu persetujuan
        if (!$surat->adalahMenungguPersetujuan()) {
            return false;
        }

        // Pengguna harus memiliki izin menyetujui
        if (!$user->hasPermission('approve_documents')) {
            return false;
        }

        // Periksa apakah pengguna memiliki peran yang sesuai untuk level persetujuan saat ini
        $currentLevel = $surat->ambilLevelPersetujuanSaatIni();
        
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
    public function reject(User $user, Surat $surat): bool
    {
        // Otorisasi sama dengan menyetujui
        return $this->approve($user, $surat);
    }

    /**
     * Menentukan apakah pengguna dapat meminta koreksi.
     */
    public function requestCorrection(User $user, Surat $surat): bool
    {
        // Otorisasi sama dengan menyetujui
        return $this->approve($user, $surat);
    }

    /**
     * Menentukan apakah pengguna dapat mengarsipkan dokumen.
     */
    public function archive(User $user, Surat $surat): bool
    {
        // Pengguna dengan izin arsip dapat mengarsipkan dokumen yang sudah disetujui
        return $user->hasPermission('archive_documents') && $surat->adalahDisetujui();
    }
}




<?php

namespace App\Models;

/**
 * Model Staff
 * 
 * Merepresentasikan tipe pengguna Staff.
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class Staff extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'staff';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Staff';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna staff.
     */
    public function scopeHanyaStaff($query)
    {
        return $query->where('jabatan', 'Staff');
    }

    /**
     * Method dari UML diagram - Membuat surat masuk.
     */
    public function membuatSuratMasuk(): void
    {
        // Implementasi untuk membuat surat masuk
    }

    /**
     * Mengelola tugas.
     */
    public function mengelolaTugas(): void
    {
        // Implementasi untuk mengelola tugas
    }

    /**
     * Mengajukan surat masuk.
     */
    public function mengajukanSuratMasuk(): void
    {
        // Implementasi untuk mengajukan surat masuk
    }

    /**
     * Membuat surat keluar.
     */
    public function membuatSuratKeluar(): void
    {
        // Implementasi untuk membuat surat keluar
    }

    /**
     * Mengelola lampiran surat.
     */
    public function mengelolaSuratLampiran(): void
    {
        // Implementasi untuk mengelola lampiran
    }

    /**
     * Mengirim surat untuk persetujuan.
     */
    public function mengirimSuratUntukPersetujuan(): void
    {
        // Implementasi untuk mengirim surat ke proses persetujuan
    }

    /**
     * Memeriksa proses persetujuan.
     */
    public function memeriksaProsesPesetujuan(): void
    {
        // Implementasi untuk memeriksa status proses persetujuan
    }

    /**
     * Mengatur perubahan jadwal.
     */
    public function mengaturPerubahanJadwal(): void
    {
        // Implementasi untuk mengatur perubahan jadwal
    }

    /**
     * Melihat laporan.
     */
    public function melihatLaporan(): void
    {
        // Implementasi untuk melihat laporan
    }
}




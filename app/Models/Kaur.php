<?php

namespace App\Models;

/**
 * Model Kaur
 * 
 * Merepresentasikan tipe pengguna Kaur (Kepala Urusan).
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class Kaur extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'kaur';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Kaur';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna kaur.
     */
    public function scopeHanyaKaur($query)
    {
        return $query->where('jabatan', 'Kaur');
    }

    /**
     * Mengawasi jadwal.
     */
    public function mengawassiJadwal(): void
    {
        // Implementasi untuk mengawasi jadwal
    }

    /**
     * Membuat laporan kegiatan.
     */
    public function membuatLaporanKegiatan(): void
    {
        // Implementasi untuk membuat laporan kegiatan
    }

    /**
     * Menyetujui surat keluar.
     */
    public function menyetujuiSuratKeluar(): void
    {
        // Implementasi untuk menyetujui surat keluar
    }

    /**
     * Menangani perubahan jadwal mendadak.
     */
    public function menanganiPerubahanJadwalMendadak(): void
    {
        // Implementasi untuk menangani perubahan jadwal mendadak
    }
}




<?php

namespace App\Models;

/**
 * Model Batih
 * 
 * Merepresentasikan tipe pengguna Batih.
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class Batih extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'batih';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Batih';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna batih.
     */
    public function scopeHanyaBatih($query)
    {
        return $query->where('jabatan', 'Batih');
    }

    /**
     * Menerima surat masuk.
     */
    public function menerimaSuratMasuk(): void
    {
        // Implementasi untuk menerima surat masuk
    }

    /**
     * Mengelola jadwal.
     */
    public function mengelolaJadwal(): void
    {
        // Implementasi untuk mengelola jadwal
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
     * Mengatur kebijakan jadwal.
     */
    public function mengaturanKebijakanJadwal(): void
    {
        // Implementasi untuk mengatur kebijakan jadwal
    }

    /**
     * Mengatur laporan.
     */
    public function mengaturLaporan(): void
    {
        // Implementasi untuk mengatur laporan
    }
}

<?php

namespace App\Models;

/**
 * Model Kasi
 * 
 * Merepresentasikan tipe pengguna Kasi (Kepala Seksi).
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class Kasi extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'kasi';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Kasi';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna kasi.
     */
    public function scopeHanyaKasi($query)
    {
        return $query->where('jabatan', 'Kasi');
    }

    /**
     * Mengingatkan jadwal (sesuai UML: "mengingatJadwa").
     */
    public function mengingatJadwa(): void
    {
        // Implementasi untuk mengingatkan jadwal
    }

    /**
     * Menyetujui surat.
     */
    public function menyetujuiSurat(): void
    {
        // Implementasi untuk menyetujui surat
    }

    /**
     * Memeriksa konflik jadwal pada surat.
     */
    public function memeriksaSuratKonflikasiJadwal(): void
    {
        // Implementasi untuk memeriksa konflik jadwal pada surat
    }

    /**
     * Menyetujui perubahan jadwal mendadak.
     */
    public function menyetujuiPerubahanJadwalMendadak(): void
    {
        // Implementasi untuk menyetujui perubahan jadwal mendadak
    }

    /**
     * Mengeluarkan perubahan jadwal (sesuai UML: "mengaluarPerubahanJadwal").
     */
    public function mengaluarPerubahanJadwal(): void
    {
        // Implementasi untuk mengeluarkan perubahan jadwal
    }

    /**
     * Membuat laporan bulanan.
     */
    public function membuatLaporanBulanan(): void
    {
        // Implementasi untuk membuat laporan bulanan
    }
}

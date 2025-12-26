<?php

namespace App\Models;

/**
 * Model Pimpinan
 * 
 * Merepresentasikan tipe pengguna Pimpinan (Kepemimpinan).
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class Pimpinan extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'pimpinan';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Pimpinan';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna pimpinan.
     */
    public function scopeHanyaPimpinan($query)
    {
        return $query->where('jabatan', 'Pimpinan');
    }

    /**
     * Menerima surat lengkap.
     */
    public function menerimaLengkapSurat(): void
    {
        // Implementasi untuk menerima surat lengkap
    }

    /**
     * Menyetujui surat.
     */
    public function menyetujuiSurat(): void
    {
        // Implementasi untuk menyetujui surat
    }

    /**
     * Memeriksa surat.
     */
    public function memeriksaSurat(): void
    {
        // Implementasi untuk memeriksa surat
    }
}

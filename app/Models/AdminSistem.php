<?php

namespace App\Models;

/**
 * Model AdminSistem
 * 
 * Merepresentasikan tipe pengguna Admin Sistem (Administrator Sistem).
 * Menggunakan Single Table Inheritance (STI) dari model User.
 * Sesuai dengan UML Class Diagram.
 */
class AdminSistem extends User
{
    /**
     * Tipe peran untuk model ini.
     *
     * @var string
     */
    protected $tipePeran = 'admin';

    /**
     * Method boot untuk mengatur nilai default.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->jabatan = 'Admin Sistem';
        });
    }

    /**
     * Scope untuk hanya mendapatkan pengguna admin sistem.
     */
    public function scopeHanyaAdmin($query)
    {
        return $query->where('jabatan', 'Admin Sistem');
    }

    /**
     * Mengelola pengguna.
     */
    public function mengelolaPengguna(): void
    {
        // Implementasi untuk mengelola pengguna
    }

    /**
     * Mengelola peran.
     */
    public function mengelolaPeran(): void
    {
        // Implementasi untuk mengelola peran
    }

    /**
     * Mengelola hak akses.
     */
    public function mengelolaHakAkses(): void
    {
        // Implementasi untuk mengelola hak akses
    }

    /**
     * Mengatur backup data.
     */
    public function mengaturBackupData(): void
    {
        // Implementasi untuk mengatur backup data
    }
}




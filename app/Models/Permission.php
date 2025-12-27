<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Izin (Permission)
 * 
 * Merepresentasikan izin spesifik yang dapat diberikan ke peran.
 * Contoh: buat_pengguna, edit_dokumen, setujui_dokumen, dll.
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    /**
     * Mendapatkan peran-peran yang memiliki izin ini.
     *
     * @return BelongsToMany
     */
    public function peranPeran(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Alias: roles() untuk kompatibilitas.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->peranPeran();
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Mendapatkan izin-izin berdasarkan modul.
     *
     * @param string $modul Nama modul
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function berdasarkanModul(string $modul)
    {
        return static::where('module', $modul)->get();
    }

    /**
     * Alias: byModule() untuk kompatibilitas.
     *
     * @param string $module Nama modul
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byModule(string $module)
    {
        return static::berdasarkanModul($module);
    }
}




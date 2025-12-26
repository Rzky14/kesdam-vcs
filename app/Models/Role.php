<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Role
 *
 * Representasi peran pengguna dalam sistem:
 * - admin_sistem: Akses penuh sistem
 * - pimpinan: Otoritas kepemimpinan/persetujuan
 * - kasi_kaur: Manajemen tingkat menengah
 * - batih_staf: Tingkat staf/operasional
 */
class Role extends Model
{
    use HasFactory;
    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Mendapatkan pengguna yang memiliki peran ini.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Mendapatkan izin yang diberikan ke peran ini.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Cek apakah peran memiliki izin tertentu.
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()
            ->where('name', $permissionName)
            ->exists();
    }

    /**
     * Berikan izin ke peran ini.
     *
     * @param Permission|string $permission
     * @return void
     */
    public function givePermissionTo($permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Cabut izin dari peran ini.
     *
     * @param Permission|string $permission
     * @return void
     */
    public function revokePermissionTo($permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }
}

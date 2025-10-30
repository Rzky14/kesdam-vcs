<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Model
 * 
 * Represents specific permissions that can be assigned to roles.
 * Examples: create_users, edit_documents, approve_documents, etc.
 */
class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
    ];

    /**
     * Get the roles that have this permission.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Get permissions by module.
     *
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byModule(string $module)
    {
        return static::where('module', $module)->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Setting
 * 
 * Model untuk mengelola pengaturan sistem dan preferensi pengguna.
 * 
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $type
 * @property int|null $user_id
 * @property string|null $group
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read User|null $user
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'user_id',
        'group',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope untuk filter berdasarkan tipe.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope untuk filter berdasarkan group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope untuk filter setting aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter setting user tertentu.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     */
    public static function set(string $key, mixed $value, string $type = 'system', ?int $userId = null): self
    {
        return static::updateOrCreate(
            [
                'key' => $key,
                'user_id' => $userId,
            ],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    /**
     * Check if setting is enabled.
     */
    public static function isEnabled(string $key, ?int $userId = null): bool
    {
        $query = static::where('key', $key)->where('is_active', true);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $setting = $query->first();
        
        if (!$setting) {
            return false;
        }
        
        // Jika value adalah boolean
        if (is_bool($setting->value)) {
            return $setting->value;
        }
        
        // Jika value adalah string 'true'/'false'
        if (is_string($setting->value)) {
            return strtolower($setting->value) === 'true' || $setting->value === '1';
        }
        
        return (bool) $setting->value;
    }
}




<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User
 * 
 * Merepresentasikan pengguna dalam sistem.
 * Kelas dasar untuk semua tipe pengguna (STI Pattern).
 * Sesuai dengan UML Class Diagram.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Menunjukkan apakah ID auto-increment.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe dari ID auto-increment.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Aktifkan timestamp default Laravel.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi secara massal (sesuai UML).
     *
     * @var list<string>
     */
    protected $fillable = [
        'nrp',
        'rank',
        'position',
        'unit',
        'name',
        'email',
        'phone',
        'address',
        'profile_photo',
        'is_active',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan untuk serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Dapatkan atribut yang harus di-cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Method boot untuk setup model events.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Tidak perlu auto-generate ID karena menggunakan auto_increment
    }

    /**
     * Override route key name untuk route model binding.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Dapatkan peran yang dimiliki pengguna.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Dapatkan semua izin melalui peran.
     *
     * @return \Illuminate\Support\Collection
     */
    public function permissions()
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
    }

    /**
     * Cek apakah pengguna memiliki peran tertentu.
     *
     * @param string $namaPeran
     * @return bool
     */
    public function punyaPeran(string $namaPeran): bool
    {
        return $this->roles()->where('name', $namaPeran)->exists();
    }

    /**
     * Alias for punyaPeran() - Check if user has a specific role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->punyaPeran($roleName);
    }

    /**
     * Cek apakah pengguna memiliki salah satu peran yang diberikan.
     *
     * @param array $peranArray
     * @return bool
     */
    public function punyaSalahSatuPeran(array $peranArray): bool
    {
        return $this->roles()->whereIn('name', $peranArray)->exists();
    }

    /**
     * Alias for punyaSalahSatuPeran() - Check if user has any of the given roles.
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->punyaSalahSatuPeran($roles);
    }

    /**
     * Cek apakah pengguna memiliki izin tertentu.
     *
     * @param string $namaIzin
     * @return bool
     */
    public function punyaIzin(string $namaIzin): bool
    {
        return $this->permissions()->contains('name', $namaIzin);
    }

    /**
     * Alias for punyaIzin() - Check if user has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->punyaIzin($permission);
    }

    /**
     * Cek apakah pengguna adalah admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin_sistem') 
            || $this->hasRole('admin') 
            || $this->hasRole('Admin Sistem')
            || $this->hasRole('Admin');
    }

    /**
     * Cek apakah pengguna adalah pimpinan.
     *
     * @return bool
     */
    public function isPimpinan(): bool
    {
        return $this->hasRole('Pimpinan/Pejabat Tinggi')
            || $this->hasRole('pimpinan');
    }

    /**
     * Cek apakah pengguna adalah kasi/kaur.
     *
     * @return bool
     */
    public function isKasi(): bool
    {
        return $this->hasRole('Kasi/Kaur')
            || $this->hasRole('kasi');
    }

    /**
     * Cek apakah pengguna adalah batih/staf.
     *
     * @return bool
     */
    public function isStaf(): bool
    {
        return $this->hasRole('Batih/Staf')
            || $this->hasRole('staf');
    }

    /**
     * Dapatkan semua izin untuk pengguna (melalui peran).
     *
     * @return \Illuminate\Support\Collection
     */
    public function ambilSemuaIzin()
    {
        return $this->permissions();
    }

    /**
     * Alias for ambilSemuaIzin() - Get all permissions for the user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        return $this->ambilSemuaIzin();
    }

    /**
     * Berikan peran kepada pengguna.
     *
     * @param Role|string $peran
     * @return void
     */
    public function berikanPeran($peran): void
    {
        if (is_string($peran)) {
            $peran = Role::where('name', $peran)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$peran->id]);
    }

    /**
     * Hapus peran dari pengguna.
     *
     * @param Role|string $peran
     * @return void
     */
    public function hapusPeran($peran): void
    {
        if (is_string($peran)) {
            $peran = Role::where('name', $peran)->firstOrFail();
        }

        $this->roles()->detach($peran->id);
    }

    /**
     * Dapatkan log audit untuk pengguna ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logAudit()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Dapatkan preferensi notifikasi untuk pengguna ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preferensiNotifikasi()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Cek apakah pengguna mengaktifkan tipe notifikasi in-app.
     *
     * @param string $tipe
     * @return bool
     */
    public function notifikasiInAppAktif(string $tipe): bool
    {
        $preferensi = $this->preferensiNotifikasi()
            ->where('notification_type', $tipe)
            ->first();

        return $preferensi ? $preferensi->in_app_enabled : true; // Default: aktif
    }

    /**
     * Cek apakah pengguna mengaktifkan tipe notifikasi email.
     *
     * @param string $tipe
     * @return bool
     */
    public function notifikasiEmailAktif(string $tipe): bool
    {
        $preferensi = $this->preferensiNotifikasi()
            ->where('notification_type', $tipe)
            ->first();

        return $preferensi ? $preferensi->email_enabled : false; // Default: nonaktif
    }
}




<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'action',
        'document_type',
        'classification',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mendapatkan peran yang dimiliki izin ini.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope untuk mendapatkan izin yang aktif saja.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter berdasarkan peran.
     */
    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope untuk filter berdasarkan aksi.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope untuk filter berdasarkan tipe dokumen.
     */
    public function scopeByDocumentType($query, $documentType)
    {
        return $query->where('document_type', $documentType)
                     ->orWhereNull('document_type');
    }

    /**
     * Scope untuk filter berdasarkan klasifikasi.
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('classification', $classification)
                     ->orWhereNull('classification');
    }

    /**
     * Cek apakah peran dapat melakukan aksi pada tipe dokumen dan klasifikasi.
     */
    public static function canPerform($roleId, $action, $documentType = null, $classification = null)
    {
        return self::active()
            ->where('role_id', $roleId)
            ->where('action', $action)
            ->byDocumentType($documentType)
            ->byClassification($classification)
            ->exists();
    }

    /**
     * Mendapatkan semua izin untuk sebuah peran.
     */
    public static function getPermissionsForRole($roleId)
    {
        return self::active()
            ->byRole($roleId)
            ->get()
            ->groupBy('action');
    }

    /**
     * Mendapatkan peran yang dapat melakukan aksi tertentu.
     */
    public static function getRolesForAction($action, $documentType = null, $classification = null)
    {
        return self::active()
            ->where('action', $action)
            ->byDocumentType($documentType)
            ->byClassification($classification)
            ->with('role')
            ->get()
            ->unique('role_id');
    }

    /**
     * Mendapatkan label aksi.
     */
    public function getActionLabel()
    {
        $labels = [
            'view' => 'Lihat',
            'submit' => 'Ajukan',
            'approve' => 'Setujui',
            'request_correction' => 'Minta Koreksi',
        ];
        return $labels[$this->action] ?? $this->action;
    }
}




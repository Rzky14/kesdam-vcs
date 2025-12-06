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
     * Get the role this permission belongs to
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope to get active permissions only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to filter by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by document type
     */
    public function scopeByDocumentType($query, $documentType)
    {
        return $query->where('document_type', $documentType)
                     ->orWhereNull('document_type');
    }

    /**
     * Scope to filter by classification
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('classification', $classification)
                     ->orWhereNull('classification');
    }

    /**
     * Check if role can perform action on document type and classification
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
     * Get all permissions for a role
     */
    public static function getPermissionsForRole($roleId)
    {
        return self::active()
            ->byRole($roleId)
            ->get()
            ->groupBy('action');
    }

    /**
     * Get roles that can perform specific action
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
     * Get action label
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

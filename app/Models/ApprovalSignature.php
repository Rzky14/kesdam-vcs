<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_history_id',
        'user_id',
        'signature_file_path',
        'signature_type',
        'signed_at',
        'certificate_number',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the approval history this signature belongs to
     */
    public function approvalHistory()
    {
        return $this->belongsTo(ApprovalHistory::class);
    }

    /**
     * Get the user who signed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document via approval history
     */
    public function document()
    {
        return $this->hasOneThrough(
            Document::class,
            ApprovalHistory::class,
            'id',
            'id',
            'approval_history_id',
            'document_id'
        );
    }

    /**
     * Scope to get signatures from specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get digital signatures
     */
    public function scopeDigital($query)
    {
        return $query->where('signature_type', 'digital');
    }

    /**
     * Scope to get scanned signatures
     */
    public function scopeScanned($query)
    {
        return $query->where('signature_type', 'scanned');
    }

    /**
     * Scope to get uploaded image signatures
     */
    public function scopeUploadedImage($query)
    {
        return $query->where('signature_type', 'uploaded_image');
    }

    /**
     * Get signature type label
     */
    public function getSignatureTypeLabel()
    {
        $labels = [
            'digital' => 'Digital',
            'scanned' => 'Scanned',
            'uploaded_image' => 'Gambar Terupload',
        ];
        return $labels[$this->signature_type] ?? $this->signature_type;
    }

    /**
     * Get full URL for signature file
     */
    public function getSignatureUrl()
    {
        return asset('storage/' . $this->signature_file_path);
    }

    /**
     * Check if signature is valid and not expired
     */
    public function isValid()
    {
        // Implement your signature validation logic here
        // For digital signatures, you might check certificate validity
        return true;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'document_type',
        'classification',
        'approval_chain',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'approval_chain' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get all workflows that are currently active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter workflows by document type
     */
    public function scopeByDocumentType($query, $documentType)
    {
        return $query->where('document_type', $documentType)
                     ->orWhereNull('document_type');
    }

    /**
     * Filter workflows by classification
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('classification', $classification)
                     ->orWhereNull('classification');
    }

    /**
     * Get the approval chain with role details
     */
    public function getApprovalChainDetails()
    {
        return Role::whereIn('id', $this->approval_chain)->get();
    }

    /**
     * Get next approver role ID based on current level
     */
    public function getNextApproverRole($currentLevel = 0)
    {
        if (isset($this->approval_chain[$currentLevel])) {
            return $this->approval_chain[$currentLevel];
        }
        return null;
    }

    /**
     * Check if all approvals are complete
     */
    public function isApprovalComplete($currentLevel)
    {
        return $currentLevel >= count($this->approval_chain);
    }

    /**
     * Get total approval levels needed
     */
    public function getTotalApprovalLevels()
    {
        return count($this->approval_chain);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;
use App\Services\ApprovalWorkflowService;

class ApprovalPolicy
{
    private ApprovalWorkflowService $approvalService;

    public function __construct(ApprovalWorkflowService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Check if user can view document approval history
     */
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->created_by ||
               $user->hasPermission('view_approvals') ||
               $this->isApproverInChain($user, $document);
    }

    /**
     * Check if user can approve document
     */
    public function approve(User $user, Document $document): bool
    {
        if (!$user->hasPermission('approve_documents')) {
            return false;
        }

        return $this->approvalService->canUserApproveDocument($user, $document);
    }

    /**
     * Check if user can reject document
     */
    public function reject(User $user, Document $document): bool
    {
        return $this->approve($user, $document);
    }

    /**
     * Check if user can request correction
     */
    public function requestCorrection(User $user, Document $document): bool
    {
        if (!$user->hasPermission('request_correction')) {
            return false;
        }

        return $this->approvalService->canUserApproveDocument($user, $document);
    }

    /**
     * Check if user is in approval chain
     */
    private function isApproverInChain(User $user, Document $document): bool
    {
        $workflow = $this->approvalService->getWorkflow($document);
        
        if (!$workflow) {
            return false;
        }

        $userRoleId = $user->roles->first()?->id;
        $approvalChain = $workflow->approval_chain;
        
        return in_array($userRoleId, $approvalChain ?? []);
    }
}

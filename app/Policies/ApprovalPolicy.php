<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dokumen;
use App\Services\ApprovalWorkflowService;

class ApprovalPolicy
{
    private ApprovalWorkflowService $approvalService;

    public function __construct(ApprovalWorkflowService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Cek apakah pengguna dapat melihat riwayat persetujuan dokumen
     */
    public function view(User $user, Dokumen $document): bool
    {
        return $user->id === $document->created_by ||
               $user->hasPermission('view_approvals') ||
               $this->isApproverInChain($user, $document);
    }

    /**
     * Cek apakah pengguna dapat menyetujui dokumen
     */
    public function approve(User $user, Dokumen $document): bool
    {
        if (!$user->hasPermission('approve_documents')) {
            return false;
        }

        return $this->approvalService->canUserApproveDocument($user, $document);
    }

    /**
     * Cek apakah pengguna dapat menolak dokumen
     */
    public function reject(User $user, Dokumen $document): bool
    {
        return $this->approve($user, $document);
    }

    /**
     * Cek apakah pengguna dapat meminta koreksi
     */
    public function requestCorrection(User $user, Dokumen $document): bool
    {
        if (!$user->hasPermission('request_correction')) {
            return false;
        }

        return $this->approvalService->canUserApproveDocument($user, $document);
    }

    /**
     * Cek apakah pengguna berada di rantai persetujuan
     */
    private function isApproverInChain(User $user, Dokumen $document): bool
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

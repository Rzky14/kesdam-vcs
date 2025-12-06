<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Models\ApprovalRolePermission;
use App\Models\CorrectionRequest;
use App\Models\ApprovalDeadline;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalWorkflowService
{
    /**
     * Get the appropriate workflow for a document
     */
    public function getWorkflow(Document $document): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::active()
            ->byDocumentType($document->type)
            ->byClassification($document->classification)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Submit a document for approval
     */
    public function submitForApproval(Document $document, User $submittedBy): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);

        if (!$workflow) {
            throw new \Exception('No approval workflow found for this document type and classification');
        }

        // Create initial approval history entry
        $approvalHistory = ApprovalHistory::create([
            'document_id' => $document->id,
            'user_id' => $submittedBy->id,
            'action' => 'submitted',
            'status' => 'pending',
            'approval_level' => 1,
            'current_approver_role' => $workflow->getNextApproverRole(0),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action_date' => now(),
        ]);

        // Update document status
        $document->update(['status' => 'pending_approval']);

        // Create approval deadlines
        $this->createApprovalDeadlines($document, $workflow);

        // Log audit trail
        Log::info('Document submitted for approval', [
            'document_id' => $document->id,
            'submitted_by' => $submittedBy->id,
            'workflow_id' => $workflow->id,
        ]);

        return $approvalHistory;
    }

    /**
     * Approve a document at current level
     */
    public function approveDocument(Document $document, User $approver, ?string $comment = null): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('No approval history found for this document');
        }

        // Check if approver has permission
        if (!ApprovalRolePermission::canPerform(
            $approver->roles()->first()->id,
            'approve',
            $document->type,
            $document->classification
        )) {
            throw new \Exception('User does not have permission to approve this document');
        }

        // Create approval history entry
        $approval = ApprovalHistory::create([
            'document_id' => $document->id,
            'user_id' => $approver->id,
            'action' => 'approved',
            'status' => 'approved',
            'comment' => $comment,
            'approval_level' => $lastApproval->approval_level,
            'current_approver_role' => $lastApproval->current_approver_role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action_date' => now(),
        ]);

        // Check if all approvals are complete
        if ($workflow->isApprovalComplete($lastApproval->approval_level)) {
            $document->update(['status' => 'approved']);
        } else {
            // Move to next approval level
            $nextLevel = $lastApproval->approval_level + 1;
            $nextRole = $workflow->getNextApproverRole($nextLevel - 1);

            ApprovalHistory::create([
                'document_id' => $document->id,
                'user_id' => $approver->id,
                'action' => 'submitted',
                'status' => 'pending',
                'approval_level' => $nextLevel,
                'current_approver_role' => $nextRole,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'action_date' => now(),
            ]);

            $document->update(['status' => 'pending_approval']);
        }

        // Update deadline status
        $deadline = ApprovalDeadline::where('document_id', $document->id)
            ->where('approval_level', $lastApproval->approval_level)
            ->first();

        if ($deadline) {
            $deadline->markAsMet();
        }

        Log::info('Document approved', [
            'document_id' => $document->id,
            'approved_by' => $approver->id,
            'approval_level' => $lastApproval->approval_level,
        ]);

        return $approval;
    }

    /**
     * Reject a document at current level
     */
    public function rejectDocument(Document $document, User $rejector, string $reason): ApprovalHistory
    {
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('No approval history found for this document');
        }

        // Create rejection history entry
        $rejection = ApprovalHistory::create([
            'document_id' => $document->id,
            'user_id' => $rejector->id,
            'action' => 'rejected',
            'status' => 'rejected',
            'comment' => $reason,
            'approval_level' => $lastApproval->approval_level,
            'current_approver_role' => $lastApproval->current_approver_role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action_date' => now(),
        ]);

        // Update document status
        $document->update(['status' => 'rejected']);

        // Update deadline status
        $deadline = ApprovalDeadline::where('document_id', $document->id)
            ->where('approval_level', $lastApproval->approval_level)
            ->first();

        if ($deadline) {
            $deadline->markAsMissed();
        }

        Log::warning('Document rejected', [
            'document_id' => $document->id,
            'rejected_by' => $rejector->id,
            'reason' => $reason,
        ]);

        return $rejection;
    }

    /**
     * Request corrections on a document
     */
    public function requestCorrection(Document $document, User $requestor, string $notes, ?string $dueDateDays = null): CorrectionRequest
    {
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('No approval history found for this document');
        }

        // Create correction request entry
        $correctionRequest = CorrectionRequest::create([
            'document_id' => $document->id,
            'approval_history_id' => $lastApproval->id,
            'requested_by_user_id' => $requestor->id,
            'assigned_to_user_id' => $document->created_by,
            'correction_notes' => $notes,
            'status' => 'pending',
            'revision_number' => $this->getNextRevisionNumber($document),
            'requested_at' => now(),
            'due_date' => $dueDateDays ? now()->addDays((int)$dueDateDays) : now()->addDays(3),
        ]);

        // Create approval history entry for correction request
        ApprovalHistory::create([
            'document_id' => $document->id,
            'user_id' => $requestor->id,
            'action' => 'correction_requested',
            'status' => 'correction_requested',
            'comment' => $notes,
            'approval_level' => $lastApproval->approval_level,
            'current_approver_role' => $lastApproval->current_approver_role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action_date' => now(),
        ]);

        // Update document status
        $document->update(['status' => 'correction_requested']);

        Log::info('Correction requested for document', [
            'document_id' => $document->id,
            'requested_by' => $requestor->id,
            'correction_request_id' => $correctionRequest->id,
        ]);

        return $correctionRequest;
    }

    /**
     * Resubmit a corrected document
     */
    public function resubmitDocument(Document $document, User $submittedBy): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('No approval history found for this document');
        }

        // Create resubmission history entry
        $resubmission = ApprovalHistory::create([
            'document_id' => $document->id,
            'user_id' => $submittedBy->id,
            'action' => 'resubmitted',
            'status' => 'pending',
            'approval_level' => $lastApproval->approval_level,
            'current_approver_role' => $lastApproval->current_approver_role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'action_date' => now(),
        ]);

        // Update document status
        $document->update(['status' => 'pending_approval']);

        // Update correction request status
        $correctionRequest = CorrectionRequest::where('document_id', $document->id)
            ->where('status', 'pending')
            ->first();

        if ($correctionRequest) {
            $correctionRequest->markAsCompleted();
        }

        Log::info('Document resubmitted after correction', [
            'document_id' => $document->id,
            'resubmitted_by' => $submittedBy->id,
        ]);

        return $resubmission;
    }

    /**
     * Get pending approvals for a user
     */
    public function getPendingApprovalsForUser(User $user)
    {
        $userRoles = $user->roles->pluck('id')->toArray();

        return Document::where('status', 'pending_approval')
            ->with(['approvalHistories' => function ($query) {
                $query->orderBy('approval_level', 'desc')->limit(1);
            }])
            ->get()
            ->filter(function ($document) use ($userRoles) {
                $lastApproval = $document->approvalHistories->first();
                return $lastApproval && in_array($lastApproval->current_approver_role, $userRoles);
            });
    }

    /**
     * Get approval history for a document
     */
    public function getApprovalHistory(Document $document)
    {
        return $document->approvalHistories()
            ->orderBy('approval_level', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Create approval deadlines for a document
     */
    private function createApprovalDeadlines(Document $document, ApprovalWorkflow $workflow): void
    {
        // Get SLA configuration (you can customize this per document type/classification)
        $slaDays = $this->getApprovalSLA($document->type, $document->classification);

        $totalLevels = $workflow->getTotalApprovalLevels();

        for ($i = 1; $i <= $totalLevels; $i++) {
            ApprovalDeadline::create([
                'document_id' => $document->id,
                'approval_level' => (string)$i,
                'document_type' => $document->type,
                'classification' => $document->classification,
                'days_allowed' => $slaDays,
                'deadline_at' => now()->addDays($slaDays * $i),
                'status' => 'active',
            ]);
        }
    }

    /**
     * Get SLA days for approval based on document type and classification
     */
    private function getApprovalSLA(string $documentType, string $classification): int
    {
        $slaConfig = [
            'masuk' => ['biasa' => 2, 'rahasia' => 1, 'telegram' => 1],
            'keluar' => ['biasa' => 3, 'rahasia' => 2, 'telegram' => 1],
        ];

        return $slaConfig[$documentType][$classification] ?? 3;
    }

    /**
     * Get next revision number for a document
     */
    private function getNextRevisionNumber(Document $document): int
    {
        return CorrectionRequest::where('document_id', $document->id)->count() + 1;
    }

    /**
     * Check if user can approve document
     */
    public function canUserApproveDocument(User $user, Document $document): bool
    {
        $userRoles = $user->roles->pluck('id')->toArray();
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            return false;
        }

        return in_array($lastApproval->current_approver_role, $userRoles) && $document->status === 'pending_approval';
    }

    /**
     * Check if user can request correction
     */
    public function canUserRequestCorrection(User $user, Document $document): bool
    {
        return $this->canUserApproveDocument($user, $document);
    }

    /**
     * Get approval workflow statistics for a document
     */
    public function getWorkflowStatistics(Document $document): array
    {
        $approvalHistories = $document->approvalHistories()->get();
        $workflow = $this->getWorkflow($document);

        return [
            'total_levels' => $workflow ? $workflow->getTotalApprovalLevels() : 0,
            'current_level' => $approvalHistories->max('approval_level') ?? 0,
            'total_approvals' => $approvalHistories->where('action', 'approved')->count(),
            'total_rejections' => $approvalHistories->where('action', 'rejected')->count(),
            'total_corrections' => $approvalHistories->where('action', 'correction_requested')->count(),
            'approval_history' => $approvalHistories,
        ];
    }
}

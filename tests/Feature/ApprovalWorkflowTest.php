<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Services\ApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalWorkflowService $approvalService;
    private User $approver;
    private User $creator;
    private Document $document;
    private ApprovalWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = app(ApprovalWorkflowService::class);

        // Create users with roles
        $this->creator = User::factory()->create(['role_id' => 4]); // Staf
        $this->approver = User::factory()->create(['role_id' => 3]); // Kasi

        // Create workflow
        $this->workflow = ApprovalWorkflow::create([
            'name' => 'Test Workflow',
            'document_type' => 'masuk',
            'classification' => 'biasa',
            'approval_chain' => json_encode([3, 2, 1]), // Role IDs
            'is_active' => true,
            'priority' => 1,
        ]);

        // Create document
        $this->document = Document::factory()->create([
            'type' => 'masuk',
            'classification' => 'biasa',
            'created_by' => $this->creator->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test dapat menemukan workflow yang sesuai
     */
    public function test_dapat_menemukan_workflow_sesuai_document_type()
    {
        $workflow = $this->approvalService->getWorkflow($this->document);

        $this->assertNotNull($workflow);
        $this->assertEquals('masuk', $workflow->document_type);
        $this->assertEquals('biasa', $workflow->classification);
    }

    /**
     * Test submit dokumen untuk approval
     */
    public function test_dapat_submit_dokumen_untuk_approval()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $this->assertDatabaseHas('approval_histories', [
            'document_id' => $this->document->id,
            'user_id' => $this->creator->id,
            'action' => 'submitted',
        ]);

        $this->assertEquals('pending_approval', $this->document->fresh()->status);
    }

    /**
     * Test approve dokumen di level pertama
     */
    public function test_dapat_approve_dokumen()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $approval = $this->approvalService->approveDocument(
            $this->document,
            $this->approver,
            'Approved by Kasi'
        );

        $this->assertNotNull($approval);
        $this->assertDatabaseHas('approval_histories', [
            'document_id' => $this->document->id,
            'user_id' => $this->approver->id,
            'action' => 'approved',
        ]);
    }

    /**
     * Test reject dokumen dengan alasan
     */
    public function test_dapat_reject_dokumen_dengan_alasan()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $rejection = $this->approvalService->rejectDocument(
            $this->document,
            $this->approver,
            'Format tidak sesuai'
        );

        $this->assertNotNull($rejection);
        $this->assertDatabaseHas('approval_histories', [
            'document_id' => $this->document->id,
            'action' => 'rejected',
            'comment' => 'Format tidak sesuai',
        ]);

        $this->assertEquals('rejected', $this->document->fresh()->status);
    }

    /**
     * Test request correction untuk dokumen
     */
    public function test_dapat_request_correction_untuk_dokumen()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $correctionNotes = 'Perbaiki bagian signature dan tanggal';
        
        $correction = $this->approvalService->requestCorrection(
            $this->document,
            $this->approver,
            $correctionNotes
        );

        $this->assertNotNull($correction);
        $this->assertDatabaseHas('correction_requests', [
            'document_id' => $this->document->id,
            'correction_notes' => $correctionNotes,
            'assigned_to_user_id' => $this->creator->id,
        ]);

        $this->assertEquals('correction_requested', $this->document->fresh()->status);
    }

    /**
     * Test resubmit dokumen setelah koreksi
     */
    public function test_dapat_resubmit_dokumen_setelah_koreksi()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);
        $this->approvalService->requestCorrection(
            $this->document,
            $this->approver,
            'Perbaiki format'
        );

        $resubmission = $this->approvalService->resubmitDocument(
            $this->document,
            $this->creator
        );

        $this->assertNotNull($resubmission);
        $this->assertEquals('pending_approval', $this->document->fresh()->status);
    }

    /**
     * Test get pending approvals untuk user
     */
    public function test_dapat_get_pending_approvals_untuk_user()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $pendingApprovals = $this->approvalService->getPendingApprovalsForUser($this->approver);

        $this->assertGreaterThan(0, $pendingApprovals->count());
    }

    /**
     * Test authorization - user dapat approve dokumen
     */
    public function test_dapat_check_permission_approve_dokumen()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $canApprove = $this->approvalService->canUserApproveDocument(
            $this->approver,
            $this->document
        );

        $this->assertTrue($canApprove);
    }

    /**
     * Test get approval history untuk dokumen
     */
    public function test_dapat_get_approval_history_dokumen()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);
        $this->approvalService->approveDocument($this->document, $this->approver);

        $history = $this->approvalService->getApprovalHistory($this->document);

        $this->assertGreaterThanOrEqual(2, $history->count());
    }

    /**
     * Test workflow statistics
     */
    public function test_dapat_get_workflow_statistics()
    {
        $this->approvalService->submitForApproval($this->document, $this->creator);

        $stats = $this->approvalService->getWorkflowStatistics($this->document);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_levels', $stats);
        $this->assertArrayHasKey('current_level', $stats);
    }
}

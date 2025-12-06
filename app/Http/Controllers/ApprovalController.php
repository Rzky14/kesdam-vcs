<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ApprovalHistory;
use App\Models\CorrectionRequest;
use App\Models\ApprovalDeadline;
use App\Services\ApprovalWorkflowService;
use App\Http\Requests\ApproveDocumentRequest;
use App\Http\Requests\RejectDocumentRequest;
use App\Http\Requests\RequestCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalWorkflowService $approvalService)
    {
        $this->approvalService = $approvalService;
        $this->middleware('auth');
    }

    /**
     * Show approval dashboard with pending approvals
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get pending approvals for current user
        $pendingApprovals = $this->approvalService->getPendingApprovalsForUser($user);

        // Get recent approval actions
        $recentApprovals = ApprovalHistory::whereIn('user_id', [$user->id])
            ->with(['document', 'user'])
            ->orderBy('action_date', 'desc')
            ->limit(10)
            ->get();

        // Get overdue deadlines
        $overdueDeadlines = ApprovalDeadline::overdue()
            ->with('document')
            ->get();

        // Get upcoming deadlines (next 3 days)
        $upcomingDeadlines = ApprovalDeadline::upcoming(3)
            ->with('document')
            ->get();

        // Get correction requests assigned to user
        $pendingCorrections = CorrectionRequest::pending()
            ->where('assigned_to_user_id', $user->id)
            ->with(['document', 'requestedBy'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Statistics
        $statistics = [
            'pending_approvals_count' => $pendingApprovals->count(),
            'overdue_deadlines_count' => $overdueDeadlines->count(),
            'upcoming_deadlines_count' => $upcomingDeadlines->count(),
            'pending_corrections_count' => $pendingCorrections->count(),
        ];

        return view('approvals.dashboard', compact(
            'pendingApprovals',
            'recentApprovals',
            'overdueDeadlines',
            'upcomingDeadlines',
            'pendingCorrections',
            'statistics'
        ));
    }

    /**
     * Show approval details for a document
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $statistics = $this->approvalService->getWorkflowStatistics($document);
        $correctionRequests = $document->correctionRequests()->get();
        $deadlines = $document->approvalDeadlines()->get();

        $canApprove = $this->approvalService->canUserApproveDocument(Auth::user(), $document);
        $canRequestCorrection = $this->approvalService->canUserRequestCorrection(Auth::user(), $document);

        return view('approvals.show', compact(
            'document',
            'approvalHistory',
            'statistics',
            'correctionRequests',
            'deadlines',
            'canApprove',
            'canRequestCorrection'
        ));
    }

    /**
     * Show form to approve document
     */
    public function approveForm(Document $document)
    {
        $this->authorize('update', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'You do not have permission to approve this document.');
        }

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $lastApproval = $approvalHistory->last();

        return view('approvals.approve', compact('document', 'lastApproval'));
    }

    /**
     * Store approval
     */
    public function approve(ApproveDocumentRequest $request, Document $document)
    {
        $this->authorize('approve', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'You do not have permission to approve this document.');
        }

        $validated = $request->validated();

        try {
            $this->approvalService->approveDocument(
                $document,
                Auth::user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('approvals.show', $document)
                ->with('success', 'Document approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve document: ' . $e->getMessage());
        }
    }

    /**
     * Show form to reject document
     */
    public function rejectForm(Document $document)
    {
        $this->authorize('reject', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'You do not have permission to reject this document.');
        }

        return view('approvals.reject', compact('document'));
    }

    /**
     * Store rejection
     */
    public function reject(RejectDocumentRequest $request, Document $document)
    {
        $this->authorize('reject', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'You do not have permission to reject this document.');
        }

        $validated = $request->validated();

        try {
            $this->approvalService->rejectDocument(
                $document,
                Auth::user(),
                $validated['reason']
            );

            return redirect()->route('approvals.show', $document)
                ->with('success', 'Document rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject document: ' . $e->getMessage());
        }
    }

    /**
     * Show form to request correction
     */
    public function correctionForm(Document $document)
    {
        $this->authorize('requestCorrection', $document);

        if (!$this->approvalService->canUserRequestCorrection(Auth::user(), $document)) {
            abort(403, 'You do not have permission to request corrections.');
        }

        return view('approvals.request-correction', compact('document'));
    }

    /**
     * Store correction request
     */
    public function requestCorrection(RequestCorrectionRequest $request, Document $document)
    {
        $this->authorize('requestCorrection', $document);

        if (!$this->approvalService->canUserRequestCorrection(Auth::user(), $document)) {
            abort(403, 'You do not have permission to request corrections.');
        }

        $validated = $request->validated();

        try {
            $this->approvalService->requestCorrection(
                $document,
                Auth::user(),
                $validated['notes'],
                isset($validated['due_date']) ? $validated['due_date'] : null
            );

            return redirect()->route('approvals.show', $document)
                ->with('success', 'Correction request sent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to request correction: ' . $e->getMessage());
        }
    }

    /**
     * Show form to resubmit document after correction
     */
    public function resubmitForm(Document $document)
    {
        $this->authorize('update', $document);

        $correctionRequest = $document->correctionRequests()
            ->where('status', 'pending')
            ->first();

        if (!$correctionRequest) {
            abort(404, 'No pending correction request found.');
        }

        return view('approvals.resubmit', compact('document', 'correctionRequest'));
    }

    /**
     * Store resubmission
     */
    public function resubmit(Request $request)
    {
        $doc = Document::findOrFail($request->document_id);
        $this->authorize('update', $doc);

        $correctionRequest = $doc->correctionRequests()
            ->where('status', 'pending')
            ->first();

        if (!$correctionRequest) {
            abort(404, 'No pending correction request found.');
        }

        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        try {
            $this->approvalService->resubmitDocument($doc, Auth::user());

            return redirect()->route('approvals.show', $doc)
                ->with('success', 'Document resubmitted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resubmit document: ' . $e->getMessage());
        }
    }

    /**
     * Show approval history for a document
     */
    public function history(Document $document)
    {
        $this->authorize('view', $document);

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $statistics = $this->approvalService->getWorkflowStatistics($document);

        return view('approvals.history', compact('document', 'approvalHistory', 'statistics'));
    }

    /**
     * Show pending approvals list
     */
    public function pending()
    {
        $user = Auth::user();
        $pendingApprovals = $this->approvalService->getPendingApprovalsForUser($user);

        return view('approvals.pending', compact('pendingApprovals'));
    }

    /**
     * Show correction requests list
     */
    public function corrections()
    {
        $user = Auth::user();
        $correctionRequests = CorrectionRequest::where('assigned_to_user_id', $user->id)
            ->with(['document', 'requestedBy'])
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return view('approvals.corrections', compact('correctionRequests'));
    }

    /**
     * Download approval report
     */
    public function downloadReport(Document $document)
    {
        $this->authorize('view', $document);

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $statistics = $this->approvalService->getWorkflowStatistics($document);

        // Generate PDF or Excel
        // This is a placeholder - implement based on your requirements
        return view('approvals.report', compact('document', 'approvalHistory', 'statistics'));
    }
}

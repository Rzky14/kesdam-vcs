<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\ApprovalHistory;
use App\Models\CorrectionRequest;
use App\Models\ApprovalDeadline;
use App\Services\ApprovalWorkflowService;
use App\Http\Requests\ApproveDocumentRequest;
use App\Http\Requests\RejectDocumentRequest;
use App\Http\Requests\RequestCorrectionRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    protected $approvalService;

    public function __construct(ApprovalWorkflowService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Tampilkan dasbor persetujuan dengan antrean yang menunggu
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Ambil antrean persetujuan untuk pengguna saat ini
        $pendingApprovals = $this->approvalService->getPendingApprovalsForUser($user);

        // Ambil aktivitas persetujuan terbaru
        $recentApprovals = ApprovalHistory::whereIn('user_id', [$user->id])
            ->with(['document', 'user'])
            ->orderBy('action_date', 'desc')
            ->limit(10)
            ->get();

        // Ambil batas waktu yang sudah lewat
        $overdueDeadlines = ApprovalDeadline::overdue()
            ->with('document')
            ->get();

        // Ambil batas waktu yang akan datang (3 hari ke depan)
        $upcomingDeadlines = ApprovalDeadline::upcoming(3)
            ->with('document')
            ->get();

        // Ambil permintaan koreksi yang ditugaskan ke pengguna
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
     * Tampilkan detail persetujuan untuk dokumen
     */
    public function show(Dokumen $document)
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
     * Form persetujuan dokumen
     */
    public function approveForm(Dokumen $document)
    {
        $this->authorize('update', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk menyetujui dokumen ini.');
        }

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $lastApproval = $approvalHistory->last();

        return view('approvals.approve', compact('document', 'lastApproval'));
    }

    /**
     * Simpan persetujuan
     */
    public function approve(ApproveDocumentRequest $request, Dokumen $document)
    {
        $this->authorize('approve', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk menyetujui dokumen ini.');
        }

        $validated = $request->validated();

        try {
            $this->approvalService->approveDocument(
                $document,
                Auth::user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('approvals.show', $document)
                ->with('success', 'Dokumen berhasil disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Form penolakan dokumen
     */
    public function rejectForm(Dokumen $document)
    {
        $this->authorize('reject', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk menolak dokumen ini.');
        }

        return view('approvals.reject', compact('document'));
    }

    /**
     * Simpan penolakan
     */
    public function reject(RejectDocumentRequest $request, Dokumen $document)
    {
        $this->authorize('reject', $document);

        if (!$this->approvalService->canUserApproveDocument(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk menolak dokumen ini.');
        }

        $validated = $request->validated();

        try {
            $this->approvalService->rejectDocument(
                $document,
                Auth::user(),
                $validated['reason']
            );

            return redirect()->route('approvals.show', $document)
                ->with('success', 'Dokumen berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Form permintaan koreksi
     */
    public function correctionForm(Dokumen $document)
    {
        $this->authorize('requestCorrection', $document);

        if (!$this->approvalService->canUserRequestCorrection(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk meminta koreksi.');
        }

        return view('approvals.request-correction', compact('document'));
    }

    /**
     * Simpan permintaan koreksi
     */
    public function requestCorrection(RequestCorrectionRequest $request, Dokumen $document)
    {
        $this->authorize('requestCorrection', $document);

        if (!$this->approvalService->canUserRequestCorrection(Auth::user(), $document)) {
            abort(403, 'Anda tidak memiliki izin untuk meminta koreksi.');
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
                ->with('success', 'Permintaan koreksi berhasil dikirim.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim permintaan koreksi: ' . $e->getMessage());
        }
    }

    /**
     * Form unggah ulang dokumen setelah koreksi
     */
    public function resubmitForm(Dokumen $document)
    {
        $this->authorize('update', $document);

        $correctionRequest = $document->correctionRequests()
            ->where('status', 'pending')
            ->first();

        if (!$correctionRequest) {
            abort(404, 'Tidak ada permintaan koreksi tertunda.');
        }

        return view('approvals.resubmit', compact('document', 'correctionRequest'));
    }

    /**
     * Simpan unggah ulang
     */
    public function resubmit(Request $request)
    {
        $doc = Dokumen::findOrFail($request->document_id);
        $this->authorize('update', $doc);

        $correctionRequest = $doc->correctionRequests()
            ->where('status', 'pending')
            ->first();

        if (!$correctionRequest) {
            abort(404, 'Tidak ada permintaan koreksi tertunda.');
        }

        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        try {
            $this->approvalService->resubmitDocument($doc, Auth::user());

            return redirect()->route('approvals.show', $doc)
                ->with('success', 'Dokumen berhasil dikirim ulang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim ulang dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan riwayat persetujuan untuk dokumen
     */
    public function history(Dokumen $document)
    {
        $this->authorize('view', $document);

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $statistics = $this->approvalService->getWorkflowStatistics($document);

        return view('approvals.history', compact('document', 'approvalHistory', 'statistics'));
    }

    /**
     * Tampilkan daftar persetujuan yang menunggu
     */
    public function pending()
    {
        $user = Auth::user();
        $pendingApprovals = $this->approvalService->getPendingApprovalsForUser($user);

        return view('approvals.pending', compact('pendingApprovals'));
    }

    /**
     * Tampilkan daftar permintaan koreksi
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
     * Unduh laporan persetujuan
     */
    public function downloadReport(Dokumen $document)
    {
        $this->authorize('view', $document);

        $approvalHistory = $this->approvalService->getApprovalHistory($document);
        $statistics = $this->approvalService->getWorkflowStatistics($document);

        // TODO: Implementasi ekspor PDF atau Excel sesuai kebutuhan
        return view('approvals.report', compact('document', 'approvalHistory', 'statistics'));
    }
}

<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Models\ApprovalRolePermission;
use App\Models\CorrectionRequest;
use App\Models\ApprovalDeadline;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalWorkflowService
{
    /**
     * Ambil alur persetujuan yang sesuai untuk dokumen
     */
    public function getWorkflow(Surat $document): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::active()
            ->byDocumentType($document->type)
            ->byClassification($document->classification)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Ajukan dokumen untuk proses persetujuan
     */
    public function submitForApproval(Surat $document, User $submittedBy): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);

        if (!$workflow) {
            throw new \Exception('Tidak ada alur persetujuan untuk tipe dan klasifikasi dokumen ini');
        }

        // Buat entri awal riwayat persetujuan
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

        // Perbarui status dokumen
        $document->update(['status' => 'pending_approval']);

        // Buat batas waktu persetujuan
        $this->createApprovalDeadlines($document, $workflow);

        // Catat audit trail
        Log::info('Dokumen diajukan untuk persetujuan', [
            'document_id' => $document->id,
            'submitted_by' => $submittedBy->id,
            'workflow_id' => $workflow->id,
        ]);

        return $approvalHistory;
    }

    /**
     * Setujui dokumen pada level saat ini
     */
    public function approveDocument(Surat $document, User $approver, ?string $comment = null): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('Riwayat persetujuan untuk dokumen ini tidak ditemukan');
        }

        // Pastikan pemberi persetujuan memiliki hak akses
        if (!ApprovalRolePermission::canPerform(
            $approver->roles()->first()->id,
            'approve',
            $document->type,
            $document->classification
        )) {
            throw new \Exception('Pengguna tidak memiliki izin untuk menyetujui dokumen ini');
        }

        // Buat entri riwayat persetujuan
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

        // Cek apakah seluruh level persetujuan sudah selesai
        if ($workflow->isApprovalComplete($lastApproval->approval_level)) {
            $document->update(['status' => 'approved']);
        } else {
            // Lanjut ke level persetujuan berikutnya
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

        // Tandai batas waktu pada level ini
        $deadline = ApprovalDeadline::where('document_id', $document->id)
            ->where('approval_level', $lastApproval->approval_level)
            ->first();

        if ($deadline) {
            $deadline->markAsMet();
        }

        Log::info('Dokumen disetujui', [
            'document_id' => $document->id,
            'approved_by' => $approver->id,
            'approval_level' => $lastApproval->approval_level,
        ]);

        return $approval;
    }

    /**
     * Tolak dokumen pada level saat ini
     */
    public function rejectDocument(Surat $document, User $rejector, string $reason): ApprovalHistory
    {
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('Riwayat persetujuan untuk dokumen ini tidak ditemukan');
        }

        // Buat entri riwayat penolakan
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

        // Perbarui status dokumen
        $document->update(['status' => 'rejected']);

        // Tandai batas waktu pada level ini
        $deadline = ApprovalDeadline::where('document_id', $document->id)
            ->where('approval_level', $lastApproval->approval_level)
            ->first();

        if ($deadline) {
            $deadline->markAsMissed();
        }

        Log::warning('Dokumen ditolak', [
            'document_id' => $document->id,
            'rejected_by' => $rejector->id,
            'reason' => $reason,
        ]);

        return $rejection;
    }

    /**
     * Ajukan permintaan koreksi pada dokumen
     */
    public function requestCorrection(Surat $document, User $requestor, string $notes, ?string $dueDateDays = null): CorrectionRequest
    {
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('Riwayat persetujuan untuk dokumen ini tidak ditemukan');
        }

        // Buat entri permintaan koreksi
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

        // Tambahkan entri riwayat untuk permintaan koreksi
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

        // Perbarui status dokumen
        $document->update(['status' => 'correction_requested']);

        Log::info('Permintaan koreksi diajukan untuk dokumen', [
            'document_id' => $document->id,
            'requested_by' => $requestor->id,
            'correction_request_id' => $correctionRequest->id,
        ]);

        return $correctionRequest;
    }

    /**
     * Kirim ulang dokumen yang telah dikoreksi
     */
    public function resubmitDocument(Surat $document, User $submittedBy): ApprovalHistory
    {
        $workflow = $this->getWorkflow($document);
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            throw new \Exception('Riwayat persetujuan untuk dokumen ini tidak ditemukan');
        }

        // Buat entri riwayat pengiriman ulang
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

        // Perbarui status dokumen
        $document->update(['status' => 'pending_approval']);

        // Tandai permintaan koreksi telah diselesaikan
        $correctionRequest = CorrectionRequest::where('document_id', $document->id)
            ->where('status', 'pending')
            ->first();

        if ($correctionRequest) {
            $correctionRequest->markAsCompleted();
        }

        Log::info('Dokumen dikirim ulang setelah koreksi', [
            'document_id' => $document->id,
            'resubmitted_by' => $submittedBy->id,
        ]);

        return $resubmission;
    }

    /**
     * Ambil daftar persetujuan yang menunggu untuk pengguna
     */
    public function getPendingApprovalsForUser(User $user)
    {
        $userRoles = $user->roles->pluck('id')->toArray();

        return Surat::where('status', 'pending_approval')
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
     * Ambil riwayat persetujuan untuk dokumen
     */
    public function getApprovalHistory(Surat $document)
    {
        return $document->approvalHistories()
            ->orderBy('approval_level', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Buat batas waktu persetujuan untuk dokumen
     */
    private function createApprovalDeadlines(Surat $document, ApprovalWorkflow $workflow): void
    {
        // Ambil konfigurasi SLA (dapat disesuaikan per tipe/klasifikasi dokumen)
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
     * Ambil SLA persetujuan berdasarkan tipe dan klasifikasi dokumen
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
     * Ambil nomor revisi berikutnya untuk dokumen
     */
    private function getNextRevisionNumber(Surat $document): int
    {
        return CorrectionRequest::where('document_id', $document->id)->count() + 1;
    }

    /**
     * Periksa apakah pengguna dapat menyetujui dokumen
     */
    public function canUserApproveDocument(User $user, Surat $document): bool
    {
        $userRoles = $user->roles->pluck('id')->toArray();
        $lastApproval = $document->approvalHistories()->orderBy('approval_level', 'desc')->first();

        if (!$lastApproval) {
            return false;
        }

        return in_array($lastApproval->current_approver_role, $userRoles) && $document->status === 'pending_approval';
    }

    /**
     * Periksa apakah pengguna dapat meminta koreksi
     */
    public function canUserRequestCorrection(User $user, Surat $document): bool
    {
        return $this->canUserApproveDocument($user, $document);
    }

    /**
     * Ambil statistik alur persetujuan untuk dokumen
     */
    public function getWorkflowStatistics(Surat $document): array
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




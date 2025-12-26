<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Services\EncryptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

/**
 * DokumenController
 * 
 * Menangani request HTTP untuk Dokumen.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 * Sesuai dengan UML Class Diagram dan SOLID Principles.
 */
class DokumenController extends Controller
{
    use AuthorizesRequests;
    
    protected $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Menampilkan daftar dokumen dengan pencarian dan filter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Dokumen::class);

        $query = Dokumen::with(['pembuat', 'pengubah']);

        // Search by subject or number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('sender', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        // Filter by type (masuk/keluar)
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by classification
        if ($request->filled('classification')) {
            $query->ofClassification($request->classification);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Sort by latest
        $query->latest('date');

        $documents = $query->paginate(15)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    /**
     * Menampilkan form untuk membuat dokumen baru.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Dokumen::class);

        $type = $request->query('type', 'masuk');

        return view('documents.create', compact('type'));
    }

    /**
     * Menyimpan dokumen baru ke database.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Dokumen::class);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['masuk', 'keluar'])],
            'classification' => ['required', Rule::in(['biasa', 'rahasia', 'telegram'])],
            'number' => ['nullable', 'string', 'max:255', 'unique:documents,number'],
            'date' => ['required', 'date'],
            'sender' => ['required_if:type,masuk', 'nullable', 'string', 'max:255'],
            'recipient' => ['required_if:type,keluar', 'nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['normal', 'high', 'urgent'])],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB max per file
        ]);

        // Generate document number if not provided
        if (empty($validated['number'])) {
            $validated['number'] = $this->generateDocumentNumber(
                $validated['type'],
                $validated['classification']
            );
        }

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('documents', 'private');
                $attachmentPaths[] = $path;
            }
            $validated['attachments'] = $attachmentPaths;
        }

        // Set encryption flag for classified documents
        $validated['is_encrypted'] = $validated['classification'] === 'rahasia';

        // Encrypt sensitive fields for rahasia documents
        if ($validated['is_encrypted']) {
            $validated['subject'] = Crypt::encryptString($validated['subject']);
            if (!empty($validated['description'])) {
                $validated['description'] = Crypt::encryptString($validated['description']);
            }
        }

        // Set creator
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'draft';

        $dokumen = Dokumen::create($validated);

        return redirect()
            ->route('documents.show', $dokumen)
            ->with('success', 'Dokumen berhasil dibuat.');
    }

    /**
     * Menampilkan detail dokumen.
     */
    public function show(Dokumen $dokumen)
    {
        $this->authorize('view', $dokumen);

        $dokumen->load(['pembuat', 'pengubah']);

        // Decrypt sensitive fields if needed
        if ($dokumen->is_encrypted && $dokumen->adalahRahasia()) {
            try {
                $dokumen->subject = Crypt::decryptString($dokumen->subject);
                if (!empty($dokumen->description)) {
                    $dokumen->description = Crypt::decryptString($dokumen->description);
                }
            } catch (\Exception $e) {
                // Log error but continue
                Log::error('Failed to decrypt document: ' . $e->getMessage());
            }
        }

        return view('documents.show', compact('dokumen'));
    }

    /**
     * Menampilkan form untuk edit dokumen.
     */
    public function edit(Dokumen $dokumen)
    {
        $this->authorize('update', $dokumen);

        // Only allow editing draft or rejected documents
        if (!in_array($dokumen->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('documents.show', $dokumen)
                ->with('error', 'Hanya dokumen dengan status Draft atau Ditolak yang dapat diedit.');
        }

        // Decrypt sensitive fields if needed
        if ($dokumen->is_encrypted && $dokumen->adalahRahasia()) {
            try {
                $dokumen->subject = Crypt::decryptString($dokumen->subject);
                if (!empty($dokumen->description)) {
                    $dokumen->description = Crypt::decryptString($dokumen->description);
                }
            } catch (\Exception $e) {
                Log::error('Failed to decrypt document: ' . $e->getMessage());
            }
        }

        return view('documents.edit', compact('dokumen'));
    }

    /**
     * Memperbarui dokumen di database.
     */
    public function update(Request $request, Dokumen $dokumen)
    {
        $this->authorize('update', $dokumen);

        // Only allow editing draft or rejected documents
        if (!in_array($dokumen->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('documents.show', $dokumen)
                ->with('error', 'Hanya dokumen dengan status Draft atau Ditolak yang dapat diedit.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['masuk', 'keluar'])],
            'classification' => ['required', Rule::in(['biasa', 'rahasia', 'telegram'])],
            'number' => ['nullable', 'string', 'max:255', Rule::unique('documents', 'number')->ignore($dokumen->id)],
            'date' => ['required', 'date'],
            'sender' => ['required_if:type,masuk', 'nullable', 'string', 'max:255'],
            'recipient' => ['required_if:type,keluar', 'nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['normal', 'high', 'urgent'])],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
        ]);

        // Handle file uploads
        $existingAttachments = $dokumen->attachments ?? [];
        
        // Remove specified attachments
        if ($request->filled('remove_attachments')) {
            foreach ($request->remove_attachments as $pathToRemove) {
                if (($key = array_search($pathToRemove, $existingAttachments)) !== false) {
                    Storage::disk('private')->delete($pathToRemove);
                    unset($existingAttachments[$key]);
                }
            }
            $existingAttachments = array_values($existingAttachments);
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('documents', 'private');
                $existingAttachments[] = $path;
            }
        }

        $validated['attachments'] = $existingAttachments;

        // Set encryption flag for classified documents
        $validated['is_encrypted'] = $validated['classification'] === 'rahasia';

        // Encrypt sensitive fields for rahasia documents
        if ($validated['is_encrypted']) {
            $validated['subject'] = Crypt::encryptString($validated['subject']);
            if (!empty($validated['description'])) {
                $validated['description'] = Crypt::encryptString($validated['description']);
            }
        }

        // Set updater
        $validated['updated_by'] = Auth::id();

        $dokumen->update($validated);

        return redirect()
            ->route('documents.show', $dokumen)
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Menghapus dokumen dari database.
     */
    public function destroy(Dokumen $dokumen)
    {
        $this->authorize('delete', $dokumen);

        // Only allow deleting draft documents
        if ($dokumen->status !== 'draft') {
            return redirect()
                ->route('documents.index')
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat dihapus.');
        }

        // Delete associated files
        if (!empty($dokumen->attachments)) {
            foreach ($dokumen->attachments as $path) {
                Storage::disk('private')->delete($path);
            }
        }

        $dokumen->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Ajukan dokumen untuk persetujuan.
     */
    public function submit(Dokumen $dokumen)
    {
        $this->authorize('update', $dokumen);

        if ($dokumen->status !== 'draft') {
            return redirect()
                ->route('documents.show', $dokumen)
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat diajukan.');
        }

        $dokumen->update([
            'status' => 'pending_approval',
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('documents.show', $dokumen)
            ->with('success', 'Dokumen berhasil diajukan untuk persetujuan.');
    }

    /**
     * Arsipkan dokumen.
     */
    public function archive(Dokumen $dokumen)
    {
        $this->authorize('update', $dokumen);

        if ($dokumen->status !== 'approved') {
            return redirect()
                ->route('documents.show', $dokumen)
                ->with('error', 'Hanya dokumen yang sudah disetujui yang dapat diarsipkan.');
        }

        $dokumen->update([
            'status' => 'archived',
            'archived_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('documents.show', $dokumen)
            ->with('success', 'Dokumen berhasil diarsipkan.');
    }

    /**
     * Download lampiran dokumen.
     */
    public function download(Dokumen $dokumen, $attachmentIndex)
    {
        $this->authorize('view', $dokumen);

        if (empty($dokumen->attachments) || !isset($dokumen->attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found.');
        }

        $path = $dokumen->attachments[$attachmentIndex];

        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File not found.');
        }

        $file = Storage::disk('private')->get($path);
        $fileName = basename($path);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];
        
        $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';

        return response($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Generate unique document number.
     */
    private function generateDocumentNumber($type, $classification)
    {
        $prefix = match($type) {
            'masuk' => 'SM',
            'keluar' => 'SK',
            default => 'DOC',
        };

        $classPrefix = match($classification) {
            'rahasia' => 'R',
            'telegram' => 'T',
            default => '',
        };

        $year = date('Y');
        $month = date('m');

        // Get the last document number for this type, classification, and month
        $lastDokumen = Dokumen::where('type', $type)
            ->where('classification', $classification)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('number', 'desc')
            ->first();

        $sequenceNumber = 1;
        
        if ($lastDokumen) {
            // Extract sequence number from last document number
            preg_match('/(\\d+)\\//', $lastDokumen->number, $matches);
            if (!empty($matches[1])) {
                $sequenceNumber = intval($matches[1]) + 1;
            }
        }

        $number = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

        return $classPrefix 
            ? "{$prefix}-{$classPrefix}/{$number}/{$month}/{$year}" 
            : "{$prefix}/{$number}/{$month}/{$year}";
    }

    /**
     * Setujui dokumen.
     */
    public function approve(Request $request, Dokumen $dokumen)
    {
        $this->authorize('approve', $dokumen);

        try {
            // Get next approver before approval
            $nextRole = $dokumen->ambilRolePenyetujuBerikutnya();
            
            $dokumen->setujui(
                Auth::user(),
                $request->input('notes')
            );

            // Refresh document to get updated status
            $dokumen->refresh();
            
            // Determine success message based on document status
            if ($dokumen->adalahDisetujui()) {
                $message = 'Dokumen berhasil disetujui secara penuh. Semua tahap persetujuan telah selesai.';
            } else {
                $nextApproverLabel = match($nextRole) {
                    'kaur' => 'Kepala Urusan (KAUR)',
                    'kasi' => 'Kepala Seksi (KASI)',
                    'pimpinan' => 'Pimpinan/Pejabat Tinggi',
                    default => 'Level berikutnya',
                };
                $message = "Dokumen berhasil Anda setujui dan akan diteruskan ke {$nextApproverLabel}.";
            }

            return redirect()
                ->route('documents.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak dokumen.
     */
    public function reject(Request $request, Dokumen $dokumen)
    {
        $this->authorize('reject', $dokumen);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $dokumen->tolak(
                Auth::user(),
                $request->input('reason')
            );

            return redirect()
                ->route('documents.index')
                ->with('success', 'Dokumen berhasil ditolak dan dikembalikan kepada pembuat.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Minta koreksi untuk dokumen.
     */
    public function requestCorrection(Request $request, Dokumen $dokumen)
    {
        $this->authorize('requestCorrection', $dokumen);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $dokumen->mintaKoreksi(
                Auth::user(),
                $request->input('reason')
            );

            return redirect()
                ->route('documents.index')
                ->with('success', 'Permintaan koreksi berhasil dikirim kepada pembuat dokumen.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}

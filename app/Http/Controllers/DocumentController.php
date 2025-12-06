<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\EncryptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    use AuthorizesRequests;
    
    protected $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Display a listing of documents with search and filter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $query = Document::with(['creator', 'updater']);

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
     * Show the form for creating a new document.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Document::class);

        $type = $request->query('type', 'masuk');

        return view('documents.create', compact('type'));
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

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

        $document = Document::create($validated);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil dibuat.');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['creator', 'updater']);

        // Decrypt sensitive fields if needed
        if ($document->is_encrypted && $document->isClassified()) {
            try {
                $document->subject = Crypt::decryptString($document->subject);
                if (!empty($document->description)) {
                    $document->description = Crypt::decryptString($document->description);
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Failed to decrypt document: ' . $e->getMessage());
            }
        }

        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        // Only allow editing draft or rejected documents
        if (!in_array($document->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Hanya dokumen dengan status Draft atau Ditolak yang dapat diedit.');
        }

        // Decrypt sensitive fields if needed
        if ($document->is_encrypted && $document->isClassified()) {
            try {
                $document->subject = Crypt::decryptString($document->subject);
                if (!empty($document->description)) {
                    $document->description = Crypt::decryptString($document->description);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt document: ' . $e->getMessage());
            }
        }

        return view('documents.edit', compact('document'));
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        // Only allow editing draft or rejected documents
        if (!in_array($document->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Hanya dokumen dengan status Draft atau Ditolak yang dapat diedit.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['masuk', 'keluar'])],
            'classification' => ['required', Rule::in(['biasa', 'rahasia', 'telegram'])],
            'number' => ['nullable', 'string', 'max:255', Rule::unique('documents', 'number')->ignore($document->id)],
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
        $existingAttachments = $document->attachments ?? [];
        
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

        $document->update($validated);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        // Only allow deleting draft documents
        if ($document->status !== 'draft') {
            return redirect()
                ->route('documents.index')
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat dihapus.');
        }

        // Delete associated files
        if (!empty($document->attachments)) {
            foreach ($document->attachments as $path) {
                Storage::disk('private')->delete($path);
            }
        }

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Submit document for approval.
     */
    public function submit(Document $document)
    {
        $this->authorize('update', $document);

        if ($document->status !== 'draft') {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat diajukan.');
        }

        $document->update([
            'status' => 'pending_approval',
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diajukan untuk persetujuan.');
    }

    /**
     * Archive the document.
     */
    public function archive(Document $document)
    {
        $this->authorize('update', $document);

        if ($document->status !== 'approved') {
            return redirect()
                ->route('documents.show', $document)
                ->with('error', 'Hanya dokumen yang sudah disetujui yang dapat diarsipkan.');
        }

        $document->update([
            'status' => 'archived',
            'archived_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Dokumen berhasil diarsipkan.');
    }

    /**
     * Download document attachment.
     */
    public function download(Document $document, $attachmentIndex)
    {
        $this->authorize('view', $document);

        if (empty($document->attachments) || !isset($document->attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found.');
        }

        $path = $document->attachments[$attachmentIndex];

        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download($path);
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
        $lastDocument = Document::where('type', $type)
            ->where('classification', $classification)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('number', 'desc')
            ->first();

        $sequenceNumber = 1;
        
        if ($lastDocument) {
            // Extract sequence number from last document number
            preg_match('/(\d+)\//', $lastDocument->number, $matches);
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
     * Approve document
     */
    public function approve(Request $request, Document $document)
    {
        $this->authorize('approve', $document);

        try {
            // Get next approver before approval
            $nextRole = $document->getNextApproverRole();
            
            $document->approve(
                auth()->user(),
                $request->input('notes')
            );

            // Refresh document to get updated status
            $document->refresh();
            
            // Determine success message based on document status
            if ($document->isApproved()) {
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
     * Reject document
     */
    public function reject(Request $request, Document $document)
    {
        $this->authorize('reject', $document);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $document->reject(
                auth()->user(),
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
     * Request correction for document
     */
    public function requestCorrection(Request $request, Document $document)
    {
        $this->authorize('requestCorrection', $document);

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $document->requestCorrection(
                auth()->user(),
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

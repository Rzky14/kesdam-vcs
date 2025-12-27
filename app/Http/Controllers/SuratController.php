<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use App\Services\EncryptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class SuratController extends Controller
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
        $this->authorize('viewAny', Surat::class);

        $query = Surat::with(['creator', 'updater']);

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

        return view('surat.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Surat::class);

        $type = $request->query('type', 'masuk');

        return view('surat.create', compact('type'));
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Surat::class);

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

        $document = Surat::create($validated);

        return redirect()
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil dibuat.');
    }

    /**
     * Display the specified document.
     */
    public function show(Surat $document)
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

        return view('surat.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Surat $document)
    {
        $this->authorize('update', $document);

        // Only allow editing draft or rejected documents
        if (!in_array($document->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('surat.show', $document)
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

        return view('surat.edit', compact('document'));
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, Surat $document)
    {
        $this->authorize('update', $document);

        // Only allow editing draft or rejected documents
        if (!in_array($document->status, ['draft', 'rejected'])) {
            return redirect()
                ->route('surat.show', $document)
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
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Surat $document)
    {
        $this->authorize('delete', $document);

        // Only allow deleting draft documents
        if ($document->status !== 'draft') {
            return redirect()
                ->route('surat.index')
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
            ->route('surat.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Submit document for approval.
     */
    public function submit(Surat $document)
    {
        $this->authorize('update', $document);

        if ($document->status !== 'draft') {
            return redirect()
                ->route('surat.show', $document)
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat diajukan.');
        }

        $document->update([
            'status' => 'pending_approval',
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil diajukan untuk persetujuan.');
    }

    /**
     * Archive the document.
     */
    public function archive(Surat $document)
    {
        $this->authorize('update', $document);

        if ($document->status !== 'approved') {
            return redirect()
                ->route('surat.show', $document)
                ->with('error', 'Hanya dokumen yang sudah disetujui yang dapat diarsipkan.');
        }

        $document->update([
            'status' => 'archived',
            'archived_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil diarsipkan.');
    }

    /**
     * Download document attachment.
     */
    public function download(Surat $document, $attachmentIndex)
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
        $lastDocument = Surat::where('type', $type)
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

        // Loop untuk menghindari duplicate number
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $number = str_pad($sequenceNumber + $attempt, 4, '0', STR_PAD_LEFT);
            $generatedNumber = $classPrefix 
                ? "{$prefix}-{$classPrefix}/{$number}/{$month}/{$year}" 
                : "{$prefix}/{$number}/{$month}/{$year}";
            
            // Cek apakah nomor sudah ada
            $exists = Surat::where('number', $generatedNumber)->exists();
            
            if (!$exists) {
                return $generatedNumber;
            }
            
            $attempt++;
        } while ($attempt < $maxAttempts);
        
        // Fallback jika sudah mencoba 100 kali
        return $classPrefix 
            ? "{$prefix}-{$classPrefix}/" . uniqid() . "/{$month}/{$year}" 
            : "{$prefix}/{$number}/{$month}/{$year}";
    }
}




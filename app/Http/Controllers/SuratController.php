<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\SuratMasuk;
use App\Models\SuratKeluar;
use App\Services\EncryptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * Tampilkan daftar surat dengan pencarian dan filter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Surat::class);

        $query = Surat::with(['creator', 'updater']);

        // Cari berdasarkan perihal atau nomor
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('sender', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan jenis (masuk/keluar)
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter berdasarkan klasifikasi
        if ($request->filled('classification')) {
            $query->ofClassification($request->classification);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filter berdasarkan prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Urutkan berdasarkan terbaru
        $query->latest('date');

        $documents = $query->paginate(15)->withQueryString();

        return view('surat.index', compact('documents'));
    }

    /**
     * Tampilkan form untuk membuat surat baru.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Surat::class);

        $type = $request->query('type', 'masuk');

        return view('surat.create', compact('type'));
    }

    /**
     * Simpan surat baru ke database.
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

        // Tangani upload file
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

        // Set pembuat dokumen
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'draft';

        // Gunakan transaction dengan retry untuk handle race condition
        $maxRetries = 3;
        $document = null;
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                // Generate document number sebelum transaction
                if (empty($validated['number'])) {
                    $validated['number'] = $this->generateDocumentNumber(
                        $validated['type'],
                        $validated['classification']
                    );
                }
                
                // Create dalam transaction
                $document = DB::transaction(function () use ($validated) {
                    return Surat::create($validated);
                });
                
                break; // Success, keluar dari loop
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                if ($i === $maxRetries - 1) {
                    throw $e; // Retry terakhir gagal, throw error
                }
                // Retry dengan generate nomor baru
                $validated['number'] = null; // Reset nomor untuk regenerate
                usleep(100000); // Sleep 100ms sebelum retry
            }
        }

        return redirect()
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil dibuat.');
    }

    /**
     * Tampilkan detail surat.
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
                // Log error tapi tetap lanjut
                \Log::error('Failed to decrypt document: ' . $e->getMessage());
            }
        }

        return view('surat.show', compact('document'));
    }

    /**
     * Tampilkan form untuk edit surat.
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
     * Update surat yang ada di database.
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
        
        // Hapus lampiran yang ditentukan
        if ($request->filled('remove_attachments')) {
            foreach ($request->remove_attachments as $pathToRemove) {
                if (($key = array_search($pathToRemove, $existingAttachments)) !== false) {
                    Storage::disk('private')->delete($pathToRemove);
                    unset($existingAttachments[$key]);
                }
            }
            $existingAttachments = array_values($existingAttachments);
        }

        // Tambah lampiran baru
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

        // Set yang mengupdate
        $validated['updated_by'] = Auth::id();

        $document->update($validated);

        return redirect()
            ->route('surat.show', $document)
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Hapus surat dari database.
     */
    public function destroy(Surat $document)
    {
        $this->authorize('delete', $document);

        // Hanya izinkan hapus dokumen draft
        if ($document->status !== 'draft') {
            return redirect()
                ->route('surat.index')
                ->with('error', 'Hanya dokumen dengan status Draft yang dapat dihapus.');
        }

        // Hapus file terkait
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
     * Ajukan dokumen untuk persetujuan.
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
     * Setujui dokumen.
     */
    public function approve(Request $request, Surat $surat)
    {
        $this->authorize('approve', $surat);

        $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $surat->setujui(Auth::user(), $request->comment);

            return redirect()
                ->route('surat.show', $surat)
                ->with('success', 'Dokumen berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()
                ->route('surat.show', $surat)
                ->with('error', 'Gagal menyetujui dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Tolak dokumen.
     */
    public function reject(Request $request, Surat $surat)
    {
        $this->authorize('approve', $surat);

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $surat->tolak(Auth::user(), $request->comment);

            return redirect()
                ->route('surat.show', $surat)
                ->with('success', 'Dokumen berhasil ditolak.');
        } catch (\Exception $e) {
            return redirect()
                ->route('surat.show', $surat)
                ->with('error', 'Gagal menolak dokumen: ' . $e->getMessage());
        }
    }

    /**
     * Minta koreksi dokumen.
     */
    public function requestCorrection(Request $request, Surat $surat)
    {
        $this->authorize('approve', $surat);

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $surat->mintaKoreksi(Auth::user(), $request->comment);

            return redirect()
                ->route('surat.show', $surat)
                ->with('success', 'Permintaan koreksi berhasil dikirim.');
        } catch (\Exception $e) {
            return redirect()
                ->route('surat.show', $surat)
                ->with('error', 'Gagal mengirim permintaan koreksi: ' . $e->getMessage());
        }
    }

    /**
     * Arsipkan dokumen.
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
     * Download lampiran dokumen.
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
     * Generate nomor dokumen unik.
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

        // Cari semua nomor untuk type dan classification yang sama di bulan/tahun ini
        // INCLUDE soft deleted karena unique constraint tetap berlaku
        $existingNumbers = Surat::withTrashed()
            ->where('type', $type)
            ->where('classification', $classification)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('number');

        // Extract semua nomor urut yang ada
        $sequenceNumbers = [];
        foreach ($existingNumbers as $num) {
            // Pattern: SM/0001/12/2025 atau SM-R/0001/12/2025 atau SM-T/0001/12/2025
            if (preg_match('/[A-Z-]+\/(\d+)\/\d{2}\/\d{4}$/', $num, $matches)) {
                $sequenceNumbers[] = intval($matches[1]);
            }
        }

        // Cari nomor urut berikutnya yang available
        $sequenceNumber = 1;
        while (in_array($sequenceNumber, $sequenceNumbers)) {
            $sequenceNumber++;
        }

        // Generate nomor final
        $number = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
        $generatedNumber = $classPrefix 
            ? "{$prefix}-{$classPrefix}/{$number}/{$month}/{$year}" 
            : "{$prefix}/{$number}/{$month}/{$year}";
        
        // Double check tidak ada duplicate
        $maxAttempts = 50;
        $attempt = 0;
        
        while (Surat::where('number', $generatedNumber)->exists() && $attempt < $maxAttempts) {
            $sequenceNumber++;
            $number = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
            $generatedNumber = $classPrefix 
                ? "{$prefix}-{$classPrefix}/{$number}/{$month}/{$year}" 
                : "{$prefix}/{$number}/{$month}/{$year}";
            $attempt++;
        }
        
        if ($attempt >= $maxAttempts) {
            // Fallback dengan uniqid jika masih bentrok
            $uniqueId = uniqid();
            return $classPrefix 
                ? "{$prefix}-{$classPrefix}/{$uniqueId}/{$month}/{$year}" 
                : "{$prefix}/{$uniqueId}/{$month}/{$year}";
        }
        
        return $generatedNumber;
    }
}




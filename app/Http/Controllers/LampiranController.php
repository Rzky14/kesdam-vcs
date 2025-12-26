<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Services\EncryptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * LampiranController
 * 
 * Menangani request HTTP untuk Lampiran dokumen.
 * Lampiran disimpan sebagai JSON di field 'attachments' pada tabel documents.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 */
class LampiranController extends Controller
{
    use AuthorizesRequests;

    /**
     * @var EncryptionService
     */
    protected $layananEnkripsi;

    /**
     * Constructor dengan dependency injection.
     *
     * @param EncryptionService $layananEnkripsi
     */
    public function __construct(EncryptionService $layananEnkripsi)
    {
        $this->layananEnkripsi = $layananEnkripsi;
    }

    /**
     * Menampilkan daftar lampiran untuk dokumen tertentu.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @return \Illuminate\Http\Response
     */
    public function index(Dokumen $dokumen)
    {
        $lampiran = collect($dokumen->attachments ?? []);
        return view('lampiran.index', compact('dokumen', 'lampiran'));
    }

    /**
     * Menyimpan lampiran baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Dokumen  $dokumen
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Dokumen $dokumen)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Maks 10MB
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('file');

            // Cek apakah dokumen bersifat rahasia
            if ($dokumen->classification === 'rahasia') {
                $hasil = $this->layananEnkripsi->enkripsiDanSimpan(
                    $file,
                    'lampiran-rahasia'
                );
                $path = $hasil['path'];
            } else {
                $path = $file->store('lampiran');
            }

            // Buat entry lampiran baru
            $lampiranBaru = [
                'id' => uniqid('att_'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $file->getClientOriginalExtension(),
                'path' => $path,
                'uploaded_at' => now()->toIso8601String(),
                'uploaded_by' => Auth::id(),
            ];

            // Tambahkan ke array attachments yang ada
            $attachments = $dokumen->attachments ?? [];
            $attachments[] = $lampiranBaru;
            
            $dokumen->update([
                'attachments' => $attachments,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', 'Lampiran berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menambahkan lampiran: ' . $e->getMessage()]);
        }
    }

    /**
     * Mengunduh lampiran yang ditentukan.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @param  string  $attachmentId
     * @return \Illuminate\Http\Response
     */
    public function download(Dokumen $dokumen, string $attachmentId)
    {
        $this->authorize('view', $dokumen);

        try {
            // Cari lampiran berdasarkan ID
            $attachments = collect($dokumen->attachments ?? []);
            $lampiran = $attachments->firstWhere('id', $attachmentId);

            if (!$lampiran) {
                return back()->withErrors(['error' => 'Lampiran tidak ditemukan']);
            }

            // Cek apakah file terenkripsi
            if (str_contains($lampiran['path'], '.encrypted')) {
                $terdekripsi = $this->layananEnkripsi->ambilDanDekripsi($lampiran['path']);
                
                return response($terdekripsi)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="' . $lampiran['name'] . '"');
            }

            // Unduh file biasa
            return Storage::download($lampiran['path'], $lampiran['name']);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengunduh lampiran: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan detail lampiran.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @param  string  $attachmentId
     * @return \Illuminate\Http\Response
     */
    public function show(Dokumen $dokumen, string $attachmentId)
    {
        $this->authorize('view', $dokumen);
        
        $attachments = collect($dokumen->attachments ?? []);
        $lampiran = $attachments->firstWhere('id', $attachmentId);

        if (!$lampiran) {
            abort(404, 'Lampiran tidak ditemukan');
        }

        return view('lampiran.show', compact('dokumen', 'lampiran'));
    }

    /**
     * Menghapus lampiran dari dokumen.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @param  string  $attachmentId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dokumen $dokumen, string $attachmentId)
    {
        $this->authorize('update', $dokumen);

        DB::beginTransaction();
        try {
            $attachments = collect($dokumen->attachments ?? []);
            $lampiran = $attachments->firstWhere('id', $attachmentId);

            if (!$lampiran) {
                return back()->withErrors(['error' => 'Lampiran tidak ditemukan']);
            }

            // Hapus file dari storage
            if (Storage::exists($lampiran['path'])) {
                Storage::delete($lampiran['path']);
            }

            // Hapus dari array attachments
            $attachmentsBaru = $attachments->reject(function ($item) use ($attachmentId) {
                return $item['id'] === $attachmentId;
            })->values()->toArray();

            $dokumen->update([
                'attachments' => $attachmentsBaru,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', 'Lampiran berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus lampiran: ' . $e->getMessage()]);
        }
    }
}

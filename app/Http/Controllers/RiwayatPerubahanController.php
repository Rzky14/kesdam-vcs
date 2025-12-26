<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dokumen;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

/**
 * RiwayatPerubahanController
 * 
 * Menangani request HTTP untuk RiwayatPerubahan (audit logs).
 * Menggunakan tabel audit_logs yang ada di database.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 */
class RiwayatPerubahanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Middleware diatur di routes
    }

    /**
     * Menampilkan daftar riwayat perubahan untuk dokumen tertentu.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @return \Illuminate\Http\Response
     */
    public function index(Dokumen $dokumen)
    {
        $riwayatPerubahan = AuditLog::where('auditable_type', Dokumen::class)
            ->where('auditable_id', $dokumen->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('riwayat-perubahan.index', compact('dokumen', 'riwayatPerubahan'));
    }

    /**
     * Menampilkan detail riwayat perubahan.
     *
     * @param  \App\Models\AuditLog  $auditLog
     * @return \Illuminate\Http\Response
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['auditable', 'user']);
        return view('riwayat-perubahan.show', compact('auditLog'));
    }

    /**
     * Dapatkan perubahan terbaru untuk dashboard atau laporan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function terbaru(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $riwayatPerubahan = AuditLog::with(['auditable', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return view('riwayat-perubahan.terbaru', compact('riwayatPerubahan'));
    }

    /**
     * Filter riwayat perubahan berdasarkan event type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Dokumen  $dokumen
     * @return \Illuminate\Http\Response
     */
    public function filterBerdasarkanTipe(Request $request, Dokumen $dokumen)
    {
        $tipe = $request->input('type');
        
        $riwayatPerubahan = AuditLog::where('auditable_type', Dokumen::class)
            ->where('auditable_id', $dokumen->id)
            ->where('event', $tipe)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('riwayat-perubahan.index', compact('dokumen', 'riwayatPerubahan', 'tipe'));
    }

    /**
     * Ekspor riwayat perubahan ke PDF atau Excel.
     *
     * @param  \App\Models\Dokumen  $dokumen
     * @param  string  $format
     * @return \Illuminate\Http\Response
     */
    public function ekspor(Dokumen $dokumen, $format = 'pdf')
    {
        $this->authorize('view', $dokumen);

        $riwayatPerubahan = AuditLog::where('auditable_type', Dokumen::class)
            ->where('auditable_id', $dokumen->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'pdf') {
            // TODO: Implementasi ekspor PDF
            return view('riwayat-perubahan.ekspor-pdf', compact('dokumen', 'riwayatPerubahan'));
        } elseif ($format === 'excel') {
            // TODO: Implementasi ekspor Excel
            return view('riwayat-perubahan.ekspor-excel', compact('dokumen', 'riwayatPerubahan'));
        }

        return back()->withErrors(['error' => 'Format tidak didukung']);
    }
}

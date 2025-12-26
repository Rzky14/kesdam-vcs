<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Dokumen;
use App\Models\Schedule;
use App\Models\AuditLog;
use App\Models\ApprovalHistory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * LaporanController
 * 
 * Menangani request HTTP untuk Laporan.
 * Menggunakan model yang sesuai dengan database yang ada.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 */
class LaporanController extends Controller
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
     * Menampilkan daftar laporan.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('view_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $query = Laporan::with('pembuat');

        // Filter berdasarkan jenis laporan
        if ($request->filled('type')) {
            $query->berdasarkanJenis($request->type);
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('period_start') && $request->filled('period_end')) {
            $query->antaraTanggal($request->period_start, $request->period_end);
        }

        $laporanList = $query->orderBy('created_at', 'desc')
                            ->paginate(15);

        return view('laporan.index', compact('laporanList'));
    }

    /**
     * Menampilkan form untuk membuat laporan baru.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('create_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        return view('laporan.create');
    }

    /**
     * Generate dan simpan laporan baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        if (!$authUser->punyaIzin('create_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:schedule,document,effectiveness'],
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        DB::beginTransaction();
        try {
            // Generate data laporan
            $data = $this->ambilDataLaporan(
                $validated['type'],
                $validated['period_start'],
                $validated['period_end']
            );

            $laporan = Laporan::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'generated_by' => $authUser->id,
                'data' => $data,
                'status' => Laporan::STATUS_COMPLETED,
            ]);

            DB::commit();

            return redirect()
                ->route('laporan.show', $laporan->id)
                ->with('success', 'Laporan berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal membuat laporan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan detail laporan.
     *
     * @param  \App\Models\Laporan  $laporan
     * @return \Illuminate\Http\Response
     */
    public function show(Laporan $laporan)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('view_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $laporan->load('pembuat');

        // Data sudah disimpan di JSON field
        $data = $laporan->data ?? [];

        return view('laporan.show', compact('laporan', 'data'));
    }

    /**
     * Ekspor laporan yang ditentukan.
     *
     * @param  \App\Models\Laporan  $laporan
     * @param  string  $format
     * @return \Illuminate\Http\Response
     */
    public function ekspor(Laporan $laporan, $format = 'pdf')
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('export_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $data = $laporan->data ?? [];

        if ($format === 'pdf') {
            // TODO: Implementasi ekspor PDF
            return view('laporan.ekspor-pdf', compact('laporan', 'data'));
        } elseif ($format === 'excel') {
            // TODO: Implementasi ekspor Excel
            return view('laporan.ekspor-excel', compact('laporan', 'data'));
        }

        return back()->withErrors(['error' => 'Format tidak didukung']);
    }

    /**
     * Menghapus laporan dari database.
     *
     * @param  \App\Models\Laporan  $laporan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Laporan $laporan)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        if (!$authUser->punyaIzin('delete_reports')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $laporan->delete();

        return redirect()
            ->route('laporan.index')
            ->with('success', 'Laporan berhasil dihapus');
    }

    /**
     * Ambil data laporan berdasarkan tipe laporan.
     *
     * @param  string  $type
     * @param  string  $periodStart
     * @param  string  $periodEnd
     * @return array
     */
    protected function ambilDataLaporan(string $type, string $periodStart, string $periodEnd): array
    {
        switch ($type) {
            case 'document':
                $masuk = Dokumen::where('type', 'masuk')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->count();
                $keluar = Dokumen::where('type', 'keluar')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->count();
                $pending = Dokumen::where('status', 'pending_approval')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->count();
                $approved = Dokumen::where('status', 'approved')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->count();
                    
                return [
                    'total_masuk' => $masuk,
                    'total_keluar' => $keluar,
                    'total_pending' => $pending,
                    'total_approved' => $approved,
                    'documents' => Dokumen::whereBetween('date', [$periodStart, $periodEnd])
                        ->with('pembuat')
                        ->get()
                        ->toArray(),
                ];

            case 'schedule':
                return [
                    'total' => Schedule::whereBetween('start_date', [$periodStart, $periodEnd])->count(),
                    'by_type' => Schedule::whereBetween('start_date', [$periodStart, $periodEnd])
                        ->selectRaw('type, COUNT(*) as count')
                        ->groupBy('type')
                        ->pluck('count', 'type')
                        ->toArray(),
                    'schedules' => Schedule::whereBetween('start_date', [$periodStart, $periodEnd])
                        ->with('pembuat')
                        ->get()
                        ->toArray(),
                ];

            case 'effectiveness':
                $totalDocs = Dokumen::whereBetween('date', [$periodStart, $periodEnd])->count();
                $approvedDocs = Dokumen::where('status', 'approved')
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->count();
                $avgApprovalTime = ApprovalHistory::whereBetween('action_date', [$periodStart, $periodEnd])
                    ->where('status', 'approved')
                    ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, action_date)'));
                    
                return [
                    'total_documents' => $totalDocs,
                    'approved_documents' => $approvedDocs,
                    'approval_rate' => $totalDocs > 0 ? round(($approvedDocs / $totalDocs) * 100, 2) : 0,
                    'avg_approval_time_hours' => round($avgApprovalTime ?? 0, 2),
                ];

            default:
                return [];
        }
    }
}

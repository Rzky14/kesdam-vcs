<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * JadwalController
 * 
 * Menangani request HTTP untuk Jadwal.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 * Sesuai dengan UML Class Diagram dan SOLID Principles.
 */
class JadwalController extends Controller
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
     * Menampilkan daftar jadwal.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('view_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $query = Jadwal::with('pembuat');

        // Filter berdasarkan jenis
        if ($request->filled('jenis')) {
            $query->berdasarkanJenis($request->jenis);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->berdasarkanStatus($request->status);
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggalMulai') && $request->filled('tanggalSelesai')) {
            $query->antaraTanggal($request->tanggalMulai, $request->tanggalSelesai);
        }

        // Pencarian
        if ($request->filled('search')) {
            $cari = $request->search;
            $query->where(function ($q) use ($cari) {
                $q->where('judul', 'like', "%{$cari}%")
                  ->orWhere('deskripsi', 'like', "%{$cari}%")
                  ->orWhere('lokasi', 'like', "%{$cari}%");
            });
        }

        $jadwalList = $query->orderBy('tanggalMulai', 'desc')
                           ->paginate(15);

        return view('jadwal.index', compact('jadwalList'));
    }

    /**
     * Menampilkan form untuk membuat jadwal baru.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('create_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        return view('jadwal.create');
    }

    /**
     * Menyimpan jadwal baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        if (!$authUser->punyaIzin('create_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['dukkes', 'jaga', 'kegiatan_satuan'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'personnel' => ['nullable', 'array'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
        ]);

        DB::beginTransaction();
        try {
            $validated['created_by'] = $authUser->id;
            $validated['updated_by'] = $authUser->id;

            $jadwal = Jadwal::create($validated);

            DB::commit();

            return redirect()
                ->route('jadwal.show', $jadwal->id)
                ->with('success', 'Jadwal berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal membuat jadwal: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan detail jadwal.
     *
     * @param  \App\Models\Jadwal  $jadwal
     * @return \Illuminate\Http\Response
     */
    public function show(Jadwal $jadwal)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('view_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $jadwal->load('pembuat');

        return view('jadwal.show', compact('jadwal'));
    }

    /**
     * Menampilkan form untuk mengedit jadwal.
     *
     * @param  \App\Models\Jadwal  $jadwal
     * @return \Illuminate\Http\Response
     */
    public function edit(Jadwal $jadwal)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user->punyaIzin('edit_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        return view('jadwal.edit', compact('jadwal'));
    }

    /**
     * Memperbarui jadwal di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Jadwal  $jadwal
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Jadwal $jadwal)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        if (!$authUser->punyaIzin('edit_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['dukkes', 'jaga', 'kegiatan_satuan'])],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after_or_equal:tanggalMulai'],
            'waktu' => ['nullable', 'string', 'max:50'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
        ]);

        DB::beginTransaction();
        try {
            $jadwal->update($validated);

            DB::commit();

            return redirect()
                ->route('jadwal.show', $jadwal->idJadwal)
                ->with('success', 'Jadwal berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui jadwal: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghapus jadwal dari database.
     *
     * @param  \App\Models\Jadwal  $jadwal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Jadwal $jadwal)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        if (!$authUser->punyaIzin('delete_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        DB::beginTransaction();
        try {
            $jadwal->delete();

            DB::commit();

            return redirect()
                ->route('jadwal.index')
                ->with('success', 'Jadwal berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Gagal menghapus jadwal: ' . $e->getMessage()]);
        }
    }
}




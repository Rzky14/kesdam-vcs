<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NotifikasiController
 * 
 * Menangani request HTTP untuk Notifikasi.
 * Mengikuti Single Responsibility Principle - hanya menangani layer HTTP.
 * Sesuai dengan UML Class Diagram dan SOLID Principles.
 */
class NotifikasiController extends Controller
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
     * Menampilkan daftar notifikasi untuk pengguna saat ini.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Notifikasi::where('user_id', $user->id);

        // Filter berdasarkan status
        if ($request->filled('statusBaca')) {
            if ($request->statusBaca === 'belum') {
                $query->whereNull('read_at');
            } elseif ($request->statusBaca === 'sudah') {
                $query->whereNotNull('read_at');
            }
        }

        // Filter berdasarkan jenis
        if ($request->filled('jenis')) {
            $query->where('type', $request->jenis);
        }

        $notifikasiList = $query->orderBy('created_at', 'desc')
                               ->paginate(20);

        // Dapatkan jumlah belum dibaca
        $jumlahBelumDibaca = Notifikasi::where('user_id', $user->id)
                                 ->whereNull('read_at')
                                 ->count();

        return view('notifikasi.index', compact('notifikasiList', 'jumlahBelumDibaca'));
    }

    /**
     * Menampilkan detail notifikasi.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\Response
     */
    public function show(Notifikasi $notifikasi)
    {
        // Cek otorisasi
        $user = Auth::user();
        if ($notifikasi->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        // Tandai sebagai dibaca jika belum
        if (!$notifikasi->read_at) {
            $notifikasi->tandaiBaca();
        }

        $notifikasi->load('user');

        return view('notifikasi.show', compact('notifikasi'));
    }

    /**
     * Tandai notifikasi sebagai dibaca.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\Response
     */
    public function tandaiDibaca(Request $request, Notifikasi $notifikasi)
    {
        // Cek otorisasi
        $user = Auth::user();
        if ($notifikasi->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $notifikasi->tandaiBaca();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi ditandai sebagai dibaca',
            ]);
        }

        return back()->with('success', 'Notifikasi ditandai sebagai dibaca');
    }

    /**
     * Tandai notifikasi sebagai belum dibaca.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\Response
     */
    public function tandaiBelumDibaca(Request $request, Notifikasi $notifikasi)
    {
        // Cek otorisasi
        $user = Auth::user();
        if ($notifikasi->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $notifikasi->update(['read_at' => null]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi ditandai sebagai belum dibaca',
            ]);
        }

        return back()->with('success', 'Notifikasi ditandai sebagai belum dibaca');
    }

    /**
     * Tandai semua notifikasi sebagai dibaca untuk pengguna saat ini.
     *
     * @return \Illuminate\Http\Response
     */
    public function tandaiSemuaDibaca()
    {
        $user = Auth::user();

        Notifikasi::where('user_id', $user->id)
                  ->whereNull('read_at')
                  ->update(['read_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi ditandai sebagai dibaca',
            ]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sebagai dibaca');
    }

    /**
     * Menghapus notifikasi dari database.
     *
     * @param  \App\Models\Notifikasi  $notifikasi
     * @return \Illuminate\Http\Response
     */
    public function destroy(Notifikasi $notifikasi)
    {
        // Cek otorisasi
        $user = Auth::user();
        if ($notifikasi->user_id !== $user->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $notifikasi->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus',
            ]);
        }

        return back()->with('success', 'Notifikasi berhasil dihapus');
    }

    /**
     * Dapatkan jumlah notifikasi belum dibaca untuk pengguna saat ini (API).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function jumlahBelumDibaca()
    {
        $user = Auth::user();
        
        $jumlah = Notifikasi::where('user_id', $user->id)
                          ->whereNull('read_at')
                          ->count();

        return response()->json([
            'jumlah' => $jumlah,
        ]);
    }

    /**
     * Dapatkan notifikasi terbaru untuk pengguna saat ini (API).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function terbaru(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 5);

        $notifikasi = Notifikasi::where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->limit($limit)
                                ->get();

        return response()->json([
            'notifikasi' => $notifikasi,
        ]);
    }
}

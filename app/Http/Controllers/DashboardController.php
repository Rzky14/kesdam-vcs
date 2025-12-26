<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * DashboardController
 * 
 * Mengelola tampilan dasbor berdasarkan peran pengguna.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dasbor.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ambil statistik berdasarkan peran pengguna
        $stats = $this->getStatistics($user);
        
        // Ambil jadwal terbaru berdasarkan peran pengguna
        $recentSchedules = $this->getRecentSchedules($user);
        
        // Ambil dokumen terbaru berdasarkan peran pengguna
        $recentDocuments = $this->getRecentDocuments($user);
        
        return view('dashboard.index', compact('stats', 'recentSchedules', 'recentDocuments'));
    }
    
    /**
     * Ambil statistik berdasarkan peran pengguna.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    protected function getStatistics($user)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;
        
        if ($user->hasRole('admin_sistem')) {
            // Admin sees all data
            return [
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->count(),
                'incoming_documents' => Dokumen::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        } elseif ($user->hasRole('pimpinan')) {
            // Pimpinan focuses on approvals and incoming documents
            return [
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->count(),
                'incoming_documents' => Dokumen::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        } elseif ($user->hasRole('kasi_kaur')) {
            // Kasi/Kaur sees unit-specific data
            return [
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->where(function($query) use ($user) {
                        $query->where('created_by', $user->id)
                              ->orWhereJsonContains('personnel', $user->id);
                    })
                    ->count(),
                'incoming_documents' => Dokumen::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')
                    ->where('created_by', $user->id)
                    ->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->where('created_by', $user->id)
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        } else {
            // Batih/Staf sees only their own data
            return [
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->where(function($query) use ($user) {
                        $query->where('created_by', $user->id)
                              ->orWhereJsonContains('personnel', $user->id);
                    })
                    ->count(),
                'incoming_documents' => Dokumen::where('type', 'masuk')
                    ->where('created_by', $user->id)
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')
                    ->where('created_by', $user->id)
                    ->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->where('created_by', $user->id)
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        }
    }
    
    /**
     * Ambil jadwal terbaru berdasarkan peran pengguna.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentSchedules($user)
    {
        $query = Schedule::with('creator')
            ->where('end_date', '>=', Carbon::today())
            ->orderBy('start_date', 'asc');
        
        if (!$user->hasRole('admin_sistem') && !$user->hasRole('pimpinan')) {
            // Filter jadwal untuk Kasi/Kaur dan Batih/Staf
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereJsonContains('personnel', $user->id);
            });
        }
        
        return $query->limit(3)->get();
    }
    
    /**
     * Ambil dokumen terbaru berdasarkan peran pengguna.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentDocuments($user)
    {
        $query = Dokumen::with('creator')
            ->orderBy('date', 'desc');
        
        if ($user->hasRole('admin_sistem') || $user->hasRole('pimpinan')) {
            // Admin dan Pimpinan melihat semua dokumen terbaru
            $query->whereIn('status', ['pending_approval', 'approved']);
        } elseif ($user->hasRole('kasi_kaur')) {
            // Kasi/Kaur melihat dokumen unit dan persetujuan yang menunggu
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('status', 'pending_approval');
            });
        } else {
            // Batih/Staf hanya melihat dokumen mereka sendiri
            $query->where('created_by', $user->id);
        }
        
        return $query->limit(3)->get();
    }
}

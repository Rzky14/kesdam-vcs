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
        
        // Ambil dokumen pending approval untuk Pimpinan dan Kasi/Kaur
        $pendingApprovals = $this->getPendingApprovals($user);
        
        // Ambil notifikasi terbaru
        $recentNotifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Ambil jadwal hari ini
        $todaySchedules = $this->getTodaySchedules($user);
        
        return view('dashboard.index', compact(
            'stats', 
            'recentSchedules', 
            'recentDocuments',
            'pendingApprovals',
            'recentNotifications',
            'todaySchedules'
        ));
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
                'total_users' => User::where('is_active', true)->count(),
                'total_documents' => Dokumen::count(),
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->count(),
                'incoming_documents' => Dokumen::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'outgoing_documents' => Dokumen::where('type', 'keluar')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')->count(),
                'approved_today' => Dokumen::where('status', 'approved')
                    ->whereDate('updated_at', $today)
                    ->count(),
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
                'outgoing_documents' => Dokumen::where('type', 'keluar')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Dokumen::where('status', 'pending_approval')->count(),
                'approved_today' => Dokumen::where('status', 'approved')
                    ->whereDate('updated_at', $today)
                    ->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
                'urgent_documents' => Dokumen::where('priority', 'urgent')
                    ->where('status', 'pending_approval')
                    ->count(),
            ];
        } elseif ($user->hasRole('kasi') || $user->hasRole('kaur')) {
            // Kasi/Kaur sees unit-specific data
            // Ambil dokumen dari tim/divisi yang sama
            $teamUserIds = User::where('unit', $user->unit)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
            
            return [
                'active_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->whereIn('created_by', $teamUserIds)
                    ->count(),
                'team_members' => count($teamUserIds) - 1, // exclude self
                'pending_my_approval' => Dokumen::where('status', 'pending_approval')
                    ->whereIn('created_by', $teamUserIds)
                    ->count(),
                'draft_documents' => Dokumen::where('status', 'draft')
                    ->whereIn('created_by', $teamUserIds)
                    ->count(),
                'approved_documents' => Dokumen::where('status', 'approved')
                    ->whereIn('created_by', $teamUserIds)
                    ->count(),
                'rejected_documents' => Dokumen::where('status', 'rejected')
                    ->whereIn('created_by', $teamUserIds)
                    ->count(),
                'completed_this_month' => Dokumen::where('status', 'approved')
                    ->whereIn('created_by', $teamUserIds)
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        } else {
            // Batih/Staf sees only their own data
            return [
                'my_schedules' => Schedule::where('status', 'active')
                    ->where('end_date', '>=', $today)
                    ->where(function($query) use ($user) {
                        $query->where('created_by', $user->id)
                              ->orWhereJsonContains('personnel', (string)$user->id);
                    })
                    ->count(),
                'draft_documents' => Dokumen::where('status', 'draft')
                    ->where('created_by', $user->id)
                    ->count(),
                'pending_approval' => Dokumen::where('status', 'pending_approval')
                    ->where('created_by', $user->id)
                    ->count(),
                'approved_documents' => Dokumen::where('status', 'approved')
                    ->where('created_by', $user->id)
                    ->count(),
                'rejected_documents' => Dokumen::where('status', 'rejected')
                    ->where('created_by', $user->id)
                    ->count(),
                'tasks_today' => Schedule::whereDate('start_date', $today)
                    ->where(function($query) use ($user) {
                        $query->where('created_by', $user->id)
                              ->orWhereJsonContains('personnel', (string)$user->id);
                    })
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
                  ->orWhereJsonContains('personnel', (string)$user->id);
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
        $query = Dokumen::with(['pembuat', 'riwayatPersetujuan'])
            ->orderBy('date', 'desc');
        
        if ($user->hasRole('admin_sistem') || $user->hasRole('pimpinan')) {
            // Admin dan Pimpinan melihat semua dokumen terbaru
            $query->whereIn('status', ['pending_approval', 'approved']);
        } elseif ($user->hasRole('kasi') || $user->hasRole('kaur')) {
            // Kasi/Kaur melihat dokumen unit
            $teamUserIds = User::where('unit', $user->unit)
                ->pluck('id')
                ->toArray();
            $query->whereIn('created_by', $teamUserIds);
        } else {
            // Batih/Staf hanya melihat dokumen mereka sendiri
            $query->where('created_by', $user->id);
        }
        
        return $query->limit(5)->get();
    }
    
    /**
     * Ambil dokumen yang perlu approval.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getPendingApprovals($user)
    {
        if ($user->hasRole('pimpinan')) {
            // Pimpinan melihat semua pending approval
            return Dokumen::with(['pembuat', 'riwayatPersetujuan'])
                ->where('status', 'pending_approval')
                ->orderBy('priority', 'desc')
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();
        } elseif ($user->hasRole('kasi') || $user->hasRole('kaur')) {
            // Kasi/Kaur melihat pending dari tim
            $teamUserIds = User::where('unit', $user->unit)
                ->pluck('id')
                ->toArray();
            return Dokumen::with(['pembuat', 'riwayatPersetujuan'])
                ->where('status', 'pending_approval')
                ->whereIn('created_by', $teamUserIds)
                ->orderBy('priority', 'desc')
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();
        }
        
        return collect(); // Empty collection untuk role lain
    }
    
    /**
     * Ambil jadwal hari ini.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getTodaySchedules($user)
    {
        $today = Carbon::today();
        
        $query = Schedule::with('creator')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'active')
            ->orderBy('start_date', 'asc');
        
        if (!$user->hasRole('admin_sistem') && !$user->hasRole('pimpinan')) {
            // Filter untuk non-admin/pimpinan
            if ($user->hasRole('kasi') || $user->hasRole('kaur')) {
                // Kasi/Kaur lihat jadwal divisi
                $teamUserIds = User::where('unit', $user->unit)
                    ->pluck('id')
                    ->toArray();
                $query->whereIn('created_by', $teamUserIds);
            } else {
                // Staf hanya lihat jadwal sendiri
                $query->where(function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereJsonContains('personnel', (string)$user->id);
                });
            }
        }
        
        return $query->limit(5)->get();
    }
    
    /**
     * API untuk mendapatkan data grafik dokumen (30 hari terakhir).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocumentChartData()
    {
        $startDate = Carbon::now()->subDays(30);
        
        $chartData = Dokumen::selectRaw('DATE(date) as date, type, COUNT(*) as count')
            ->where('date', '>=', $startDate)
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date');
        
        $result = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data = $chartData->get($date, collect());
            
            $result[] = [
                'date' => $date,
                'masuk' => $data->where('type', 'masuk')->sum('count'),
                'keluar' => $data->where('type', 'keluar')->sum('count'),
            ];
        }
        
        return response()->json($result);
    }
    
    /**
     * API untuk mendapatkan statistik quick view.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQuickStats()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $stats = [
            'documents_today' => Dokumen::whereDate('created_at', $today)->count(),
            'approvals_pending' => Dokumen::where('status', 'pending_approval')->count(),
            'schedules_today' => Schedule::whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->where('status', 'active')
                ->count(),
            'notifications_unread' => $user->unreadNotifications()->count(),
        ];
        
        return response()->json($stats);
    }
}

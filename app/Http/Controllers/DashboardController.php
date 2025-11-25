<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get statistics based on user role
        $stats = $this->getStatistics($user);
        
        // Get recent schedules based on user role
        $recentSchedules = $this->getRecentSchedules($user);
        
        // Get recent documents based on user role
        $recentDocuments = $this->getRecentDocuments($user);
        
        return view('dashboard.index', compact('stats', 'recentSchedules', 'recentDocuments'));
    }
    
    /**
     * Get statistics based on user role.
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
                'incoming_documents' => Document::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Document::where('status', 'pending_approval')->count(),
                'completed_this_month' => Document::where('status', 'approved')
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
                'incoming_documents' => Document::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Document::where('status', 'pending_approval')->count(),
                'completed_this_month' => Document::where('status', 'approved')
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
                'incoming_documents' => Document::where('type', 'masuk')
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Document::where('status', 'pending_approval')
                    ->where('created_by', $user->id)
                    ->count(),
                'completed_this_month' => Document::where('status', 'approved')
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
                'incoming_documents' => Document::where('type', 'masuk')
                    ->where('created_by', $user->id)
                    ->whereDate('date', $today)
                    ->count(),
                'pending_approvals' => Document::where('status', 'pending_approval')
                    ->where('created_by', $user->id)
                    ->count(),
                'completed_this_month' => Document::where('status', 'approved')
                    ->where('created_by', $user->id)
                    ->whereMonth('updated_at', $thisMonth)
                    ->whereYear('updated_at', $thisYear)
                    ->count(),
            ];
        }
    }
    
    /**
     * Get recent schedules based on user role.
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
            // Filter schedules for Kasi/Kaur and Batih/Staf
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereJsonContains('personnel', $user->id);
            });
        }
        
        return $query->limit(3)->get();
    }
    
    /**
     * Get recent documents based on user role.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentDocuments($user)
    {
        $query = Document::with('creator')
            ->orderBy('date', 'desc');
        
        if ($user->hasRole('admin_sistem') || $user->hasRole('pimpinan')) {
            // Admin and Pimpinan see all recent documents
            $query->whereIn('status', ['pending_approval', 'approved']);
        } elseif ($user->hasRole('kasi_kaur')) {
            // Kasi/Kaur see unit documents and pending approvals
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('status', 'pending_approval');
            });
        } else {
            // Batih/Staf see only their own documents
            $query->where('created_by', $user->id);
        }
        
        return $query->limit(3)->get();
    }
}

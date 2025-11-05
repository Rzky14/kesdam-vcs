<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * ScheduleController
 * 
 * Handle CRUD operations for schedule management (Dukkes, Jaga, Kegiatan Satuan).
 * Includes conflict detection for personnel scheduling.
 */
class ScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(Request $request)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('view_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Schedule::with(['creator', 'updater']);

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->withStatus($request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $schedules = $query->orderBy('start_date', 'desc')
                          ->orderBy('start_time', 'desc')
                          ->paginate(15);

        return view('schedules.index', compact('schedules'));
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create()
    {
        // Authorization check
        if (!Auth::user()->hasPermission('create_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        // Get all active users for personnel selection
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('schedules.create', compact('users'));
    }

    /**
     * Store a newly created schedule in storage.
     */
    public function store(Request $request)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('create_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['dukkes', 'jaga', 'kegiatan_satuan'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'personnel' => ['required', 'array', 'min:1'],
            'personnel.*' => ['exists:users,id'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        // Check for scheduling conflicts if personnel are assigned
        if (!empty($validated['personnel']) && $validated['status'] === 'active') {
            $conflicts = $this->checkConflicts(
                $validated['personnel'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['start_time'] ?? null,
                $validated['end_time'] ?? null
            );

            if ($conflicts->isNotEmpty()) {
                $conflictNames = $conflicts->pluck('name')->join(', ');
                return back()
                    ->withInput()
                    ->with('error', "Konflik jadwal ditemukan untuk personel: {$conflictNames}. Silakan pilih personel lain atau ubah tanggal/waktu.");
            }
        }

        // Create schedule
        $schedule = Schedule::create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'personnel' => $validated['personnel'] ?? [],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Log creation
        AuditLog::log(
            event: 'schedule_created',
            model: $schedule,
            newValues: $schedule->toArray(),
            description: "Schedule '{$schedule->title}' created by " . Auth::user()->name
        );

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('view_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        $schedule->load(['creator', 'updater']);
        
        // Get personnel details
        $personnel = $schedule->getPersonnelUsers();
        
        // Get audit logs for this schedule
        $auditLogs = AuditLog::where('auditable_type', Schedule::class)
            ->where('auditable_id', $schedule->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('schedules.show', compact('schedule', 'personnel', 'auditLogs'));
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit(Schedule $schedule)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('edit_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        // Get all active users for personnel selection
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('schedules.edit', compact('schedule', 'users'));
    }

    /**
     * Update the specified schedule in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('edit_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['dukkes', 'jaga', 'kegiatan_satuan'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'personnel' => ['required', 'array', 'min:1'],
            'personnel.*' => ['exists:users,id'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        // Check for scheduling conflicts if personnel are assigned and status is active
        if (!empty($validated['personnel']) && $validated['status'] === 'active') {
            $conflicts = $this->checkConflicts(
                $validated['personnel'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['start_time'] ?? null,
                $validated['end_time'] ?? null,
                $schedule->id // Exclude current schedule from conflict check
            );

            if ($conflicts->isNotEmpty()) {
                $conflictNames = $conflicts->pluck('name')->join(', ');
                return back()
                    ->withInput()
                    ->with('error', "Konflik jadwal ditemukan untuk personel: {$conflictNames}. Silakan pilih personel lain atau ubah tanggal/waktu.");
            }
        }

        $oldValues = $schedule->toArray();

        // Update schedule
        $schedule->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'personnel' => $validated['personnel'] ?? [],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        // Log update
        AuditLog::log(
            event: 'schedule_updated',
            model: $schedule,
            oldValues: $oldValues,
            newValues: $schedule->fresh()->toArray(),
            description: "Schedule '{$schedule->title}' updated by " . Auth::user()->name
        );

        return redirect()->route('schedules.show', $schedule)
            ->with('success', 'Jadwal berhasil diupdate.');
    }

    /**
     * Remove the specified schedule from storage.
     */
    public function destroy(Schedule $schedule)
    {
        // Authorization check
        if (!Auth::user()->hasPermission('delete_schedules')) {
            abort(403, 'Unauthorized action.');
        }

        $scheduleTitle = $schedule->title;

        // Log deletion before actually deleting
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'schedule_deleted',
            'auditable_type' => Schedule::class,
            'auditable_id' => $schedule->id,
            'old_values' => $schedule->toArray(),
            'new_values' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "Schedule '{$scheduleTitle}' deleted by " . Auth::user()->name,
        ]);

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Check for scheduling conflicts.
     * 
     * @param array $personnelIds
     * @param string $startDate
     * @param string $endDate
     * @param string|null $startTime
     * @param string|null $endTime
     * @param int|null $excludeScheduleId
     * @return \Illuminate\Support\Collection
     */
    protected function checkConflicts(
        array $personnelIds,
        string $startDate,
        string $endDate,
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $excludeScheduleId = null
    ) {
        $conflictingUsers = collect();

        foreach ($personnelIds as $userId) {
            $query = Schedule::where('status', 'active')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })
                ->whereRaw('JSON_CONTAINS(personnel, ?)', [json_encode($userId)]);

            if ($excludeScheduleId) {
                $query->where('id', '!=', $excludeScheduleId);
            }

            // If time is specified, check time conflicts too
            if ($startTime && $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where(function ($q2) use ($startTime, $endTime) {
                        $q2->whereNotNull('start_time')
                           ->whereNotNull('end_time')
                           ->where(function ($q3) use ($startTime, $endTime) {
                               $q3->whereBetween('start_time', [$startTime, $endTime])
                                  ->orWhereBetween('end_time', [$startTime, $endTime])
                                  ->orWhere(function ($q4) use ($startTime, $endTime) {
                                      $q4->where('start_time', '<=', $startTime)
                                         ->where('end_time', '>=', $endTime);
                                  });
                           });
                    })
                    ->orWhereNull('start_time')
                    ->orWhereNull('end_time');
                });
            }

            if ($query->exists()) {
                $user = User::find($userId);
                if ($user) {
                    $conflictingUsers->push($user);
                }
            }
        }

        return $conflictingUsers;
    }
}

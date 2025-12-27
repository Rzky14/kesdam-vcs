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
 * Mengelola operasi CRUD penjadwalan (Dukkes, Jaga, Kegiatan Satuan) termasuk
 * deteksi konflik jadwal personel.
 */
class ScheduleController extends Controller
{
    /**
     * Tampilkan daftar jadwal.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('view_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $query = Schedule::with(['creator', 'updater']);

        // Pembatasan data berdasarkan peran
        if ($user->hasRole('pimpinan') || $user->hasRole('admin_sistem')) {
            // Melihat semua jadwal
        } elseif ($user->hasRole('kasi') || $user->hasRole('kaur')) {
            // Hanya jadwal dalam unit yang sama
            $teamUserIds = User::where('unit', $user->unit)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $query->where(function ($q) use ($teamUserIds) {
                $q->whereIn('created_by', $teamUserIds)
                  ->orWhere(function ($q2) use ($teamUserIds) {
                      foreach ($teamUserIds as $id) {
                          $q2->orWhereJsonContains('personnel', (string) $id);
                      }
                  });
            });
        } else {
            // Batih/Staf hanya jadwal milik sendiri / diikutkan
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereJsonContains('personnel', (string) $user->id);
            });
        }

        // Fitur pencarian
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter berdasarkan tipe
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $schedules = $query->orderBy('start_date', 'desc')
                          ->orderBy('start_time', 'desc')
                          ->paginate(15);

        return view('schedules.index', compact('schedules'));
    }

    /**
     * Tampilkan formulir pembuatan jadwal baru.
     */
    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('create_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        // Ambil semua pengguna aktif untuk pemilihan personel
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('schedules.create', compact('users'));
    }

    /**
     * Simpan jadwal baru.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('create_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
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

        // Cek konflik jadwal jika personel ditugaskan
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

        // Buat jadwal
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

        // Catat pembuatan
        AuditLog::log(
            event: 'schedule_created',
            model: $schedule,
            newValues: $schedule->toArray(),
            description: "Jadwal '{$schedule->title}' dibuat oleh " . $authUser->name
        );

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    /**
     * Tampilkan detail jadwal.
     */
    public function show(Schedule $schedule)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('view_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $schedule->load(['creator', 'updater']);
        
        // Ambil detail personel
        $personnel = $schedule->getPersonnelUsers();
        
        // Ambil log audit untuk jadwal ini
        $auditLogs = AuditLog::where('auditable_type', Schedule::class)
            ->where('auditable_id', $schedule->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('schedules.show', compact('schedule', 'personnel', 'auditLogs'));
    }

    /**
     * Tampilkan formulir untuk mengedit jadwal.
     */
    public function edit(Schedule $schedule)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$user->hasPermission('edit_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        // Ambil semua pengguna aktif untuk pemilihan personel
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->get();

        return view('schedules.edit', compact('schedule', 'users'));
    }

    /**
     * Perbarui jadwal yang dipilih.
     */
    public function update(Request $request, Schedule $schedule)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('edit_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
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

        // Cek konflik jadwal jika personel ditugaskan dan status aktif
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

        // Perbarui jadwal
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

        // Catat pembaruan
        AuditLog::log(
            event: 'schedule_updated',
            model: $schedule,
            oldValues: $oldValues,
            newValues: $schedule->fresh()->toArray(),
            description: "Jadwal '{$schedule->title}' diperbarui oleh " . $authUser->name
        );

        return redirect()->route('schedules.show', $schedule)
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Hapus jadwal.
     */
    public function destroy(Schedule $schedule)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        
        // Pemeriksaan otorisasi
        if (!$authUser->hasPermission('delete_schedules')) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $scheduleTitle = $schedule->title;

        // Catat penghapusan sebelum eksekusi delete
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'schedule_deleted',
            'auditable_type' => Schedule::class,
            'auditable_id' => $schedule->id,
            'old_values' => $schedule->toArray(),
            'new_values' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "Jadwal '{$scheduleTitle}' dihapus oleh " . $authUser->name,
        ]);

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Periksa konflik penjadwalan.
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

            // Jika waktu diisi, cek konflik waktu juga
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




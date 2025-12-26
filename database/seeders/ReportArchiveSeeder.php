<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Arsip;
use App\Models\Dokumen;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReportArchiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Reports and Archives...');

        // Get first user or create one
        $user = User::first();
        if (!$user) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Create Schedule Reports
        $this->command->info('Creating schedule reports...');
        
        $scheduleReport1 = Report::create([
            'name' => 'Laporan Jadwal - ' . Carbon::now()->subMonth()->format('F Y'),
            'type' => 'schedule',
            'period_start' => Carbon::now()->subMonth()->startOfMonth(),
            'period_end' => Carbon::now()->subMonth()->endOfMonth(),
            'created_by' => $user->id,
            'data' => [
                'total_schedules' => 45,
                'by_type' => [
                    'dukkes' => 15,
                    'jaga' => 20,
                    'kegiatan_satuan' => 10,
                ],
                'by_status' => [
                    'active' => 12,
                    'completed' => 30,
                    'cancelled' => 3,
                ],
                'average_duration' => 8.5,
            ],
            'status' => 'generated',
        ]);

        $scheduleReport2 = Report::create([
            'name' => 'Laporan Jadwal - ' . Carbon::now()->format('F Y'),
            'type' => 'schedule',
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now(),
            'created_by' => $user->id,
            'data' => [
                'total_schedules' => 28,
                'by_type' => [
                    'dukkes' => 10,
                    'jaga' => 12,
                    'kegiatan_satuan' => 6,
                ],
                'by_status' => [
                    'active' => 15,
                    'completed' => 10,
                    'draft' => 3,
                ],
                'average_duration' => 9.2,
            ],
            'status' => 'generated',
        ]);

        // Create Document Reports
        $this->command->info('Creating document reports...');
        
        $documentReport1 = Report::create([
            'name' => 'Laporan Dokumen - ' . Carbon::now()->subMonth()->format('F Y'),
            'type' => 'document',
            'period_start' => Carbon::now()->subMonth()->startOfMonth(),
            'period_end' => Carbon::now()->subMonth()->endOfMonth(),
            'created_by' => $user->id,
            'data' => [
                'total_documents' => 125,
                'by_type' => [
                    'masuk' => 68,
                    'keluar' => 57,
                ],
                'by_classification' => [
                    'biasa' => 95,
                    'rahasia' => 25,
                    'telegram' => 5,
                ],
                'by_status' => [
                    'draft' => 12,
                    'pending_approval' => 18,
                    'approved' => 90,
                    'rejected' => 5,
                ],
                'approval_rate' => 85.7,
            ],
            'status' => 'generated',
        ]);

        $documentReport2 = Report::create([
            'name' => 'Laporan Dokumen - ' . Carbon::now()->format('F Y'),
            'type' => 'document',
            'period_start' => Carbon::now()->startOfMonth(),
            'period_end' => Carbon::now(),
            'created_by' => $user->id,
            'data' => [
                'total_documents' => 78,
                'by_type' => [
                    'masuk' => 42,
                    'keluar' => 36,
                ],
                'by_classification' => [
                    'biasa' => 60,
                    'rahasia' => 15,
                    'telegram' => 3,
                ],
                'by_status' => [
                    'draft' => 8,
                    'pending_approval' => 15,
                    'approved' => 52,
                    'rejected' => 3,
                ],
                'approval_rate' => 88.1,
            ],
            'status' => 'generated',
        ]);

        $this->command->info('Reports created: 4 reports');

        // Create Archives for Documents (if Document model exists)
        $this->command->info('Creating archives...');
        
        if (class_exists('App\\Models\\Dokumen')) {
            $documents = Dokumen::limit(5)->get();
            if ($documents->count() > 0) {
                foreach ($documents as $document) {
                    Arsip::create([
                        'archiveable_type' => 'App\\Models\\Dokumen',
                        'archiveable_id' => $document->id,
                        'archived_at' => Carbon::now()->subMonths(rand(1, 6)),
                        'retention_until' => Carbon::now()->addYears(rand(2, 5)),
                        'category' => 'dokumen',
                        'tags' => json_encode(['surat', $document->type ?? 'general', $document->classification ?? 'biasa']),
                        'is_indexed' => true,
                        'notes' => 'Dokumen diarsipkan sesuai prosedur',
                    ]);
                }
                $this->command->info("Document archives created: {$documents->count()} archives");
            }
        } else {
            $this->command->warn('Document model not found, skipping document archives');
        }

        // Create Archives for Schedules (if Schedule model exists)
        if (class_exists('App\\Models\\Schedule')) {
            $schedules = Schedule::limit(5)->get();
            if ($schedules->count() > 0) {
                foreach ($schedules as $schedule) {
                    Arsip::create([
                        'archiveable_type' => 'App\\Models\\Schedule',
                        'archiveable_id' => $schedule->id,
                        'archived_at' => Carbon::now()->subMonths(rand(1, 6)),
                        'retention_until' => Carbon::now()->addYears(rand(1, 3)),
                        'category' => 'jadwal',
                        'tags' => json_encode(['jadwal', $schedule->type ?? 'general']),
                        'is_indexed' => true,
                        'notes' => 'Jadwal yang telah selesai diarsipkan',
                    ]);
                }
                $this->command->info("Schedule archives created: {$schedules->count()} archives");
            }
        } else {
            $this->command->warn('Schedule model not found, skipping schedule archives');
        }

        // Create Archives for Reports
        Arsip::create([
            'archiveable_type' => 'App\\Models\\Report',
            'archiveable_id' => $scheduleReport1->id,
            'archived_at' => Carbon::now()->subMonth(),
            'retention_until' => Carbon::now()->addYears(10),
            'category' => 'laporan',
            'tags' => json_encode(['laporan', 'jadwal', 'bulanan']),
            'is_indexed' => true,
            'notes' => 'Laporan bulanan - archive jangka panjang',
        ]);

        Arsip::create([
            'archiveable_type' => 'App\\Models\\Report',
            'archiveable_id' => $documentReport1->id,
            'archived_at' => Carbon::now()->subMonth(),
            'retention_until' => Carbon::now()->addYears(10),
            'category' => 'laporan',
            'tags' => json_encode(['laporan', 'dokumen', 'bulanan']),
            'is_indexed' => true,
            'notes' => 'Laporan bulanan - archive jangka panjang',
        ]);

        $totalArchives = Arsip::count();
        $this->command->info("Total archives created: {$totalArchives}");

        $this->command->info('✅ Report and Archive seeding completed!');
    }
}

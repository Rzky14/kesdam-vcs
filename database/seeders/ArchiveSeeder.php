<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Arsip;
use App\Models\Dokumen;
use App\Models\Schedule;
use App\Models\Report;
use App\Models\User;

class ArchiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@kesdam.mil.id')->first() ?? User::first();

        if (!$user) {
            return;
        }

        // Archive Documents
        $documents = Dokumen::take(10)->get();
        foreach ($documents as $document) {
            Arsip::create([
                'archivable_type' => Dokumen::class,
                'archivable_id' => $document->id,
                'title' => "Archive: " . $document->subject,
                'description' => "Archived document from " . $document->created_at->format('d M Y'),
                'category' => 'document',
                'indexed_at' => now(),
                'retention_days' => 365,
                'tags' => json_encode(['dokumen', 'aktif', $document->classification]),
            ]);
        }

        // Archive Schedules
        $schedules = Schedule::take(10)->get();
        foreach ($schedules as $schedule) {
            Arsip::create([
                'archivable_type' => Schedule::class,
                'archivable_id' => $schedule->id,
                'title' => "Archive: " . $schedule->type,
                'description' => "Archived schedule from " . $schedule->created_at->format('d M Y'),
                'category' => 'schedule',
                'indexed_at' => now(),
                'retention_days' => 730,
                'tags' => json_encode(['jadwal', 'aktif', $schedule->type]),
            ]);
        }

        // Archive Reports
        $reports = Report::take(5)->get();
        foreach ($reports as $report) {
            Arsip::create([
                'archivable_type' => Report::class,
                'archivable_id' => $report->id,
                'title' => "Archive: " . $report->name,
                'description' => "Archived report from " . $report->created_at->format('d M Y'),
                'category' => 'report',
                'indexed_at' => now(),
                'retention_days' => 1095,
                'tags' => json_encode(['laporan', $report->type]),
            ]);
        }

        $this->command->info('Archive seeder completed successfully!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create sample user
        $user = User::where('email', 'admin@kesdam.mil.id')->first() ?? User::first();
        
        if (!$user) {
            $user = User::factory()->create([
                'email' => 'admin@kesdam.mil.id',
                'name' => 'Admin KESDAM'
            ]);
        }

        // Create Schedule Reports
        for ($i = 1; $i <= 5; $i++) {
            Report::create([
                'name' => "Laporan Jadwal - " . Carbon::now()->subMonths($i)->format('F Y'),
                'type' => 'schedule',
                'period_start' => Carbon::now()->subMonths($i)->startOfMonth(),
                'period_end' => Carbon::now()->subMonths($i)->endOfMonth(),
                'created_by' => $user->id,
                'data' => [
                    'total_schedules' => rand(10, 50),
                    'by_type' => [
                        'Jadwal Dukkes' => rand(5, 15),
                        'Jadwal Jaga' => rand(5, 20),
                        'Jadwal Kegiatan Satuan' => rand(5, 15),
                    ],
                    'by_status' => [
                        'completed' => rand(15, 40),
                        'active' => rand(2, 8),
                        'cancelled' => rand(0, 3),
                    ],
                    'average_duration' => rand(4, 12),
                ],
                'status' => 'generated',
            ]);
        }

        // Create Document Reports
        for ($i = 1; $i <= 5; $i++) {
            Report::create([
                'name' => "Laporan Dokumen - " . Carbon::now()->subMonths($i)->format('F Y'),
                'type' => 'document',
                'period_start' => Carbon::now()->subMonths($i)->startOfMonth(),
                'period_end' => Carbon::now()->subMonths($i)->endOfMonth(),
                'created_by' => $user->id,
                'data' => [
                    'total_documents' => rand(20, 100),
                    'by_type' => [
                        'masuk' => rand(10, 50),
                        'keluar' => rand(10, 50),
                    ],
                    'by_classification' => [
                        'Biasa' => rand(15, 60),
                        'Rahasia' => rand(5, 20),
                        'Telegram' => rand(2, 10),
                    ],
                    'by_status' => [
                        'pending' => rand(0, 10),
                        'approved' => rand(15, 80),
                        'rejected' => rand(0, 5),
                    ],
                    'approval_rate' => round(rand(80, 99), 2),
                    'pending_count' => rand(0, 10),
                    'approved_count' => rand(15, 80),
                    'rejected_count' => rand(0, 5),
                ],
                'status' => 'generated',
            ]);
        }

        // Create Effectiveness Reports
        for ($i = 1; $i <= 3; $i++) {
            Report::create([
                'name' => "Laporan Efektivitas Jadwal - " . Carbon::now()->subMonths($i)->format('F Y'),
                'type' => 'effectiveness',
                'period_start' => Carbon::now()->subMonths($i)->startOfMonth(),
                'period_end' => Carbon::now()->subMonths($i)->endOfMonth(),
                'created_by' => $user->id,
                'data' => [
                    'effectiveness_rate' => round(rand(70, 98), 2),
                    'completed_schedules' => rand(20, 40),
                    'active_schedules' => rand(2, 8),
                    'cancelled_schedules' => rand(0, 3),
                    'total_schedules' => rand(25, 50),
                    'schedule_breakdown' => [
                        'Jadwal Dukkes' => [
                            'total' => rand(5, 15),
                            'completed' => rand(4, 14),
                            'effectiveness' => round(rand(80, 100), 2),
                        ],
                        'Jadwal Jaga' => [
                            'total' => rand(10, 20),
                            'completed' => rand(8, 19),
                            'effectiveness' => round(rand(75, 98), 2),
                        ],
                        'Jadwal Kegiatan Satuan' => [
                            'total' => rand(5, 15),
                            'completed' => rand(4, 14),
                            'effectiveness' => round(rand(70, 95), 2),
                        ],
                    ],
                ],
                'status' => 'generated',
            ]);
        }

        $this->command->info('Report seeder completed successfully!');
    }
}

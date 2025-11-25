<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Schedule;
use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;

echo "========================================\n";
echo "CREATING SAMPLE DATA FOR DASHBOARD\n";
echo "========================================\n\n";

// Get admin user
$admin = User::where('email', 'admin@kesdam.mil.id')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "📝 Creating sample schedules...\n";

// Create 5 sample schedules
$schedules = [
    [
        'type' => 'dukkes',
        'title' => 'Jadwal Dukkes Lapangan',
        'description' => 'Pemeriksaan kesehatan rutin personel satuan lapangan',
        'start_date' => Carbon::now()->addDays(2),
        'end_date' => Carbon::now()->addDays(2),
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'location' => 'Lapangan Kesdam III/Siliwangi',
        'status' => 'active',
        'personnel' => [$admin->id],
        'created_by' => $admin->id,
    ],
    [
        'type' => 'jaga',
        'title' => 'Jadwal Jaga Malam',
        'description' => 'Shift jaga malam minggu ini',
        'start_date' => Carbon::now(),
        'end_date' => Carbon::now()->addDays(7),
        'start_time' => '18:00:00',
        'end_time' => '06:00:00',
        'location' => 'Pos Jaga Utama',
        'status' => 'active',
        'personnel' => [$admin->id],
        'created_by' => $admin->id,
    ],
    [
        'type' => 'kegiatan_satuan',
        'title' => 'Kegiatan Satuan',
        'description' => 'Kegiatan rutin satuan',
        'start_date' => Carbon::now()->addDays(5),
        'end_date' => Carbon::now()->addDays(5),
        'start_time' => '07:00:00',
        'end_time' => '12:00:00',
        'location' => 'Aula Kesdam',
        'status' => 'draft',
        'personnel' => [$admin->id],
        'created_by' => $admin->id,
    ],
];

foreach ($schedules as $data) {
    Schedule::create($data);
    echo "   ✅ Created: {$data['title']}\n";
}

echo "\n📄 Creating sample documents...\n";

// Create 8 sample documents
$documents = [
    [
        'number' => 'SM/001/XI/2025',
        'type' => 'masuk',
        'classification' => 'biasa',
        'subject' => 'Surat Perintah Tugas',
        'description' => 'Surat perintah tugas dari Kodam III/Siliwangi',
        'sender' => 'Kodam III/Siliwangi',
        'recipient' => null,
        'date' => Carbon::today(),
        'status' => 'approved',
        'priority' => 'normal',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SK/002/XI/2025',
        'type' => 'keluar',
        'classification' => 'biasa',
        'subject' => 'Laporan Kegiatan Bulanan',
        'description' => 'Laporan kegiatan bulan November 2025',
        'sender' => null,
        'recipient' => 'Mabes TNI',
        'date' => Carbon::today(),
        'status' => 'pending_approval',
        'priority' => 'high',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SM-R/003/XI/2025',
        'type' => 'masuk',
        'classification' => 'rahasia',
        'subject' => 'Surat Rahasia Operasi',
        'description' => 'Informasi rahasia mengenai operasi khusus',
        'sender' => 'Kodam 062/TN',
        'recipient' => null,
        'date' => Carbon::today(),
        'status' => 'approved',
        'priority' => 'urgent',
        'is_encrypted' => true,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SK/004/XI/2025',
        'type' => 'keluar',
        'classification' => 'biasa',
        'subject' => 'Permohonan Dukungan Logistik',
        'description' => 'Permohonan dukungan logistik untuk kegiatan satuan',
        'sender' => null,
        'recipient' => 'Kodam III/Siliwangi',
        'date' => Carbon::today()->subDays(1),
        'status' => 'pending_approval',
        'priority' => 'normal',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SM/005/XI/2025',
        'type' => 'masuk',
        'classification' => 'telegram',
        'subject' => 'Telegram Sitrep Harian',
        'description' => 'Situation report harian dari lapangan',
        'sender' => 'Satuan Lapangan',
        'recipient' => null,
        'date' => Carbon::today(),
        'status' => 'approved',
        'priority' => 'urgent',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SK/006/XI/2025',
        'type' => 'keluar',
        'classification' => 'biasa',
        'subject' => 'Undangan Rapat Koordinasi',
        'description' => 'Undangan rapat koordinasi bulanan',
        'sender' => null,
        'recipient' => 'Seluruh Satuan',
        'date' => Carbon::today()->subDays(2),
        'status' => 'approved',
        'priority' => 'normal',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SM/007/XI/2025',
        'type' => 'masuk',
        'classification' => 'biasa',
        'subject' => 'Pemberitahuan Jadwal Inspeksi',
        'description' => 'Jadwal inspeksi rutin bulan Desember',
        'sender' => 'Tim Inspektorat',
        'recipient' => null,
        'date' => Carbon::today(),
        'status' => 'approved',
        'priority' => 'high',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
    [
        'number' => 'SK/008/XI/2025',
        'type' => 'keluar',
        'classification' => 'biasa',
        'subject' => 'Surat Keterangan Tugas',
        'description' => 'Surat keterangan tugas untuk personel',
        'sender' => null,
        'recipient' => 'Personel Satuan',
        'date' => Carbon::today(),
        'status' => 'pending_approval',
        'priority' => 'normal',
        'is_encrypted' => false,
        'created_by' => $admin->id,
    ],
];

foreach ($documents as $data) {
    Document::create($data);
    echo "   ✅ Created: {$data['subject']}\n";
}

echo "\n========================================\n";
echo "SAMPLE DATA CREATED SUCCESSFULLY!\n";
echo "========================================\n\n";

echo "📊 Summary:\n";
echo "   - Schedules: " . Schedule::count() . "\n";
echo "   - Documents: " . Document::count() . "\n";
echo "   - Active Schedules: " . Schedule::where('status', 'active')->count() . "\n";
echo "   - Incoming Documents (Today): " . Document::where('type', 'masuk')->whereDate('date', Carbon::today())->count() . "\n";
echo "   - Pending Approvals: " . Document::where('status', 'pending_approval')->count() . "\n";
echo "   - Completed This Month: " . Document::where('status', 'approved')->whereMonth('updated_at', Carbon::now()->month)->count() . "\n";

echo "\n✅ Dashboard ready to test at: http://127.0.0.1:8000/dashboard\n";
echo "========================================\n";

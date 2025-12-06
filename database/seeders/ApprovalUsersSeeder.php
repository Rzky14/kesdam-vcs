<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ApprovalUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates test users for each approval level:
     * - KAUR (Kepala Urusan) - Level 1 Approver
     * - KASI (Kepala Seksi) - Level 2 Approver
     * - PIMPINAN (Pejabat Tinggi) - Level 3 Final Approver
     * - STAF (Staff) - Document Creator (cannot approve)
     */
    public function run(): void
    {
        $this->command->info('Creating approval workflow users...');

        // 1. Create KAUR (Kepala Urusan) - Level 1 Approver
        $kaur = User::firstOrCreate(
            ['email' => 'kaur@kesdam.mil.id'],
            [
                'name' => 'Kapten Ahmad Fauzi',
                'password' => Hash::make('kaur123'),
                'nrp' => '210001',
                'rank' => 'Kapten',
                'position' => 'Kepala Urusan Administrasi',
                'unit' => 'KESDAM III/Siliwangi',
                'phone' => '081234567801',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $kaurRole = Role::where('name', 'kaur')->first();
        if ($kaurRole && !$kaur->roles()->where('role_id', $kaurRole->id)->exists()) {
            $kaur->roles()->attach($kaurRole->id);
        }

        // 2. Create KASI (Kepala Seksi) - Level 2 Approver
        $kasi = User::firstOrCreate(
            ['email' => 'kasi@kesdam.mil.id'],
            [
                'name' => 'Mayor Budi Santoso',
                'password' => Hash::make('kasi123'),
                'nrp' => '210002',
                'rank' => 'Mayor',
                'position' => 'Kepala Seksi Operasional',
                'unit' => 'KESDAM III/Siliwangi',
                'phone' => '081234567802',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $kasiRole = Role::where('name', 'kasi')->first();
        if ($kasiRole && !$kasi->roles()->where('role_id', $kasiRole->id)->exists()) {
            $kasi->roles()->attach($kasiRole->id);
        }

        // 3. Create PIMPINAN (Pejabat Tinggi) - Level 3 Final Approver
        $pimpinan = User::firstOrCreate(
            ['email' => 'pimpinan@kesdam.mil.id'],
            [
                'name' => 'Kolonel Ir. Suryadi, M.M.',
                'password' => Hash::make('pimpinan123'),
                'nrp' => '210003',
                'rank' => 'Kolonel',
                'position' => 'Kepala Kesdam III/Siliwangi',
                'unit' => 'KESDAM III/Siliwangi',
                'phone' => '081234567803',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $pimpinanRole = Role::where('name', 'pimpinan')->first();
        if ($pimpinanRole && !$pimpinan->roles()->where('role_id', $pimpinanRole->id)->exists()) {
            $pimpinan->roles()->attach($pimpinanRole->id);
        }

        // 4. Create STAF (Staff) - Document Creator (for testing)
        $staf = User::firstOrCreate(
            ['email' => 'staf@kesdam.mil.id'],
            [
                'name' => 'Sersan Dua Rizky Pratama',
                'password' => Hash::make('staf123'),
                'nrp' => '210004',
                'rank' => 'Serda',
                'position' => 'Staf Administrasi',
                'unit' => 'KESDAM III/Siliwangi',
                'phone' => '081234567804',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $stafRole = Role::where('name', 'staf')->first();
        if ($stafRole && !$staf->roles()->where('role_id', $stafRole->id)->exists()) {
            $staf->roles()->attach($stafRole->id);
        }

        // 5. Create BATIH (Bawahan) - Document Creator (for testing)
        $batih = User::firstOrCreate(
            ['email' => 'batih@kesdam.mil.id'],
            [
                'name' => 'Kopral Satu Andi Wijaya',
                'password' => Hash::make('batih123'),
                'nrp' => '210005',
                'rank' => 'Koptu',
                'position' => 'Bawahan Langsung',
                'unit' => 'KESDAM III/Siliwangi',
                'phone' => '081234567805',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $batihRole = Role::where('name', 'batih')->first();
        if ($batihRole && !$batih->roles()->where('role_id', $batihRole->id)->exists()) {
            $batih->roles()->attach($batihRole->id);
        }

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✅ Approval Users Created Successfully!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('📋 APPROVAL WORKFLOW USERS:');
        $this->command->info('');
        $this->command->info('1️⃣  LEVEL 1 - KAUR (Kepala Urusan)');
        $this->command->info('   Email    : kaur@kesdam.mil.id');
        $this->command->info('   Password : kaur123');
        $this->command->info('   Name     : Kapten Ahmad Fauzi');
        $this->command->info('   Role     : Approve dokumen di level pertama');
        $this->command->info('');
        $this->command->info('2️⃣  LEVEL 2 - KASI (Kepala Seksi)');
        $this->command->info('   Email    : kasi@kesdam.mil.id');
        $this->command->info('   Password : kasi123');
        $this->command->info('   Name     : Mayor Budi Santoso');
        $this->command->info('   Role     : Approve dokumen setelah KAUR');
        $this->command->info('');
        $this->command->info('3️⃣  LEVEL 3 - PIMPINAN (Final Approver)');
        $this->command->info('   Email    : pimpinan@kesdam.mil.id');
        $this->command->info('   Password : pimpinan123');
        $this->command->info('   Name     : Kolonel Ir. Suryadi, M.M.');
        $this->command->info('   Role     : Final approval setelah KASI');
        $this->command->info('');
        $this->command->info('📝 DOCUMENT CREATORS (for testing):');
        $this->command->info('');
        $this->command->info('4️⃣  STAF (Staff)');
        $this->command->info('   Email    : staf@kesdam.mil.id');
        $this->command->info('   Password : staf123');
        $this->command->info('   Name     : Sersan Dua Rizky Pratama');
        $this->command->info('   Role     : Submit dokumen (cannot approve)');
        $this->command->info('');
        $this->command->info('5️⃣  BATIH (Bawahan)');
        $this->command->info('   Email    : batih@kesdam.mil.id');
        $this->command->info('   Password : batih123');
        $this->command->info('   Name     : Kopral Satu Andi Wijaya');
        $this->command->info('   Role     : Submit dokumen (cannot approve)');
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('🔄 APPROVAL FLOW:');
        $this->command->info('STAF/BATIH submit → KAUR approve → KASI approve → PIMPINAN approve → ✅ APPROVED');
        $this->command->info('========================================');
    }
}

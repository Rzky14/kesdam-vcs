<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nrp')->unique()->nullable()->after('id'); // Nomor Registrasi Pokok (Military ID)
            $table->string('rank')->nullable()->after('nrp'); // Pangkat
            $table->string('position')->nullable()->after('rank'); // Jabatan
            $table->string('unit')->nullable()->after('position'); // Satuan
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('profile_photo')->nullable()->after('address');
            $table->boolean('is_active')->default(true)->after('profile_photo');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nrp',
                'rank',
                'position',
                'unit',
                'phone',
                'address',
                'profile_photo',
                'is_active',
                'last_login_at'
            ]);
        });
    }
};

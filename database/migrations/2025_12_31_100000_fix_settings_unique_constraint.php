<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus unique constraint yang lama
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });
        
        // Tambah unique constraint baru untuk kombinasi key + user_id
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['key', 'user_id'], 'settings_key_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_user_unique');
        });
        
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('key');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel settings.
 * 
 * Tabel ini menyimpan pengaturan sistem dan preferensi pengguna.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel settings.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            
            // Key unik untuk setting
            $table->string('key', 100)->unique()->comment('Kunci unik setting');
            
            // Value setting (JSON untuk fleksibilitas)
            $table->text('value')->nullable()->comment('Nilai setting');
            
            // Tipe setting
            $table->enum('type', ['system', 'notification', 'user'])->default('system')->comment('Tipe setting');
            
            // User ID (jika setting per user)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('ID user (untuk user-specific settings)');
            
            // Metadata
            $table->string('group', 50)->nullable()->comment('Grup setting');
            $table->text('description')->nullable()->comment('Deskripsi setting');
            
            // Status
            $table->boolean('is_active')->default(true)->comment('Status aktif');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['user_id', 'type']);
            $table->index('group');
        });
    }

    /**
     * Batalkan migrasi - hapus tabel settings.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

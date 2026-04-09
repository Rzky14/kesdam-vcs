<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel lampiran.
 * 
 * Tabel ini menyimpan data file lampiran yang terkait dengan dokumen.
 * Sesuai dengan UML Class Diagram - entitas Lampiran.
 */
return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel lampiran.
     */
    public function up(): void
    {
        Schema::create('lampiran', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign key ke tabel dokumen (documents)
            $table->foreignId('dokumen_id')
                ->constrained('documents')
                ->onDelete('cascade')
                ->comment('Referensi ke dokumen induk');
            
            // Atribut file sesuai UML Class Diagram
            $table->string('nama_file', 255)
                ->comment('Nama file asli yang diunggah');
            
            $table->string('tipe_file', 100)
                ->comment('Tipe/mime type file (pdf, docx, jpg, dll)');
            
            $table->unsignedBigInteger('ukuran_file')
                ->comment('Ukuran file dalam bytes');
            
            $table->string('path_file', 500)
                ->comment('Path lokasi penyimpanan file');
            
            $table->timestamp('tanggal_unggah')
                ->useCurrent()
                ->comment('Tanggal dan waktu file diunggah');
            
            // Atribut keamanan
            $table->boolean('is_encrypted')
                ->default(false)
                ->comment('Apakah file terenkripsi');
            
            $table->string('encryption_key_id')
                ->nullable()
                ->comment('ID kunci enkripsi jika file terenkripsi');
            
            // Relasi pengguna
            $table->foreignId('diunggah_oleh')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User yang mengunggah file');
            
            // Metadata tambahan
            $table->string('hash_file', 64)
                ->nullable()
                ->comment('Hash SHA-256 untuk verifikasi integritas file');
            
            $table->text('keterangan')
                ->nullable()
                ->comment('Deskripsi atau keterangan file');
            
            $table->boolean('is_active')
                ->default(true)
                ->comment('Status aktif lampiran');
            
            // Timestamps standar Laravel
            $table->timestamps();
            
            // Soft delete
            $table->softDeletes();
            
            // Indexes
            $table->index('dokumen_id', 'idx_lampiran_dokumen');
            $table->index('diunggah_oleh', 'idx_lampiran_pengunggah');
            $table->index('tipe_file', 'idx_lampiran_tipe');
            $table->index('tanggal_unggah', 'idx_lampiran_tanggal');
            $table->index(['is_active', 'deleted_at'], 'idx_lampiran_status');
        });
    }

    /**
     * Batalkan migrasi - hapus tabel lampiran.
     */
    public function down(): void
    {
        Schema::dropIfExists('lampiran');
    }
};

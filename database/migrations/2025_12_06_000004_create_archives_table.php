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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            
            // Archive Information
            $table->string('name');
            $table->text('description')->nullable();
            
            // Polymorphic relationship to archiveable model (Document, Schedule, Report, etc.)
            $table->string('archiveable_type');
            $table->unsignedBigInteger('archiveable_id');
            $table->index(['archiveable_type', 'archiveable_id'], 'archiveable_index');
            
            // Archive Metadata
            $table->foreignId('archived_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('archive_date')->useCurrent();
            $table->date('retention_until')->nullable()->comment('Tanggal batas retensi arsip');
            
            // Archive Classification
            $table->string('category')->default('general')->comment('Kategori arsip: general, document, schedule, report');
            $table->json('tags')->nullable()->comment('Tag untuk kategorisasi dan pencarian');
            
            // Indexing & Search
            $table->boolean('is_indexed')->default(false)->comment('Apakah arsip sudah diindeks untuk pencarian');
            $table->index('is_indexed');
            $table->index('category');
            $table->index('archive_date');
            $table->index('retention_until');
            
            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};

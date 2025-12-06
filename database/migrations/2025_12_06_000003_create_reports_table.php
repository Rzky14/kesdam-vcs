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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['schedule', 'document']); // Jenis laporan
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->json('data')->nullable(); // Data laporan dalam JSON
            $table->enum('status', ['generated', 'exported'])->default('generated');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'created_at']);
            $table->index(['created_by']);
            $table->index(['period_start', 'period_end']);
        });

        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('archiveable_type'); // Document, Schedule, Report
            $table->unsignedBigInteger('archiveable_id');
            $table->foreignId('archived_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archive_date');
            $table->timestamp('retention_until')->nullable();
            $table->string('category'); // schedule, document, report
            $table->json('tags')->nullable();
            $table->boolean('is_indexed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['archiveable_type', 'archiveable_id']);
            $table->index(['category', 'archive_date']);
            $table->index(['is_indexed']);
            $table->fullText(['name', 'description']); // Full text search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
        Schema::dropIfExists('reports');
    }
};

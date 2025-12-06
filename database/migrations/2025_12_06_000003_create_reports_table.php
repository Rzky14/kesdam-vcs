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
            
            // Polymorphic relationship
            $table->string('reportable_type')->nullable();
            $table->unsignedBigInteger('reportable_id')->nullable();
            
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->json('data')->nullable(); // Data laporan dalam JSON
            $table->enum('status', ['draft', 'in_progress', 'completed', 'failed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'created_at']);
            $table->index(['generated_by']);
            $table->index(['period_start', 'period_end']);
            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

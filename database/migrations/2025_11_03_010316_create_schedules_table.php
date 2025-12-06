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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            // Schedule type: dukkes, jaga, kegiatan_satuan
            $table->enum('type', ['dukkes', 'jaga', 'kegiatan_satuan']);
            
            // Basic information
            $table->string('title');
            $table->text('description')->nullable();
            
            // Date and time
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Location
            $table->string('location')->nullable();
            
            // Personnel involved (JSON array of user IDs)
            $table->json('personnel')->nullable();
            
            // Status: draft, active, completed, cancelled
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            
            // Additional notes
            $table->text('notes')->nullable();
            
            // Created by user
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Updated by user (nullable for initial creation)
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

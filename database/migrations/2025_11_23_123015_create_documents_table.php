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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            // Document Type: masuk (incoming) or keluar (outgoing)
            $table->enum('type', ['masuk', 'keluar']);
            
            // Document Classification: biasa, rahasia, telegram
            $table->enum('classification', ['biasa', 'rahasia', 'telegram']);
            
            // Document Number (auto-generated or manual)
            $table->string('number')->unique();
            
            // Document Date
            $table->date('date');
            
            // Sender (for incoming) or Recipient (for outgoing)
            $table->string('sender')->nullable(); // For surat masuk
            $table->string('recipient')->nullable(); // For surat keluar
            
            // Document Details
            $table->string('subject');
            $table->text('description')->nullable();
            
            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();
            
            // Document Status
            // draft, pending_approval, approved, rejected, archived
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'archived'])
                  ->default('draft');
            
            // Priority Level (optional)
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            
            // For encrypted documents (surat rahasia)
            $table->boolean('is_encrypted')->default(false);
            
            // Archived Date
            $table->date('archived_at')->nullable();
            
            // Foreign Keys
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better search performance
            $table->index('type');
            $table->index('classification');
            $table->index('status');
            $table->index('date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

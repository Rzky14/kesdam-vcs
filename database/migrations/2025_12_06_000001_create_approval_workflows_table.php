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
        // Approval Workflow Configuration Table
        // Defines the approval chain for documents based on document type and classification
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Standard Document Approval", "Classified Document Approval"
            $table->string('document_type')->nullable(); // 'masuk', 'keluar', or null for all
            $table->string('classification')->nullable(); // 'biasa', 'rahasia', 'telegram', or null for all
            $table->json('approval_chain'); // Array of role IDs in order of approval
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority workflows take precedence
            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_type', 'classification']);
            $table->index(['is_active', 'priority']);
        });

        // Approval History Table
        // Tracks all approval actions on documents
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // 'submitted', 'approved', 'rejected', 'correction_requested', 'resubmitted'
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected', 'correction_requested'
            $table->text('comment')->nullable(); // Reason for rejection or correction request
            $table->string('current_approver_role')->nullable(); // The role that should approve at this stage
            $table->integer('approval_level')->default(1); // Which level in approval chain
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('action_date');
            $table->timestamps();

            $table->index(['document_id', 'approval_level']);
            $table->index(['user_id', 'action']);
            $table->index('action_date');
        });

        // Approval Signatures Table
        // Stores manual signature uploads for documents that have been approved
        Schema::create('approval_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_history_id')->constrained('approval_histories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('signature_file_path'); // Path to uploaded signature image
            $table->string('signature_type'); // 'digital', 'scanned', 'uploaded_image'
            $table->timestamp('signed_at');
            $table->string('certificate_number')->nullable(); // For digital signatures
            $table->json('metadata')->nullable(); // Device info, etc.
            $table->timestamps();

            $table->index(['user_id', 'signed_at']);
            $table->unique(['approval_history_id']);
        });

        // Correction Requests Table
        // Tracks requests for document corrections
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('approval_history_id')->constrained('approval_histories')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade'); // Approver who requested
            $table->foreignId('assigned_to_user_id')->constrained('users')->onDelete('cascade'); // Original creator/owner
            $table->text('correction_notes'); // What needs to be corrected
            $table->string('status')->default('pending'); // 'pending', 'in_progress', 'completed', 'rejected'
            $table->integer('revision_number')->default(1);
            $table->timestamp('requested_at');
            $table->timestamp('corrected_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index('requested_at');
        });

        // Approval Permissions Cache Table
        // Cache which roles can approve at each level
        Schema::create('approval_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('action'); // 'view', 'submit', 'approve', 'request_correction'
            $table->string('document_type')->nullable(); // Apply to specific document type or null for all
            $table->string('classification')->nullable(); // Apply to specific classification or null for all
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['role_id', 'action', 'document_type', 'classification'], 'arp_role_action_type_class');
            $table->index(['role_id', 'action']);
        });

        // Approval Deadlines Table (optional, for SLA tracking)
        Schema::create('approval_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->string('approval_level'); // Which level this deadline applies to
            $table->string('document_type'); // 'masuk', 'keluar'
            $table->string('classification'); // 'biasa', 'rahasia', 'telegram'
            $table->integer('days_allowed')->default(3); // How many days to complete this approval level
            $table->timestamp('deadline_at');
            $table->string('status')->default('active'); // 'active', 'met', 'missed', 'waived'
            $table->timestamps();

            $table->index(['document_id', 'approval_level']);
            $table->index(['status', 'deadline_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_deadlines');
        Schema::dropIfExists('approval_role_permissions');
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('approval_signatures');
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('approval_workflows');
    }
};

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
        // Tabel untuk menyimpan notifikasi
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // 'approval_request', 'document_status', 'schedule_reminder', 'system'
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable(); // icon class untuk UI
            $table->string('action_url')->nullable(); // URL untuk link notifikasi
            $table->string('related_model_type')->nullable(); // Model type (Document, Schedule, etc)
            $table->unsignedBigInteger('related_model_id')->nullable(); // Model ID
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type']);
        });

        // Tabel untuk user notification preferences
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('approval_request_enabled')->default(true);
            $table->boolean('approval_request_email')->default(true);
            $table->boolean('document_status_enabled')->default(true);
            $table->boolean('document_status_email')->default(true);
            $table->boolean('schedule_reminder_enabled')->default(true);
            $table->boolean('schedule_reminder_email')->default(false);
            $table->boolean('system_notifications_enabled')->default(true);
            $table->string('schedule_reminder_timing')->default('1-day'); // '1-day', 'day-of', '1-hour'
            $table->timestamps();
        });

        // Tabel untuk tracking notifikasi yang sudah dikirim (untuk menghindari duplikasi)
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // 'in_app', 'email', 'sms'
            $table->string('status'); // 'sent', 'failed', 'pending'
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('notifications');
    }
};

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
        Schema::table('reports', function (Blueprint $table) {
            // Modify generated_by to allow nullable and set default to user with id 1 (admin)
            $table->unsignedBigInteger('generated_by')->nullable()->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Revert to required foreign key
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete()->change();
        });
    }
};

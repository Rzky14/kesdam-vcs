<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop approval-related tables if they exist
        Schema::dropIfExists('approval_deadlines');
        Schema::dropIfExists('approval_role_permissions');
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('approval_signatures');
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('approval_workflows');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do on rollback
    }
};

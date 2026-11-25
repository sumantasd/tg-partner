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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
            $table->string('service_category', 150)->nullable()->after('project_title');
            $table->date('start_date')->nullable()->after('rejection_reason');
            $table->date('expected_completion_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['service_category', 'start_date', 'expected_completion_date']);
        });
    }
};

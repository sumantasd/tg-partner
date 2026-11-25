<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commission_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('commission_type', 30)->default('percentage'); // percentage, fixed
            $table->decimal('commission_value', 12, 2)->default(10.00); // % or INR
            $table->string('fixed_frequency', 50)->nullable(); // per_payment, per_project, per_milestone
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 30)->default('active'); // active, inactive, superseded
            $table->boolean('is_default')->default(false); // Global platform default policy
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_policy_id')->nullable()->constrained('commission_policies')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_default', 'status', 'effective_from']);
        });

        // Automatically seed the baseline v1.0 Default Policy using existing system setting
        $currentRate = 10.00;
        if (Schema::hasTable('system_settings')) {
            $setting = DB::table('system_settings')->where('key', 'default_commission_rate')->first();
            if ($setting && is_numeric($setting->value)) {
                $currentRate = (float) $setting->value;
            }
        }

        $adminUser = DB::table('users')->where('role', 'admin')->first();

        DB::table('commission_policies')->insert([
            'policy_code' => 'POL-DEF-V1',
            'name' => 'Standard Global Commission Policy',
            'description' => 'Default platform commission rate established at project inception. Applies automatically to all partner referrals unless a project-specific rate is negotiated.',
            'commission_type' => 'percentage',
            'commission_value' => $currentRate,
            'fixed_frequency' => null,
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'status' => 'active',
            'is_default' => true,
            'version' => 1,
            'parent_policy_id' => null,
            'notes' => 'Baseline policy migrated safely from initial platform system settings.',
            'created_by' => $adminUser ? $adminUser->id : null,
            'updated_by' => $adminUser ? $adminUser->id : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_policies');
    }
};

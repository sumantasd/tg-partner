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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_logout_at')->nullable()->after('last_login_at');
            $table->timestamp('last_meaningful_activity_at')->nullable()->after('last_logout_at');
            $table->timestamp('last_lead_submission_at')->nullable()->after('last_meaningful_activity_at');
            $table->timestamp('last_lead_update_at')->nullable()->after('last_lead_submission_at');
            $table->timestamp('last_customer_interaction_at')->nullable()->after('last_lead_update_at');
            $table->string('inactivity_status', 30)->default('active')->after('last_customer_interaction_at');
            $table->timestamp('inactivity_warning_sent_at')->nullable()->after('inactivity_status');
            $table->timestamp('inactivity_alert_sent_at')->nullable()->after('inactivity_warning_sent_at');
            $table->timestamp('inactivity_critical_sent_at')->nullable()->after('inactivity_alert_sent_at');
            $table->boolean('inactivity_flagged_for_review')->default(false)->after('inactivity_critical_sent_at');
            $table->timestamp('inactivity_flagged_at')->nullable()->after('inactivity_flagged_for_review');
            $table->foreignId('inactivity_flagged_by')->nullable()->after('inactivity_flagged_at')->constrained('users')->nullOnDelete();
            $table->text('inactivity_review_reason')->nullable()->after('inactivity_flagged_by');

            $table->index('inactivity_status');
            $table->index('last_meaningful_activity_at');
        });

        // Seed default inactivity thresholds in system_settings
        $defaultSettings = [
            'inactivity_enabled' => 'true',
            'inactivity_partner_warning_days' => '30',
            'inactivity_partner_alert_days' => '60',
            'inactivity_partner_critical_days' => '90',
            'inactivity_staff_warning_days' => '30',
            'inactivity_staff_alert_days' => '60',
            'inactivity_staff_critical_days' => '90',
        ];

        foreach ($defaultSettings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Backfill existing users: set last_meaningful_activity_at to last_login_at or created_at
        DB::table('users')->whereNull('last_meaningful_activity_at')->update([
            'last_meaningful_activity_at' => DB::raw('COALESCE(last_login_at, created_at)'),
            'inactivity_status' => 'active',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['inactivity_status']);
            $table->dropIndex(['last_meaningful_activity_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['inactivity_flagged_by']);
            $table->dropColumn([
                'last_logout_at',
                'last_meaningful_activity_at',
                'last_lead_submission_at',
                'last_lead_update_at',
                'last_customer_interaction_at',
                'inactivity_status',
                'inactivity_warning_sent_at',
                'inactivity_alert_sent_at',
                'inactivity_critical_sent_at',
                'inactivity_flagged_for_review',
                'inactivity_flagged_at',
                'inactivity_flagged_by',
                'inactivity_review_reason',
            ]);
        });
    }
};

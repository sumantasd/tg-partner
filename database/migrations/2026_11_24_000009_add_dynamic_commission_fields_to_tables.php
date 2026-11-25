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
        // 1. Extend projects table with dynamic commission configuration
        Schema::table('projects', function (Blueprint $table) {
            $table->string('commission_model', 30)->default('global_default')->index()->after('status'); // global_default, custom
            $table->string('custom_commission_type', 30)->nullable()->after('commission_model'); // percentage, fixed
            $table->decimal('custom_commission_rate', 14, 2)->nullable()->after('custom_commission_type');
            $table->string('custom_fixed_frequency', 30)->nullable()->after('custom_commission_rate'); // per_payment, per_project, per_milestone
            $table->date('commission_effective_date')->nullable()->after('custom_fixed_frequency');
            $table->string('commission_status', 30)->default('active')->after('commission_effective_date'); // active, inactive
            $table->foreignId('commission_policy_id')->nullable()->after('commission_status')->constrained('commission_policies')->nullOnDelete();
            $table->text('commission_notes')->nullable()->after('commission_policy_id');
        });

        // 2. Extend customer_payments table with billing month & period tracking
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->string('billing_month', 7)->nullable()->index()->after('payment_type'); // e.g. '2026-11'
            $table->string('billing_period', 100)->nullable()->after('billing_month'); // e.g. 'November 2026'
        });

        // 3. Extend commissions table with historical policy snapshot & adjustment references
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('policy_source', 30)->default('global_policy')->index()->after('commission_type'); // global_policy, project_override, manual
            $table->foreignId('commission_policy_id')->nullable()->after('policy_source')->constrained('commission_policies')->nullOnDelete();
            $table->unsignedInteger('policy_version')->nullable()->after('commission_policy_id');
            $table->string('policy_code', 50)->nullable()->after('policy_version');
            $table->string('commission_frequency', 30)->nullable()->after('policy_code'); // per_payment, per_project, per_milestone
            $table->string('billing_month', 7)->nullable()->index()->after('commission_frequency');
            $table->string('billing_period', 100)->nullable()->after('billing_month');
            $table->date('effective_policy_date')->nullable()->after('billing_period');
            $table->timestamp('calculated_at')->nullable()->after('effective_policy_date');
            $table->foreignId('parent_commission_id')->nullable()->after('paid_at')->constrained('commissions')->nullOnDelete();
            $table->string('adjustment_type', 30)->nullable()->after('parent_commission_id'); // reversal, recovery, adjustment
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commission_policy_id');
            $table->dropConstrainedForeignId('parent_commission_id');
            $table->dropIndex(['policy_source']);
            $table->dropIndex(['billing_month']);
            $table->dropColumn([
                'policy_source',
                'policy_version',
                'policy_code',
                'commission_frequency',
                'billing_month',
                'billing_period',
                'effective_policy_date',
                'calculated_at',
                'adjustment_type',
            ]);
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropIndex(['billing_month']);
            $table->dropColumn(['billing_month', 'billing_period']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commission_policy_id');
            $table->dropIndex(['commission_model']);
            $table->dropColumn([
                'commission_model',
                'custom_commission_type',
                'custom_commission_rate',
                'custom_fixed_frequency',
                'commission_effective_date',
                'commission_status',
                'commission_notes',
            ]);
        });
    }
};


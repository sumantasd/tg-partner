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
        // 1. Add payment type and monthly schedule configurations to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('single')->index()->after('final_deal_value'); // single, monthly
            $table->decimal('monthly_amount', 14, 2)->nullable()->after('payment_type');
            $table->unsignedInteger('duration_months')->nullable()->after('monthly_amount');
            $table->date('billing_start_date')->nullable()->after('duration_months');
            $table->unsignedTinyInteger('monthly_due_day')->nullable()->after('billing_start_date');
            $table->date('first_payment_due_date')->nullable()->after('monthly_due_day');
            $table->unsignedSmallInteger('grace_period_days')->default(0)->after('first_payment_due_date');
            $table->boolean('schedules_paused')->default(false)->after('grace_period_days');
            $table->timestamp('schedules_paused_at')->nullable()->after('schedules_paused');
        });

        // 2. Create project_payment_schedules table
        Schema::create('project_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_number');
            $table->string('billing_month', 7)->index(); // 'YYYY-MM'
            $table->string('billing_period', 100); // 'Month Year'
            $table->date('due_date')->index();
            $table->decimal('expected_amount', 14, 2);
            $table->decimal('received_amount', 14, 2)->default(0.00);
            $table->decimal('outstanding_amount', 14, 2);
            $table->string('status', 35)->default('upcoming')->index(); // upcoming, due, partially_paid, paid_pending_verification, verified, overdue, cancelled
            $table->foreignId('customer_payment_id')->nullable()->constrained('customer_payments')->nullOnDelete();
            $table->boolean('is_paused')->default(false);
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'billing_month'], 'uq_project_schedule_month');
            $table->index(['project_id', 'status'], 'idx_project_schedule_status');
        });

        // 3. Add schedule reference to customer_payments
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->foreignId('payment_schedule_id')->nullable()->after('billing_period')->constrained('project_payment_schedules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_schedule_id');
        });

        Schema::dropIfExists('project_payment_schedules');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropColumn([
                'payment_type',
                'monthly_amount',
                'duration_months',
                'billing_start_date',
                'monthly_due_day',
                'first_payment_due_date',
                'grace_period_days',
                'schedules_paused',
                'schedules_paused_at',
            ]);
        });
    }
};

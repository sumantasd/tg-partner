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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number', 32)->unique();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Team Member
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete(); // Admin
            $table->timestamp('assigned_at')->nullable();
            $table->string('customer_name', 150);
            $table->string('customer_company', 200)->nullable();
            $table->string('customer_phone', 30)->index();
            $table->string('customer_email', 255)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->string('service_required', 150);
            $table->text('project_description');
            $table->decimal('estimated_value', 14, 2)->nullable();
            $table->char('currency', 3)->default('INR');
            $table->string('status', 50)->default('submitted')->index();
            $table->string('interest_level', 50)->nullable()->index();
            $table->decimal('quotation_value', 14, 2)->nullable();
            $table->timestamp('quotation_sent_at')->nullable();
            $table->date('next_followup_date')->nullable()->index();
            $table->text('loss_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_duplicate_flag')->default(false)->index();
            $table->foreignId('duplicate_matched_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_phone', 'customer_email']);
            $table->index(['partner_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

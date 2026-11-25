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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('commission_number', 32)->unique();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->string('milestone_name', 100)->default('Full Project');
            $table->unsignedTinyInteger('milestone_sequence')->default(1);
            $table->string('commission_type', 20)->default('percentage'); // percentage | fixed
            $table->decimal('calculation_basis', 14, 2)->default(0.00);
            $table->decimal('rate_applied', 5, 2)->default(0.00);
            $table->decimal('amount', 14, 2)->default(0.00); // Authoritative ledger value
            $table->char('currency', 3)->default('INR');
            $table->string('status', 50)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payable_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps(); // Authoritative financial ledger - NO softDeletes

            // Unique constraint prevents duplicate commissions for the same milestone
            $table->unique(['project_id', 'milestone_sequence'], 'unique_project_milestone');
            $table->index(['partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};


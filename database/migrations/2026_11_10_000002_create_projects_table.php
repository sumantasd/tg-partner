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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('project_code', 32)->unique();
            $table->string('client_name', 150);
            $table->string('client_company', 200)->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone', 30)->nullable();
            $table->string('project_title');
            $table->text('project_description');
            $table->decimal('estimated_budget', 14, 2)->nullable();
            $table->decimal('final_deal_value', 14, 2)->nullable(); // Set by Owner
            $table->char('currency', 3)->default('INR');
            $table->string('status', 50)->default('submitted')->index();
            $table->string('agreed_commission_type', 20)->default('percentage'); // percentage | fixed
            $table->decimal('agreed_commission_rate', 5, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->useCurrent()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};


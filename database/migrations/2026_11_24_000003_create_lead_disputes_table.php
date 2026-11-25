<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number', 32)->unique();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('dispute_category', 50); // duplicate_rejection, unauthorized_reassignment, incorrect_ownership, missing_ownership, other
            $table->string('status', 50)->default('pending')->index(); // pending, under_review, resolved, rejected
            $table->string('customer_phone_claimed', 30)->nullable();
            $table->string('customer_email_claimed', 255)->nullable();
            $table->text('explanation');
            $table->string('evidence_path')->nullable();
            $table->string('evidence_filename')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'status']);
            $table->index('dispute_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_disputes');
    }
};

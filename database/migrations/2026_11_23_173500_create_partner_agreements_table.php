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
        Schema::create('partner_agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_number', 50)->unique();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('agreement_templates')->nullOnDelete();
            $table->string('template_version', 20)->default('1.0');
            $table->foreignId('commission_policy_id')->nullable()->constrained('commission_policies')->nullOnDelete();
            $table->string('commission_policy_version', 20)->nullable();
            $table->decimal('commission_rate_snapshot', 8, 2)->default(10.00);
            $table->string('commission_type_snapshot', 50)->default('percentage');
            $table->string('pdf_path', 255);
            $table->longText('content_snapshot');
            $table->date('issue_date');
            $table->date('effective_date');
            $table->string('status', 30)->default('pending_acceptance');
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index('status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_agreements');
    }
};

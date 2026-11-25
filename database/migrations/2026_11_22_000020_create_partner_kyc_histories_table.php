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
        if (! Schema::hasTable('partner_kyc_histories')) {
            Schema::create('partner_kyc_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
                $table->foreignId('partner_kyc_id')->constrained('partner_kycs')->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('action', 50); // submitted, resubmitted, review_started, kyc_approved, kyc_rejected, bank_verified, bank_rejected, correction_requested
                $table->string('kyc_status', 32);
                $table->string('bank_verification_status', 32);
                $table->json('submission_snapshot'); // Complete freeze of all fields and document paths at this moment
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->json('correction_fields')->nullable();
                $table->text('internal_notes')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_kyc_histories');
    }
};

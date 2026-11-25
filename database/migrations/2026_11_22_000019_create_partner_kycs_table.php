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
        if (! Schema::hasTable('partner_kycs')) {
            Schema::create('partner_kycs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->unique()->constrained('partners')->cascadeOnDelete();
                
                // Statuses
                $table->string('kyc_status', 32)->default('not_submitted')->index();
                $table->string('bank_verification_status', 32)->default('not_submitted')->index();

                // Personal / Business Details
                $table->string('legal_name', 150)->nullable();
                $table->string('partner_type', 32)->default('individual'); // individual | business
                $table->date('dob_or_incorporation_date')->nullable();
                $table->text('pan_number')->nullable(); // Encrypted at rest
                $table->string('pan_number_masked', 16)->nullable(); // e.g. ABCDE****F
                $table->text('registered_address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 100)->default('India');

                // Documents (Private Storage paths)
                $table->string('pan_document_path')->nullable();
                $table->string('identity_document_type', 50)->nullable(); // aadhaar, passport, voter_id, driving_license
                $table->string('identity_document_path')->nullable();
                $table->string('address_document_path')->nullable();
                $table->string('bank_proof_document_path')->nullable();

                // Bank Details
                $table->string('bank_account_holder_name', 150)->nullable();
                $table->string('bank_name', 150)->nullable();
                $table->text('bank_account_number')->nullable(); // Encrypted at rest
                $table->string('bank_account_last4', 8)->nullable();
                $table->text('bank_ifsc_code')->nullable(); // Encrypted at rest
                $table->text('upi_id')->nullable(); // Encrypted at rest
                $table->string('preferred_payout_method', 32)->default('bank_transfer');

                // Submission & Audit Timestamps
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamp('kyc_approved_at')->nullable();
                $table->foreignId('kyc_approved_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamp('bank_verified_at')->nullable();
                $table->foreignId('bank_verified_by')->nullable()->constrained('users')->nullOnDelete();

                // Rejection & Correction Fields
                $table->text('rejection_reason')->nullable();
                $table->text('correction_reason')->nullable();
                $table->json('correction_fields')->nullable();
                $table->text('internal_admin_notes')->nullable();

                $table->unsignedInteger('current_version')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_kycs');
    }
};

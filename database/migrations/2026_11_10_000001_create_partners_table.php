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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('partner_code', 32)->unique();
            $table->string('company_name')->index();
            $table->string('phone', 30);
            $table->string('website')->nullable();
            $table->text('tax_id')->nullable(); // Encrypted at rest
            $table->string('address_line1')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->text('bank_account_number')->nullable(); // Encrypted at rest
            $table->char('bank_account_last4', 4)->nullable(); // Unencrypted for partner-facing UI
            $table->text('bank_routing_code')->nullable(); // Encrypted at rest (IFSC / SWIFT)
            $table->string('payout_method', 50)->default('bank_transfer');
            $table->text('payout_details')->nullable(); // Encrypted at rest (UPI / instructions)
            $table->decimal('default_commission_rate', 5, 2)->default(10.00);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};


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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 32)->unique();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->string('payment_type', 50)->default('advance'); // advance, milestone, final
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('payment_method', 50)->default('bank_transfer');
            $table->string('transaction_reference', 100)->nullable()->index();
            $table->date('payment_date')->index();
            $table->string('proof_attachment_path')->nullable();
            $table->string('status', 50)->default('recorded')->index(); // recorded, verified, rejected
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('commission_generated')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};

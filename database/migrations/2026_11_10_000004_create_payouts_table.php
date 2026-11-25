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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number', 32)->unique();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('payment_method', 50)->default('bank_transfer');
            $table->string('transaction_reference', 100)->nullable()->index();
            $table->string('payment_status', 50)->default('initiated')->index();
            $table->date('payment_date')->nullable()->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('recipient_snapshot'); // Encrypted JSON historical snapshot
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps(); // Immutable financial ledger - NO softDeletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};


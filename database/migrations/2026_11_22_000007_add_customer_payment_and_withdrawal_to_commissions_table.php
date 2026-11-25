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
        Schema::table('commissions', function (Blueprint $table) {
            // One authoritative unique relationship between customer_payments and commissions
            $table->foreignId('customer_payment_id')->nullable()->unique()->after('partner_id')->constrained('customer_payments')->restrictOnDelete();
            $table->foreignId('withdrawal_request_id')->nullable()->after('payout_id')->constrained('withdrawal_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropUnique(['customer_payment_id']);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_payment_id');
            $table->dropConstrainedForeignId('withdrawal_request_id');
        });
    }
};

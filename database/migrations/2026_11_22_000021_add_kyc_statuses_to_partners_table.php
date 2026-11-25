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
        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'kyc_status')) {
                $table->string('kyc_status', 32)->default('not_submitted')->after('payout_details')->index();
            }
            if (! Schema::hasColumn('partners', 'bank_verification_status')) {
                $table->string('bank_verification_status', 32)->default('not_submitted')->after('kyc_status')->index();
            }
            if (! Schema::hasColumn('partners', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('bank_verification_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'kyc_status')) {
                $table->dropIndex(['kyc_status']);
            }
            if (Schema::hasColumn('partners', 'bank_verification_status')) {
                $table->dropIndex(['bank_verification_status']);
            }
            $cols = [];
            if (Schema::hasColumn('partners', 'kyc_verified_at')) $cols[] = 'kyc_verified_at';
            if (Schema::hasColumn('partners', 'bank_verification_status')) $cols[] = 'bank_verification_status';
            if (Schema::hasColumn('partners', 'kyc_status')) $cols[] = 'kyc_status';
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

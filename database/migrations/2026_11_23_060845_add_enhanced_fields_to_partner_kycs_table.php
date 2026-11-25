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
        Schema::table('partner_kycs', function (Blueprint $table) {
            $table->string('identity_document_back_path')->nullable()->after('identity_document_path');
            $table->string('authorized_signatory_name')->nullable()->after('legal_name');
            $table->string('business_registration_number')->nullable()->after('authorized_signatory_name');
            $table->string('gstin')->nullable()->after('business_registration_number');
            $table->boolean('id_contains_current_address')->default(true)->after('identity_document_back_path');
            $table->boolean('declaration_accepted')->default(false)->after('preferred_payout_method');
            $table->timestamp('declaration_accepted_at')->nullable()->after('declaration_accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_kycs', function (Blueprint $table) {
            $table->dropColumn([
                'identity_document_back_path',
                'authorized_signatory_name',
                'business_registration_number',
                'gstin',
                'id_contains_current_address',
                'declaration_accepted',
                'declaration_accepted_at',
            ]);
        });
    }
};

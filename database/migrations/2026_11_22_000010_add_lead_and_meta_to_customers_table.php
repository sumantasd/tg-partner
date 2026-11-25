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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('first_partner_id')->constrained('leads')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('active')->after('created_by')->index();
            $table->string('whatsapp_number', 30)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('lead_id');
            $table->dropColumn(['status', 'whatsapp_number']);
        });
    }
};

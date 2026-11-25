<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_id')->nullable()->change();
            $table->string('whatsapp_number', 30)->nullable()->after('customer_phone');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('lead_source', 50)->default('Direct')->after('service_required');
            $table->string('priority', 50)->default('Medium')->after('lead_source');
            $table->foreignId('created_by')->nullable()->after('assigned_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'priority', 'lead_source', 'state', 'whatsapp_number']);
            $table->unsignedBigInteger('partner_id')->nullable(false)->change();
        });
    }
};

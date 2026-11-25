<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('original_partner_id')->nullable()->after('partner_id')->constrained('partners')->nullOnDelete();
            $table->string('ownership_status', 30)->default('protected')->after('status')->index();
            $table->timestamp('ownership_started_at')->nullable()->after('assigned_at');
            $table->timestamp('ownership_expires_at')->nullable()->after('ownership_started_at')->index();
            $table->text('ownership_change_reason')->nullable()->after('rejection_reason');
            $table->timestamp('submitted_at')->nullable()->after('converted_at');
        });

        // Safe database-agnostic backfill for existing leads
        $existingLeads = DB::table('leads')->whereNull('original_partner_id')->get();
        foreach ($existingLeads as $lead) {
            $createdAt = $lead->created_at ? Carbon::parse($lead->created_at) : now();
            DB::table('leads')->where('id', $lead->id)->update([
                'original_partner_id' => $lead->partner_id,
                'ownership_status' => 'protected',
                'ownership_started_at' => $createdAt,
                'submitted_at' => $createdAt,
                'ownership_expires_at' => $createdAt->copy()->addDays(90),
            ]);
        }

        // Ensure default setting for lead ownership validity days exists
        if (!DB::table('system_settings')->where('key', 'lead_ownership_validity_days')->exists()) {
            DB::table('system_settings')->insert([
                'key' => 'lead_ownership_validity_days',
                'value' => '90',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['original_partner_id']);
            $table->dropIndex(['ownership_status']);
            $table->dropIndex(['ownership_expires_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'original_partner_id',
                'ownership_status',
                'ownership_started_at',
                'ownership_expires_at',
                'ownership_change_reason',
                'submitted_at',
            ]);
        });
    }
};

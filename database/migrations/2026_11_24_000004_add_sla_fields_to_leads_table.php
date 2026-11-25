<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('first_contact_due_at')->nullable()->after('assigned_at')->index();
            $table->timestamp('first_contacted_at')->nullable()->after('first_contact_due_at');
            $table->string('first_contact_sla_status', 30)->default('pending')->after('first_contacted_at')->index();
            $table->timestamp('last_contacted_at')->nullable()->after('first_contact_sla_status');
            $table->timestamp('followup_due_at')->nullable()->after('next_followup_date')->index();
            $table->string('followup_sla_status', 30)->default('on_track')->after('followup_due_at')->index();
            $table->string('overall_sla_status', 30)->default('on_track')->after('followup_sla_status')->index();
            $table->unsignedInteger('missed_followups_count')->default(0)->after('overall_sla_status');
            $table->timestamp('last_escalated_at')->nullable()->after('missed_followups_count');
        });

        // Initialize default SLA configuration in system_settings if not present
        $defaults = [
            'sla_enabled'                          => 'true',
            'sla_first_contact_hours'              => '24',
            'sla_followup_hours'                   => '48',
            'sla_reassigned_first_contact_hours'   => '12',
            'sla_calculation_mode'                 => 'calendar_hours',
            'sla_business_start_time'              => '09:30',
            'sla_business_end_time'                => '18:30',
            'sla_working_days'                     => '[1,2,3,4,5,6]',
            'sla_due_soon_threshold_hours'         => '4',
        ];

        foreach ($defaults as $key => $val) {
            if (! DB::table('system_settings')->where('key', $key)->exists()) {
                DB::table('system_settings')->insert([
                    'key'        => $key,
                    'value'      => $val,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Backfill existing leads: if completed status (Won/Lost/Rejected), mark exempt. If unassigned, pending.
        $leads = DB::table('leads')->get();
        foreach ($leads as $l) {
            $isClosed = in_array($l->status, ['won', 'lost', 'rejected']);
            $isAssigned = ! empty($l->assigned_to);

            $overallStatus = $isClosed ? 'exempt' : ($isAssigned ? 'on_track' : 'exempt');
            $firstContactStatus = $isClosed ? 'exempt' : ($isAssigned ? 'on_track' : 'pending');

            $assignedAt = $l->assigned_at ? \Illuminate\Support\Carbon::parse($l->assigned_at) : null;
            $firstContactDue = $assignedAt ? $assignedAt->copy()->addHours(24) : null;

            $followupDue = null;
            if ($l->next_followup_date) {
                $followupDue = \Illuminate\Support\Carbon::parse($l->next_followup_date)->setTime(18, 30);
            }

            DB::table('leads')->where('id', $l->id)->update([
                'first_contact_due_at'     => $firstContactDue,
                'first_contact_sla_status' => $firstContactStatus,
                'followup_due_at'          => $followupDue,
                'followup_sla_status'      => $isClosed ? 'exempt' : 'on_track',
                'overall_sla_status'       => $overallStatus,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['first_contact_due_at']);
            $table->dropIndex(['first_contact_sla_status']);
            $table->dropIndex(['followup_due_at']);
            $table->dropIndex(['followup_sla_status']);
            $table->dropIndex(['overall_sla_status']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'first_contact_due_at',
                'first_contacted_at',
                'first_contact_sla_status',
                'last_contacted_at',
                'followup_due_at',
                'followup_sla_status',
                'overall_sla_status',
                'missed_followups_count',
                'last_escalated_at',
            ]);
        });
    }
};

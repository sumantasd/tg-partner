<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sla_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('escalation_type', 50)->index(); // first_contact_breach, followup_breach, repeated_missed_followup
            $table->string('severity', 20)->default('warning'); // info, warning, critical
            $table->timestamp('due_at')->nullable();
            $table->timestamp('breached_at')->nullable();
            $table->text('details')->nullable();
            $table->boolean('notification_sent')->default(true);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['assigned_to', 'escalation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sla_escalations');
    }
};

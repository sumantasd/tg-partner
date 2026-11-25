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
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity_type', 50)->index(); // call, email, whatsapp, meeting, quotation, note, status_change
            $table->string('from_status', 50)->nullable(); // canonical status history tracking
            $table->string('to_status', 50)->nullable();   // canonical status history tracking
            $table->string('customer_interest', 50)->nullable(); // hot, warm, cold, not_interested
            $table->string('summary', 255);
            $table->text('details')->nullable();
            $table->unsignedInteger('call_duration_seconds')->nullable();
            $table->date('next_followup_date')->nullable();
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};

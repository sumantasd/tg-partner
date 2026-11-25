<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_ownership_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('previous_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('new_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('action', 50); // initial_assignment, reassigned, extended, released, dispute_resolved
            $table->text('reason')->nullable();
            $table->timestamp('valid_until_before')->nullable();
            $table->timestamp('valid_until_after')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index('new_partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_ownership_histories');
    }
};

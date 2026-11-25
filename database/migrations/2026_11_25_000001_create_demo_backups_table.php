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
        Schema::create('demo_backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_code', 64)->unique();
            $table->string('name');
            $table->string('filename');
            $table->string('disk')->default('local');
            $table->string('filepath');
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->string('sha256_checksum', 64)->nullable();
            $table->unsignedInteger('database_tables_count')->default(0);
            $table->unsignedInteger('files_count')->default(0);
            $table->boolean('is_encrypted')->default(true);
            $table->string('encryption_method')->default('AES-256');
            $table->string('status', 32)->default('pending'); // pending, in_progress, completed, verified, failed
            $table->string('type', 32)->default('manual'); // manual, pre_cleanup, pre_restore
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_backups');
    }
};

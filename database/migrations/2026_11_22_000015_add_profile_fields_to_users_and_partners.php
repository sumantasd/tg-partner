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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'designation')) {
                $table->string('designation', 100)->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('designation');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (!Schema::hasColumn('partners', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'department')) $cols[] = 'department';
            if (Schema::hasColumn('users', 'designation')) $cols[] = 'designation';
            if (Schema::hasColumn('users', 'phone')) $cols[] = 'phone';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

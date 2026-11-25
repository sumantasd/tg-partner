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
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'joining_date')) {
                $table->date('joining_date')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 255)->nullable()->after('joining_date');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'last_login_at')) $cols[] = 'last_login_at';
            if (Schema::hasColumn('users', 'profile_photo_path')) $cols[] = 'profile_photo_path';
            if (Schema::hasColumn('users', 'joining_date')) $cols[] = 'joining_date';
            if (Schema::hasColumn('users', 'employee_id')) $cols[] = 'employee_id';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

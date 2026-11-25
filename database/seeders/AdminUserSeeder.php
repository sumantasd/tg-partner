<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial Owner/Admin account idempotently.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@techgoogly.com');
        $adminPassword = env('ADMIN_INITIAL_PASSWORD', 'TechGoogly@Admin2026!');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'              => 'Tech Googly Administrator',
                'password'          => Hash::make($adminPassword),
                'role'              => UserRole::Admin,
                'status'            => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );
    }
}


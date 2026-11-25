<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name= : Name of the Admin} 
                            {--email= : Email address of the Admin} 
                            {--password= : Optional password (prompted securely if omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a secure Owner/Admin account with active status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=============================================');
        $this->info(' Tech Googly Partner Portal - Admin Provisioning');
        $this->info('=============================================');

        $name = $this->option('name') ?: $this->ask('Enter Admin Name');
        $email = $this->option('email') ?: $this->ask('Enter Admin Email');

        $validator = Validator::make([
            'name'  => $name,
            'email' => $email,
        ], [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Enter Admin Password (minimum 8 characters)');
            $confirm = $this->secret('Confirm Admin Password');

            if ($password !== $confirm) {
                $this->error('Passwords do not match.');
                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return self::FAILURE;
        }

        $admin = User::create([
            'name'              => $name,
            'email'             => strtolower(trim($email)),
            'password'          => Hash::make($password),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("✓ Owner/Admin account created successfully!");
        $this->table(['ID', 'Name', 'Email', 'Role', 'Status'], [
            [$admin->id, $admin->name, $admin->email, $admin->role->label(), $admin->status->label()],
        ]);

        return self::SUCCESS;
    }
}


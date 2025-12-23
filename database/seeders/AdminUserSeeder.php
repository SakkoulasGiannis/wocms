<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Assign admin role
        $admin->assignRole('admin');

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: test@example.com');
        $this->command->info('🔑 Password: password');
    }
}

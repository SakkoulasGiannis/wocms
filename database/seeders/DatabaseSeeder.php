<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions FIRST
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // Create admin user
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'), // Change in production!
        ]);
        $adminUser->assignRole('admin');

        // Create editor user
        $editorUser = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => bcrypt('password'), // Change in production!
        ]);
        $editorUser->assignRole('editor');

        // Create regular user
        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'), // Change in production!
        ]);
        $regularUser->assignRole('user');

        // Seed templates (Home must be first)
        $this->call([
            HomeTemplateSeeder::class,
            DefaultTemplatesSeeder::class,
            BlogTemplateSeeder::class, // Create Blog template with fields
            TemplateInitSeeder::class, // Fix any templates missing model_class/table_name
            SectionTemplatesSeeder::class, // Seed system section templates
        ]);

        // Seed blog posts
        $this->call([
            BlogPostsSeeder::class, // Create sample blog posts
        ]);

        $this->command->info('');
        $this->command->info('✓ Created 3 users:');
        $this->command->info('  - admin@example.com (Admin)');
        $this->command->info('  - editor@example.com (Editor)');
        $this->command->info('  - user@example.com (User)');
        $this->command->info('  Password for all: password');
        $this->command->info('');
        $this->command->info('✓ Created templates:');
        $this->command->info('  - Home');
        $this->command->info('  - Pages');
        $this->command->info('  - Blog (with 10 sample posts)');
        $this->command->info('  - Section Templates');
    }
}

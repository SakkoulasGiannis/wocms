<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view dashboard',

            // Content Management
            'view content',
            'create content',
            'edit content',
            'delete content',

            // Templates
            'view templates',
            'create templates',
            'edit templates',
            'delete templates',

            // Forms
            'view forms',
            'create forms',
            'edit forms',
            'delete forms',
            'view form submissions',

            // Media Library
            'view media',
            'upload media',
            'delete media',

            // Page Sections
            'view page sections',
            'edit page sections',

            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Roles & Permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign permissions',

            // Settings
            'view settings',
            'edit settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin Role (full access)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Editor Role (content management access - no templates, forms, roles management)
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'view dashboard',
            'view content',
            'create content',
            'edit content',
            'delete content',
            'view media',
            'upload media',
            'delete media',
            'view page sections',
            'edit page sections',
            'view settings',
            'view form submissions',
        ]);

        // Create User Role (minimal access - read only)
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'view dashboard',
            'view content',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Created roles: Admin, Editor, User');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}

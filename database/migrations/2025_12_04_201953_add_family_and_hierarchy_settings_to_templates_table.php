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
        Schema::table('templates', function (Blueprint $table) {
            // Family Settings
            $table->boolean('allow_children')->default(true)->after('is_active');
            $table->boolean('allow_new_pages')->default(true)->after('allow_children');

            // Hierarchy Settings - JSON arrays to store multiple template IDs
            $table->json('allowed_parent_templates')->nullable()->after('allow_new_pages');
            $table->json('allowed_child_templates')->nullable()->after('allowed_parent_templates');

            // Access Control
            $table->boolean('use_custom_access')->default(false)->after('allowed_child_templates');
            $table->json('allowed_roles')->nullable()->after('use_custom_access');

            // Visual
            $table->string('icon', 100)->nullable()->after('allowed_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn([
                'allow_children',
                'allow_new_pages',
                'allowed_parent_templates',
                'allowed_child_templates',
                'use_custom_access',
                'allowed_roles',
                'icon',
            ]);
        });
    }
};

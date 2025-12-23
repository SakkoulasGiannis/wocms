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
        // Add status field to pages table (only if table exists)
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (!Schema::hasColumn('pages', 'status')) {
                    $table->string('status', 20)->default('active')->after('slug')->comment('Status: active, draft, disabled');
                }
            });
        }

        // Add status field to blogs table (only if table exists)
        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                if (!Schema::hasColumn('blogs', 'status')) {
                    $table->string('status', 20)->default('active')->after('slug')->comment('Status: active, draft, disabled');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

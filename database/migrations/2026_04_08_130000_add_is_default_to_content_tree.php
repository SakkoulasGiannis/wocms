<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_published');
        });

        // Mark the existing home node (url_path = '/') as the default
        DB::table('content_tree')
            ->where('url_path', '/')
            ->whereNull('deleted_at')
            ->limit(1)
            ->update(['is_default' => true]);

        // Allow multiple home entries
        DB::table('templates')
            ->where('slug', 'home')
            ->update(['allow_new_pages' => true]);
    }

    public function down(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });

        DB::table('templates')
            ->where('slug', 'home')
            ->update(['allow_new_pages' => false]);
    }
};

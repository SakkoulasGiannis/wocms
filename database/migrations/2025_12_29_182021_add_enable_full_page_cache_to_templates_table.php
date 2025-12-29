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
            $table->boolean('enable_full_page_cache')->default(false)->after('is_active');
            $table->integer('cache_ttl')->default(3600)->after('enable_full_page_cache')->comment('Cache TTL in seconds (default: 1 hour)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['enable_full_page_cache', 'cache_ttl']);
        });
    }
};

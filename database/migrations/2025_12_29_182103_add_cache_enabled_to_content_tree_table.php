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
        Schema::table('content_tree', function (Blueprint $table) {
            $table->boolean('cache_enabled')->nullable()->after('is_published')
                  ->comment('null = use template setting, true = force enable, false = force disable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->dropColumn('cache_enabled');
        });
    }
};

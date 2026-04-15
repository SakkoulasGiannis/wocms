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
            $table->json('page_layout')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->dropColumn('page_layout');
        });
    }
};

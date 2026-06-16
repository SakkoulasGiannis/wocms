<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-node layout (chrome) selection for the frontend.
 *
 * `layout` holds a slug from config/layouts.php (e.g. "minimal", "portal").
 * NULL means "inherit from the nearest ancestor that has one, else the
 * site default". This is the page-chrome layout (header/footer/menu),
 * NOT to be confused with `page_layout` (a JSON snapshot of the section
 * tree, used by PageImporter).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->string('layout')->nullable()->after('page_layout');
        });
    }

    public function down(): void
    {
        Schema::table('content_tree', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};

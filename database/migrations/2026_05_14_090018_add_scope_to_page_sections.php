<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `scope` column to page_sections so a single Template can carry TWO
 * sets of design sections:
 *   - scope = 'listing' → applied to the index page (/completed-villas, /blog)
 *   - scope = 'entry'   → applied to single-entry pages (/completed-villas/{slug})
 *   - scope = null      → existing per-entity sections (Home, Page, Property…)
 *                          — backward compatible, never filtered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('page_sections', 'scope')) {
                $table->string('scope', 32)->nullable()->after('sectionable_id')
                    ->comment('listing | entry | null (default per-entity)');
                $table->index(['sectionable_type', 'sectionable_id', 'scope'], 'page_sections_sectionable_scope_idx');
            }
        });

        // One-off migration: any existing template-scoped sections (sectionable_type =
        // App\Models\Template) that already exist from earlier Phase A testing become
        // 'entry' scope by default — matches the prior behaviour where there was no scope.
        \DB::table('page_sections')
            ->where('sectionable_type', 'App\\Models\\Template')
            ->whereNull('scope')
            ->update(['scope' => 'entry']);
    }

    public function down(): void
    {
        Schema::table('page_sections', function (Blueprint $table) {
            if (Schema::hasColumn('page_sections', 'scope')) {
                $table->dropIndex('page_sections_sectionable_scope_idx');
                $table->dropColumn('scope');
            }
        });
    }
};

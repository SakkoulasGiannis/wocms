<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two independent flags that control the visibility of the
 * "Design listing" and "Design entry" buttons in the admin entry list,
 * decoupled from `use_slug_prefix` (which used to imply both).
 *
 *   - `design_listing_enabled` → toggles the listing-page visual editor link
 *   - `design_entry_enabled`   → toggles the entry-page visual editor link
 *
 * Backfill: any template that previously qualified for both buttons
 * (requires_database && is_active && use_slug_prefix) gets both flags = true,
 * preserving the current behaviour for the live data. Anything else gets false.
 *
 * Idempotent: column existence checked before add.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $t): void {
            if (! Schema::hasColumn('templates', 'design_listing_enabled')) {
                $t->boolean('design_listing_enabled')->default(false)->after('use_slug_prefix');
            }
            if (! Schema::hasColumn('templates', 'design_entry_enabled')) {
                $t->boolean('design_entry_enabled')->default(false)->after('design_listing_enabled');
            }
        });

        // Backfill to preserve current behaviour for already-configured templates.
        DB::table('templates')
            ->where('requires_database', true)
            ->where('is_active', true)
            ->where('use_slug_prefix', true)
            ->update([
                'design_listing_enabled' => true,
                'design_entry_enabled' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $t): void {
            foreach (['design_listing_enabled', 'design_entry_enabled'] as $col) {
                if (Schema::hasColumn('templates', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};

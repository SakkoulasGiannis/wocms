<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `sort_order` column to the 3 project entry tables (Completed Villas,
 * Under Construction, Renovations) so the admin entry list can persist the
 * drag-to-reorder order. The EntryList Livewire component already calls
 * reorder() → updates sort_order on the model, BUT also re-reads via
 * `orderBy('sort_order')` only when the column exists. Without this column,
 * reorders silently fall through to `->latest()` and items snap back.
 *
 * Backfills sequential values matching the current `latest()` (id desc) order,
 * so existing displayed order is preserved.
 *
 * Idempotent — checks for column existence before adding/backfilling.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['completed_villas', 'under_constructions', 'renovations'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'sort_order')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t): void {
                // After `id` is convenient but not portable — append at the end.
                $t->unsignedInteger('sort_order')->default(0)->index();
            });

            // Backfill: existing rows get sequential sort_order matching their
            // CURRENT visible order (latest first). So newest entry → 0, next → 1, etc.
            // This preserves the user's current view after enabling sort_order ordering.
            $rows = DB::table($table)->orderByDesc('id')->pluck('id');
            $position = 0;
            foreach ($rows as $id) {
                DB::table($table)->where('id', $id)->update(['sort_order' => $position++]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sort_order')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->dropIndex($table.'_sort_order_index');
                $t->dropColumn('sort_order');
            });
        }
    }
};

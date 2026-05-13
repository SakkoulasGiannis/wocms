<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 3 project templates (Completed Villas, Under Construction, Renovations) were
 * generated with INT columns for size fields because TemplateTableGenerator mapped
 * `number` field type to ->integer(). That rejected fractional values like 2.2
 * (Laravel validator says "must be an integer"; DB would truncate). Switch the
 * three size columns to DECIMAL(12,2) so plot/building/pool sizes accept decimals.
 *
 * year_built stays INT — a year is integer.
 */
return new class extends Migration
{
    private array $tables = ['completed_villas', 'under_constructions', 'renovations'];

    private array $columns = ['building_size', 'plot_size', 'pool_size'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach ($this->columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->decimal($column, 12, 2)->nullable()->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach ($this->columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->integer($column)->nullable()->change();
                    }
                }
            });
        }
    }
};

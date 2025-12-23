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
        Schema::table('template_fields', function (Blueprint $table) {
            $table->boolean('adapts_to_render_mode')->default(false)->after('default_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn('adapts_to_render_mode');
        });
    }
};

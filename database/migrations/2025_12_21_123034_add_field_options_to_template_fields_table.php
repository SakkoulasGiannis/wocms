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
            $table->boolean('is_searchable')->default(false)->after('is_required');
            $table->boolean('is_filterable')->default(false)->after('is_searchable');
            $table->boolean('is_url_identifier')->default(false)->after('is_filterable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn(['is_searchable', 'is_filterable', 'is_url_identifier']);
        });
    }
};

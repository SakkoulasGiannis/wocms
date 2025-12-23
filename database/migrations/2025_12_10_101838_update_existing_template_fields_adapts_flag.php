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
        // Update existing template fields to have correct adapts_to_render_mode flag
        // This is for existing installations that already have template fields

        // Fields that should adapt to render mode (main content fields)
        $adaptiveFieldNames = ['body', 'html', 'content', 'contents'];

        // Update fields with these names to adapts_to_render_mode = true
        \DB::table('template_fields')
            ->whereIn('name', $adaptiveFieldNames)
            ->update(['adapts_to_render_mode' => true]);

        // All other fields should NOT adapt (title, slug, etc.)
        \DB::table('template_fields')
            ->whereNotIn('name', $adaptiveFieldNames)
            ->update(['adapts_to_render_mode' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all to false (default state)
        \DB::table('template_fields')
            ->update(['adapts_to_render_mode' => false]);
    }
};

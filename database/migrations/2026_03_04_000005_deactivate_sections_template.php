<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Section model was removed and the sections table dropped.
     * Deactivate the template that referenced it and unpublish orphaned content nodes.
     */
    public function up(): void
    {
        // Deactivate the 'sections' template (model_class = Section)
        DB::table('templates')
            ->where('model_class', 'Section')
            ->update(['is_active' => false]);

        // Unpublish any content tree entries that reference App\Models\Section
        DB::table('content_tree')
            ->where('content_type', 'App\\Models\\Section')
            ->update(['is_published' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('templates')
            ->where('model_class', 'Section')
            ->update(['is_active' => true]);

        DB::table('content_tree')
            ->where('content_type', 'App\\Models\\Section')
            ->update(['is_published' => true]);
    }
};

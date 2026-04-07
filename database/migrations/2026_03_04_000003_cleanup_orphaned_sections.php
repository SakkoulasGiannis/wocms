<?php

use App\Models\PageSection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Soft-delete page_sections that reference the legacy Section model
     * (sectionable_type = App\Models\Section), since the sections table
     * will be dropped and those records are orphaned test data.
     */
    public function up(): void
    {
        PageSection::where('sectionable_type', 'App\\Models\\Section')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        PageSection::withTrashed()
            ->where('sectionable_type', 'App\\Models\\Section')
            ->update(['deleted_at' => null]);
    }
};

<?php

use App\Models\PageSection;
use App\Models\SectionTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Link existing page_sections that have section_template_id = NULL
     * to the matching system SectionTemplate by converting section_type to slug.
     */
    public function up(): void
    {
        // Build a lookup map: slug → template_id
        $templateMap = SectionTemplate::where('is_system', true)
            ->pluck('id', 'slug')
            ->toArray();

        // Find all page_sections without a template link
        PageSection::whereNull('section_template_id')
            ->whereNotNull('section_type')
            ->chunkById(100, function ($sections) use ($templateMap) {
                foreach ($sections as $section) {
                    // Convert section_type (hero_simple) to slug (hero-simple)
                    $slug = Str::slug(str_replace('_', '-', $section->section_type));

                    if (isset($templateMap[$slug])) {
                        $section->update(['section_template_id' => $templateMap[$slug]]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Only unlink system templates that were auto-linked
        $systemTemplateIds = SectionTemplate::where('is_system', true)->pluck('id');

        PageSection::whereIn('section_template_id', $systemTemplateIds)
            ->update(['section_template_id' => null]);
    }
};

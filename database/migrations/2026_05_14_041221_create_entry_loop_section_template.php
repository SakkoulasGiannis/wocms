<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers the "Entry Loop" section template — a generic collection display
 * that pulls entries from any template (Completed Villas, Properties, etc.)
 * and renders them as a card grid. Picks the source template via dropdown and
 * exposes limit / order / columns / pagination + token-driven card fields so
 * the user can choose which entry fields fill the title / image / link.
 *
 * Idempotent: firstOrCreate by slug, so re-running is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        $template = SectionTemplate::firstOrCreate(
            ['slug' => 'entry-loop'],
            [
                'name' => 'Entry Loop',
                'category' => 'Dynamic',
                'description' => 'Display a grid of entries from any template with field-token bindings.',
                'blade_file' => 'components.sections.entry-loop',
                'html_template' => '', // required by NOT NULL column; renderer uses blade_file
                'icon' => '🔁',
                'is_system' => true,
                'is_active' => true,
                'order' => 95,
                'default_settings' => [],
            ]
        );

        if (! $template->wasRecentlyCreated) {
            return; // already seeded
        }

        $fields = [
            // Source
            ['name' => 'source_template', 'label' => 'Source template', 'type' => 'template_picker', 'description' => 'Which template to pull entries from', 'is_required' => true, 'order' => 0],

            // Heading
            ['name' => 'heading', 'label' => 'Section heading', 'type' => 'text', 'description' => 'Optional title above the grid', 'placeholder' => 'Latest Completed Villas', 'order' => 1],
            ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'description' => 'Optional small label above the heading', 'placeholder' => 'OUR PROJECTS', 'order' => 2],

            // Query
            ['name' => 'limit', 'label' => 'Limit (0 = all)', 'type' => 'number', 'default_value' => '12', 'order' => 10],
            ['name' => 'order_by', 'label' => 'Order by', 'type' => 'select', 'default_value' => 'created_at', 'settings' => ['options' => [
                'created_at' => 'Date created',
                'updated_at' => 'Date updated',
                'name' => 'Name',
                'year_built' => 'Year (if available)',
                'building_size' => 'Building size (if available)',
            ]], 'order' => 11],
            ['name' => 'order_dir', 'label' => 'Order direction', 'type' => 'select', 'default_value' => 'desc', 'settings' => ['options' => [
                'desc' => 'Descending (newest / largest first)',
                'asc'  => 'Ascending (oldest / smallest first)',
            ]], 'order' => 12],
            ['name' => 'show_pagination', 'label' => 'Show pagination', 'type' => 'checkbox', 'default_value' => '0', 'order' => 13],
            ['name' => 'per_page', 'label' => 'Per page (when paginating)', 'type' => 'number', 'default_value' => '12', 'description' => 'Only used when pagination is on', 'order' => 14],

            // Layout
            ['name' => 'columns', 'label' => 'Columns (desktop)', 'type' => 'select', 'default_value' => '3', 'settings' => ['options' => [
                '1' => '1 column', '2' => '2 columns', '3' => '3 columns', '4' => '4 columns',
            ]], 'order' => 20],
            ['name' => 'gap', 'label' => 'Gap', 'type' => 'select', 'default_value' => 'normal', 'settings' => ['options' => [
                'tight'  => 'Tight',
                'normal' => 'Normal',
                'loose'  => 'Loose',
            ]], 'order' => 21],

            // Card bindings (tokens that resolve against each entry during the loop)
            ['name' => 'card_image_token', 'label' => 'Card image', 'type' => 'text', 'default_value' => '{main_image:preview}', 'description' => 'Token from the source template (e.g. {main_image:preview}, {featured_image})', 'order' => 30],
            ['name' => 'card_title_token', 'label' => 'Card title', 'type' => 'text', 'default_value' => '{name}', 'description' => 'Token from the source template (e.g. {name}, {title})', 'order' => 31],
            ['name' => 'card_subtitle_token', 'label' => 'Card subtitle', 'type' => 'text', 'default_value' => '{location}', 'description' => 'Token or static text (e.g. {location} · {year_built})', 'order' => 32],
            ['name' => 'card_link_pattern', 'label' => 'Card link', 'type' => 'text', 'default_value' => '/{template_slug}/{slug}', 'description' => 'URL pattern. {template_slug} = source template slug, plus any entry token', 'order' => 33],
            ['name' => 'card_image_fallback', 'label' => 'Card image fallback', 'type' => 'text', 'placeholder' => '/themes/kretaeiendom/images/home/house-7.jpg', 'description' => 'Used when entry has no image', 'order' => 34],

            // Section spacing
            ['name' => 'section_class', 'label' => 'Section padding/bg classes', 'type' => 'text', 'default_value' => 'py-20 lg:py-24 bg-white', 'order' => 40],
        ];

        foreach ($fields as $field) {
            SectionTemplateField::create(array_merge(
                ['section_template_id' => $template->id],
                $field
            ));
        }
    }

    public function down(): void
    {
        $template = SectionTemplate::where('slug', 'entry-loop')->first();
        if ($template) {
            $template->fields()->delete();
            $template->delete();
        }
    }
};

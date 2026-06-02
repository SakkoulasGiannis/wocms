<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers a "Blog Loop" section template — a configurable list of blog
 * posts pickable from the visual editor with:
 *  - limit
 *  - category filter (dynamic dropdown of BlogCategory)
 *  - tags filter (comma-separated slugs/names, any-match)
 *  - order / direction
 *  - grid layout, optional excerpt + category/tag chips
 *
 * Idempotent (firstOrCreate by slug). Re-runnable safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        $template = SectionTemplate::firstOrCreate(
            ['slug' => 'blog-loop'],
            [
                'name' => 'Blog Loop',
                'category' => 'Dynamic',
                'description' => 'Display a configurable grid of blog posts with category and tag filters.',
                'blade_file' => 'components.sections.blog-loop',
                'html_template' => '',
                'icon' => '📰',
                'is_system' => true,
                'is_active' => true,
                'order' => 94,
                'default_settings' => [],
            ]
        );

        if (! $template->wasRecentlyCreated) {
            return;
        }

        $fields = [
            ['name' => 'heading',    'label' => 'Section heading',    'type' => 'text', 'placeholder' => 'Latest from the Blog', 'order' => 1],
            ['name' => 'subheading', 'label' => 'Subheading',         'type' => 'text', 'placeholder' => 'NEWS & INSIGHTS',     'order' => 2],

            ['name' => 'limit',      'label' => 'Number of posts',    'type' => 'number', 'default_value' => '6', 'description' => 'How many posts to show (use 0 for unlimited)', 'order' => 10],

            // Dynamic options resolved at render time by the visual editor
            // (special-cased on $field->name === 'category_slug').
            ['name' => 'category_slug', 'label' => 'Filter by category', 'type' => 'select', 'description' => 'Show posts from a specific category — leave empty for all.', 'order' => 20],

            ['name' => 'tags_csv',   'label' => 'Filter by tags',     'type' => 'text', 'placeholder' => 'tag-slug-1, tag-slug-2', 'description' => 'Comma-separated tag slugs or names. Empty = no tag filter.', 'order' => 21],

            ['name' => 'order_by',   'label' => 'Order by',           'type' => 'select', 'default_value' => 'published_at', 'settings' => ['options' => [
                'published_at' => 'Publish date',
                'created_at'   => 'Date created',
                'title'        => 'Title',
            ]], 'order' => 30],
            ['name' => 'order_dir',  'label' => 'Order direction',    'type' => 'select', 'default_value' => 'desc', 'settings' => ['options' => [
                'desc' => 'Descending (newest / Z–A)',
                'asc'  => 'Ascending (oldest / A–Z)',
            ]], 'order' => 31],

            ['name' => 'columns',    'label' => 'Columns (desktop)',  'type' => 'select', 'default_value' => '3', 'settings' => ['options' => [
                '1' => '1 column',
                '2' => '2 columns',
                '3' => '3 columns',
                '4' => '4 columns',
            ]], 'order' => 40],
            ['name' => 'gap',        'label' => 'Gap',                'type' => 'select', 'default_value' => 'normal', 'settings' => ['options' => [
                'tight'  => 'Tight',
                'normal' => 'Normal',
                'loose'  => 'Loose',
            ]], 'order' => 41],

            ['name' => 'show_excerpt', 'label' => 'Show excerpt',     'type' => 'boolean', 'default_value' => '1', 'order' => 50],
            ['name' => 'show_chips',   'label' => 'Show category/tag chips on cards', 'type' => 'boolean', 'default_value' => '1', 'order' => 51],

            ['name' => 'section_class', 'label' => 'Section CSS class', 'type' => 'text', 'placeholder' => 'py-20 lg:py-24 bg-white', 'description' => 'Tailwind classes applied to the outer <section>.', 'order' => 60],
        ];

        foreach ($fields as $f) {
            SectionTemplateField::create(array_merge(
                ['section_template_id' => $template->id],
                $f
            ));
        }
    }

    public function down(): void
    {
        $tpl = SectionTemplate::where('slug', 'blog-loop')->where('is_system', true)->first();
        if ($tpl) {
            $tpl->fields()->delete();
            $tpl->forceDelete();
        }
    }
};

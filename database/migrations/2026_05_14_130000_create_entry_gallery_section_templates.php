<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers three "Entry Gallery" section templates (grid / masonry / slider)
 * that auto-pull images from the current entry's Spatie media collection.
 *
 * Designed for template-design mode (entry scope) — when placed inside an
 * entry-scoped page section list, each variant renders that entry's gallery
 * images. The media_collection field defaults to 'gallery' but can target any
 * collection on the entry (e.g. 'photos', 'screenshots').
 *
 * Idempotent: firstOrCreate by slug. Re-running is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->definitions() as $def) {
            $template = SectionTemplate::firstOrCreate(
                ['slug' => $def['slug']],
                [
                    'name' => $def['name'],
                    'category' => 'Gallery',
                    'description' => $def['description'],
                    'blade_file' => $def['blade_file'],
                    'html_template' => '',
                    'icon' => $def['icon'],
                    'is_system' => true,
                    'is_active' => true,
                    'order' => $def['order'],
                    'default_settings' => $def['default_settings'] ?? [],
                ]
            );

            if (! $template->wasRecentlyCreated) {
                continue;
            }

            foreach ($def['fields'] as $field) {
                SectionTemplateField::create(array_merge(
                    ['section_template_id' => $template->id],
                    $field
                ));
            }
        }
    }

    public function down(): void
    {
        $slugs = collect($this->definitions())->pluck('slug')->all();

        SectionTemplate::whereIn('slug', $slugs)
            ->where('is_system', true)
            ->each(function ($t) {
                $t->fields()->delete();
                $t->forceDelete();
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        // Shared core fields all three variants accept.
        $sharedHead = [
            ['name' => 'heading',    'label' => 'Heading',    'type' => 'text', 'placeholder' => 'Photo Gallery', 'order' => 0],
            ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'placeholder' => 'EXPLORE',       'order' => 1],
            ['name' => 'media_collection', 'label' => 'Media collection', 'type' => 'text', 'default_value' => 'gallery', 'description' => 'Spatie media collection name on the entry (default: gallery)', 'order' => 2],
            ['name' => 'lightbox',   'label' => 'Enable lightbox', 'type' => 'boolean', 'default_value' => '1', 'order' => 3],
            ['name' => 'section_class', 'label' => 'Section CSS class', 'type' => 'text', 'description' => 'Tailwind classes applied to the <section>. Leave default for sensible padding.', 'order' => 9],
        ];

        return [
            // ─── GRID ───────────────────────────────────────────────────────
            [
                'slug' => 'entry_gallery_grid',
                'name' => 'Entry Gallery — Grid',
                'description' => 'Auto-loads the current entry\'s photos into a uniform square grid with a click-to-zoom lightbox.',
                'blade_file' => 'frontend.sections.entry_gallery_grid',
                'icon' => '🖼️',
                'order' => 96,
                'default_settings' => ['columns' => 3, 'gap' => 'normal', 'lightbox' => true],
                'fields' => array_merge($sharedHead, [
                    ['name' => 'columns', 'label' => 'Columns (desktop)', 'type' => 'select', 'default_value' => '3', 'settings' => ['options' => [
                        '2' => '2 columns', '3' => '3 columns', '4' => '4 columns', '5' => '5 columns', '6' => '6 columns',
                    ]], 'order' => 5],
                    ['name' => 'gap', 'label' => 'Gap', 'type' => 'select', 'default_value' => 'normal', 'settings' => ['options' => [
                        'tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose',
                    ]], 'order' => 6],
                ]),
            ],

            // ─── MASONRY ────────────────────────────────────────────────────
            [
                'slug' => 'entry_gallery_masonry',
                'name' => 'Entry Gallery — Masonry',
                'description' => 'Pinterest-style flowing layout — preserves each photo\'s natural aspect ratio.',
                'blade_file' => 'frontend.sections.entry_gallery_masonry',
                'icon' => '🧱',
                'order' => 97,
                'default_settings' => ['columns' => 3, 'gap' => 'normal', 'lightbox' => true],
                'fields' => array_merge($sharedHead, [
                    ['name' => 'columns', 'label' => 'Columns (desktop)', 'type' => 'select', 'default_value' => '3', 'settings' => ['options' => [
                        '2' => '2 columns', '3' => '3 columns', '4' => '4 columns', '5' => '5 columns',
                    ]], 'order' => 5],
                    ['name' => 'gap', 'label' => 'Gap', 'type' => 'select', 'default_value' => 'normal', 'settings' => ['options' => [
                        'tight' => 'Tight', 'normal' => 'Normal', 'loose' => 'Loose',
                    ]], 'order' => 6],
                ]),
            ],

            // ─── SLIDER ─────────────────────────────────────────────────────
            [
                'slug' => 'entry_gallery_slider',
                'name' => 'Entry Gallery — Slider',
                'description' => 'Horizontal swipeable slider with snap + arrows. Tap a slide to open the lightbox.',
                'blade_file' => 'frontend.sections.entry_gallery_slider',
                'icon' => '🎞️',
                'order' => 98,
                'default_settings' => ['aspect' => 'landscape', 'per_view' => 'multi', 'lightbox' => true],
                'fields' => array_merge($sharedHead, [
                    ['name' => 'aspect', 'label' => 'Image shape', 'type' => 'select', 'default_value' => 'landscape', 'settings' => ['options' => [
                        'landscape' => 'Landscape (4:3)',
                        'wide' => 'Wide (16:9)',
                        'square' => 'Square (1:1)',
                        'portrait' => 'Portrait (3:4)',
                    ]], 'order' => 5],
                    ['name' => 'per_view', 'label' => 'Slides per view', 'type' => 'select', 'default_value' => 'multi', 'settings' => ['options' => [
                        'one' => '1 at a time',
                        'two' => '2 (sm: 1, md: 2)',
                        'multi' => '3 (sm: 1, md: 2, lg: 3)',
                        'four' => '4 (sm: 1.5, md: 3, lg: 4)',
                    ]], 'order' => 6],
                ]),
            ],
        ];
    }
};

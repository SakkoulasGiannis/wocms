<?php

namespace App\Services;

use App\Models\SectionTemplate;
use App\Models\Template;

/**
 * Knows the "starter section" layout to use when a user first opens
 * "Design listing" or "Design entry" for a template that has no sections yet.
 *
 * Each preset is a list of section configs in the same shape PageSection::create
 * expects (section_template_id, section_type, content, order, settings).
 *
 * Templates are matched first by exact slug; falls through to a generic
 * preset that introspects the template's fields and picks reasonable
 * candidates (first text field → title, first image field → hero, etc.).
 */
class StarterSectionPresets
{
    /**
     * Build the starter sections for (template, scope). Returns an array of
     * section attribute arrays ready to be inserted, OR an empty array if no
     * sensible preset is available.
     */
    public function presetsFor(Template $template, string $scope): array
    {
        $slug = $template->slug;

        if ($scope === 'listing') {
            return $this->listingPreset($template);
        }

        if ($scope === 'entry') {
            return match (true) {
                in_array($slug, ['completed-villas', 'under-construction', 'renovations'], true) => $this->villaEntryPreset($template),
                $slug === 'blog' => $this->blogEntryPreset($template),
                in_array($slug, ['properties', 'rental-properties'], true) => $this->propertyEntryPreset($template),
                default => $this->genericEntryPreset($template),
            };
        }

        return [];
    }

    /* ── Listing presets ─────────────────────────────────────────────────── */

    protected function listingPreset(Template $template): array
    {
        $loop = $this->sectionTemplateId('entry-loop');
        if (! $loop) return [];

        // Pick best token defaults based on the template's actual field names
        $fieldNames = $template->fields()->pluck('name')->toArray();
        $titleField   = $this->firstMatching($fieldNames, ['name', 'title', 'heading']) ?? 'name';
        $imageField   = $this->firstMatching($fieldNames, ['main_image', 'featured_image', 'image']) ?? 'main_image';
        $subFieldGuesses = [
            in_array('location', $fieldNames, true)  ? '{location}'  : null,
            in_array('city',     $fieldNames, true)  ? '{city}'      : null,
            in_array('year_built', $fieldNames, true) ? '{year_built}' : null,
        ];
        $subtitle = collect(array_filter($subFieldGuesses))->take(2)->implode(' · ');
        if ($subtitle === '') $subtitle = '{slug}';

        return [
            [
                'section_template_id' => $loop,
                'section_type' => 'entry_loop',
                'name' => 'All ' . $template->name,
                'content' => [
                    'source_template'     => $template->slug,
                    'heading'             => 'All ' . $template->name,
                    'subheading'          => '',
                    'limit'               => 0,
                    'order_by'            => 'created_at',
                    'order_dir'           => 'desc',
                    'show_pagination'     => true,
                    'per_page'            => 12,
                    'columns'             => 3,
                    'gap'                 => 'normal',
                    'card_image_token'    => '{' . $imageField . ':preview}',
                    'card_title_token'    => '{' . $titleField . '}',
                    'card_subtitle_token' => $subtitle,
                    'card_link_pattern'   => '/{template_slug}/{slug}',
                    'card_image_fallback' => '/themes/kretaeiendom/images/home/house-7.jpg',
                    'section_class'       => 'py-20 lg:py-24 bg-white',
                ],
                'settings' => [],
                'order' => 1,
            ],
        ];
    }

    /* ── Entry presets ──────────────────────────────────────────────────── */

    protected function villaEntryPreset(Template $template): array
    {
        $hero = $this->sectionTemplateId('hero-simple');
        $out = [];
        $order = 1;

        if ($hero) {
            $out[] = [
                'section_template_id' => $hero,
                'section_type' => 'hero_simple',
                'name' => 'Project hero',
                'content' => [
                    'background_image' => '{main_image:hero}',
                    'heading'          => '{name}',
                    'subheading'       => '{location}',
                    'text'             => '',
                    'button_text'      => '',
                    'button_url'       => '',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        // Specs block — uses nested primitives so EVERY label and value is
        // individually editable in the visual editor sidebar (instead of a
        // monolithic custom-html blob the user can't visually manipulate).
        $specs = [
            ['label' => 'Location',      'token' => '{location|—}'],
            ['label' => 'Year built',    'token' => '{year_built|—}'],
            ['label' => 'Building size', 'token' => '{building_size|—} m²'],
            ['label' => 'Plot size',     'token' => '{plot_size|—} m²'],
            ['label' => 'Drawn by',      'token' => '{drawn_by|—}'],
        ];
        $out[] = $this->specsSection('Project details', $specs, $order++);

        return $out;
    }

    protected function blogEntryPreset(Template $template): array
    {
        $hero = $this->sectionTemplateId('hero-simple');
        $wysiwyg = $this->sectionTemplateId('wysiwyg');
        $out = [];
        $order = 1;

        if ($hero) {
            $out[] = [
                'section_template_id' => $hero,
                'section_type' => 'hero_simple',
                'name' => 'Post hero',
                'content' => [
                    'background_image' => '{featured_image}',
                    'heading'          => '{title}',
                    'subheading'       => '{author|}',
                    'text'             => '',
                    'button_text'      => '',
                    'button_url'       => '',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        if ($wysiwyg) {
            // Use wysiwyg (rich-text editor) instead of custom-html so the user
            // can edit the body content visually, not as raw HTML in a textarea.
            $out[] = [
                'section_template_id' => $wysiwyg,
                'section_type' => 'wysiwyg',
                'name' => 'Post body',
                'content' => ['content' => '{body}'],
                'settings' => [],
                'order' => $order++,
            ];
        }

        return $out;
    }

    protected function propertyEntryPreset(Template $template): array
    {
        $hero = $this->sectionTemplateId('hero-simple');
        $out = [];
        $order = 1;

        if ($hero) {
            $out[] = [
                'section_template_id' => $hero,
                'section_type' => 'hero_simple',
                'name' => 'Property hero',
                'content' => [
                    'background_image' => '{featured_image:hero}',
                    'heading'          => '{title}',
                    'subheading'       => '{city|}',
                    'text'             => '',
                    'button_text'      => '',
                    'button_url'       => '',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        // Property meta block as nested primitives
        $specs = [
            ['label' => 'Bedrooms',  'token' => '{bedrooms|—}'],
            ['label' => 'Bathrooms', 'token' => '{bathrooms|—}'],
            ['label' => 'Area',      'token' => '{area|—} m²'],
            ['label' => 'Price',     'token' => '{price|—}'],
        ];
        $out[] = $this->specsSection('Property details', $specs, $order++);

        return $out;
    }

    /**
     * Build a nested "specs" section tree (section > heading + grid > paragraphs)
     * that the visual editor can drill into and edit each piece individually.
     */
    protected function specsSection(string $heading, array $specs, int $order): array
    {
        $primSection   = $this->sectionTemplateId('primitive-section');
        $primDiv       = $this->sectionTemplateId('primitive-div');
        $primHeading   = $this->sectionTemplateId('primitive-heading');
        $primGrid      = $this->sectionTemplateId('primitive-grid');
        $primParagraph = $this->sectionTemplateId('primitive-paragraph');

        // If primitives aren't installed yet, fall back to a flat list of
        // primitive-paragraphs so the user can still edit something.
        if (! $primSection || ! $primGrid || ! $primParagraph) {
            return [
                'section_template_id' => $this->sectionTemplateId('custom-html'),
                'section_type' => 'custom_html',
                'name' => $heading,
                'content' => ['html' => '<div class="py-16"><h2>' . $heading . '</h2><ul>' .
                    implode('', array_map(fn ($s) => '<li><strong>' . $s['label'] . ':</strong> ' . $s['token'] . '</li>', $specs)) .
                    '</ul></div>'],
                'settings' => [],
                'order' => $order,
            ];
        }

        // Each spec becomes a primitive-div containing the label + value as
        // two paragraphs — every part individually clickable in the editor.
        $gridChildren = [];
        $i = 0;
        foreach ($specs as $spec) {
            $gridChildren[] = [
                'section_template_id' => $primDiv,
                'section_type' => 'primitive_div',
                'name' => $spec['label'],
                'content' => ['class' => 'flex flex-col gap-1', 'id' => ''],
                'settings' => [],
                'order' => $i + 1,
                'children' => [
                    [
                        'section_template_id' => $primParagraph,
                        'section_type' => 'primitive_paragraph',
                        'name' => $spec['label'] . ' (label)',
                        'content' => ['content' => $spec['label'], 'class' => 'text-sm text-variant-1', 'id' => ''],
                        'settings' => [],
                        'order' => 1,
                    ],
                    [
                        'section_template_id' => $primParagraph,
                        'section_type' => 'primitive_paragraph',
                        'name' => $spec['label'] . ' (value)',
                        'content' => ['content' => $spec['token'], 'class' => 'font-semibold text-on-surface', 'id' => ''],
                        'settings' => [],
                        'order' => 2,
                    ],
                ],
            ];
            $i++;
        }

        return [
            'section_template_id' => $primSection,
            'section_type' => 'primitive_section',
            'name' => $heading,
            'content' => ['class' => 'bg-white py-16 lg:py-20', 'id' => ''],
            'settings' => [],
            'order' => $order,
            'children' => [
                [
                    'section_template_id' => $primDiv,
                    'section_type' => 'primitive_div',
                    'name' => 'Container',
                    'content' => ['class' => 'mx-auto max-w-5xl px-4 sm:px-6 lg:px-8', 'id' => ''],
                    'settings' => [],
                    'order' => 1,
                    'children' => [
                        [
                            'section_template_id' => $primHeading,
                            'section_type' => 'primitive_heading',
                            'name' => 'Section title',
                            'content' => ['text' => $heading, 'tag' => 'h2', 'class' => 'mb-8 text-3xl font-bold text-on-surface', 'id' => ''],
                            'settings' => [],
                            'order' => 1,
                        ],
                        [
                            'section_template_id' => $primGrid,
                            'section_type' => 'primitive_grid',
                            'name' => 'Specs grid',
                            'content' => ['columns' => '2', 'gap' => '6', 'class' => 'gap-x-8 gap-y-4', 'id' => ''],
                            'settings' => [],
                            'order' => 2,
                            'children' => $gridChildren,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generic entry preset for unknown templates. Picks the first text field as
     * the heading source and (if any) the first image field as the hero image.
     */
    protected function genericEntryPreset(Template $template): array
    {
        $hero = $this->sectionTemplateId('hero-simple');
        if (! $hero) return [];

        $fields = $template->fields()->orderBy('order')->get();
        $titleField = $fields->where('type', 'text')->first()?->name ?? 'title';
        $imageField = $fields->where('type', 'image')->first()?->name;

        return [
            [
                'section_template_id' => $hero,
                'section_type' => 'hero_simple',
                'name' => 'Hero',
                'content' => [
                    'background_image' => $imageField ? '{'.$imageField.'}' : '',
                    'heading'          => '{'.$titleField.'}',
                    'subheading'       => '',
                    'text'             => '',
                    'button_text'      => '',
                    'button_url'       => '',
                ],
                'settings' => [],
                'order' => 1,
            ],
        ];
    }

    /* ── Helpers ────────────────────────────────────────────────────────── */

    protected function sectionTemplateId(string $slug): ?int
    {
        return SectionTemplate::where('slug', $slug)->where('is_active', true)->value('id');
    }

    protected function firstMatching(array $haystack, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (in_array($c, $haystack, true)) return $c;
        }
        return null;
    }
}

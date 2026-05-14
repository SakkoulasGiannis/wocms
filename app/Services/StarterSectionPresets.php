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
        $hero  = $this->sectionTemplateId('hero-simple');
        $html  = $this->sectionTemplateId('custom-html');
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

        if ($html) {
            $out[] = [
                'section_template_id' => $html,
                'section_type' => 'custom_html',
                'name' => 'Specs',
                'content' => [
                    'html' => '<section class="bg-white py-16 lg:py-20">' .
                              '<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">' .
                              '<h2 class="mb-8 text-3xl font-bold text-on-surface">Project details</h2>' .
                              '<dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">' .
                              '<div><dt class="text-sm text-variant-1">Location</dt><dd class="font-semibold text-on-surface">{location|—}</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Year built</dt><dd class="font-semibold text-on-surface">{year_built|—}</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Building size</dt><dd class="font-semibold text-on-surface">{building_size|—} m²</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Plot size</dt><dd class="font-semibold text-on-surface">{plot_size|—} m²</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Drawn by</dt><dd class="font-semibold text-on-surface">{drawn_by|—}</dd></div>' .
                              '</dl></div></section>',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        return $out;
    }

    protected function blogEntryPreset(Template $template): array
    {
        $hero  = $this->sectionTemplateId('hero-simple');
        $html  = $this->sectionTemplateId('custom-html');
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

        if ($html) {
            $out[] = [
                'section_template_id' => $html,
                'section_type' => 'custom_html',
                'name' => 'Post body',
                'content' => [
                    'html' => '<article class="bg-white py-12 lg:py-20"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 prose prose-lg">{body}</div></article>',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        return $out;
    }

    protected function propertyEntryPreset(Template $template): array
    {
        $hero  = $this->sectionTemplateId('hero-simple');
        $html  = $this->sectionTemplateId('custom-html');
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

        if ($html) {
            $out[] = [
                'section_template_id' => $html,
                'section_type' => 'custom_html',
                'name' => 'Property meta',
                'content' => [
                    'html' => '<section class="bg-white py-16 lg:py-20"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">' .
                              '<dl class="grid grid-cols-2 gap-6 sm:grid-cols-4">' .
                              '<div><dt class="text-sm text-variant-1">Bedrooms</dt><dd class="text-xl font-bold text-on-surface">{bedrooms|—}</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Bathrooms</dt><dd class="text-xl font-bold text-on-surface">{bathrooms|—}</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Area</dt><dd class="text-xl font-bold text-on-surface">{area|—} m²</dd></div>' .
                              '<div><dt class="text-sm text-variant-1">Price</dt><dd class="text-xl font-bold text-brand">{price|—}</dd></div>' .
                              '</dl></div></section>',
                ],
                'settings' => [],
                'order' => $order++,
            ];
        }

        return $out;
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

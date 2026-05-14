<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

/**
 * Round out the primitive toolkit so every common HTML element is available
 * as a draggable section in the visual editor:
 *   - primitive-span  (inline text wrapper)
 *   - primitive-link  (anchor — <a>)
 *   - primitive-list  (ul / ol with repeater items)
 *   - primitive-icon  (inline SVG via raw markup)
 *
 * All fields accept {token} placeholders that are resolved against the
 * current entry by TokenResolver before the html_template is interpolated.
 * Idempotent — firstOrCreate by slug.
 */
return new class extends Migration
{
    private array $primitives = [];

    public function up(): void
    {
        $this->primitives = $this->definitions();

        foreach ($this->primitives as $def) {
            $tpl = SectionTemplate::firstOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'           => $def['name'],
                    'category'       => 'Primitives',
                    'description'    => $def['description'] ?? '',
                    'html_template'  => $def['html_template'],
                    'icon'           => $def['icon'] ?? '',
                    'is_system'      => true,
                    'is_active'      => true,
                    'order'          => $def['order'] ?? 100,
                    'default_settings' => [],
                ]
            );

            if (! $tpl->wasRecentlyCreated) continue;

            foreach ($def['fields'] as $f) {
                SectionTemplateField::create(array_merge(
                    ['section_template_id' => $tpl->id],
                    $f
                ));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->definitions() as $def) {
            $tpl = SectionTemplate::where('slug', $def['slug'])->first();
            if ($tpl) {
                $tpl->fields()->delete();
                $tpl->delete();
            }
        }
    }

    private function definitions(): array
    {
        return [
            // ─── <span> ──────────────────────────────────────────────────────
            [
                'slug' => 'primitive-span',
                'name' => 'Span (inline text)',
                'description' => 'Inline <span> wrapper. Useful for highlighting words inline.',
                'icon' => '<>',
                'order' => 60,
                'html_template' => '<span id="{{id}}" class="{{class}}">{{text}}</span>',
                'fields' => [
                    ['name' => 'text',  'label' => 'Text',  'type' => 'text', 'description' => 'Inline text — tokens like {title} are resolved against the current entry.', 'order' => 0],
                    ['name' => 'class', 'label' => 'CSS classes', 'type' => 'text', 'placeholder' => 'text-brand font-semibold', 'order' => 1],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'order' => 2],
                ],
            ],

            // ─── <a> ─────────────────────────────────────────────────────────
            [
                'slug' => 'primitive-link',
                'name' => 'Link (anchor)',
                'description' => 'Plain <a> link — for text links without button styling.',
                'icon' => '🔗',
                'order' => 61,
                'html_template' => '<a href="{{url}}" target="{{target}}" rel="{{rel}}" id="{{id}}" class="{{class}}">{{text}}</a>',
                'fields' => [
                    ['name' => 'text',   'label' => 'Link text', 'type' => 'text', 'description' => 'Visible text — supports tokens', 'order' => 0],
                    ['name' => 'url',    'label' => 'URL',       'type' => 'text', 'description' => 'Supports tokens too — e.g. /{template_slug}/{slug}', 'placeholder' => 'https://… or /path', 'order' => 1],
                    ['name' => 'target', 'label' => 'Open in',   'type' => 'select', 'default_value' => '_self', 'settings' => ['options' => ['_self' => 'Same tab', '_blank' => 'New tab']], 'order' => 2],
                    ['name' => 'rel',    'label' => 'rel attr',  'type' => 'text', 'placeholder' => 'noopener noreferrer', 'order' => 3],
                    ['name' => 'class',  'label' => 'CSS classes', 'type' => 'text', 'placeholder' => 'text-brand hover:underline', 'order' => 4],
                    ['name' => 'id',     'label' => 'ID',        'type' => 'text', 'order' => 5],
                ],
            ],

            // ─── <ul>/<ol> ───────────────────────────────────────────────────
            [
                'slug' => 'primitive-list',
                'name' => 'List (ul / ol)',
                'description' => 'Bullet or numbered list. Items are repeatable.',
                'icon' => '☰',
                'order' => 62,
                'html_template' => '<{{tag}} id="{{id}}" class="{{class}}">{{#each items}}<li>{{this.text}}</li>{{/each}}</{{tag}}>',
                'fields' => [
                    ['name' => 'tag',   'label' => 'Type',  'type' => 'select', 'default_value' => 'ul', 'settings' => ['options' => ['ul' => 'Bullets (ul)', 'ol' => 'Numbered (ol)']], 'order' => 0],
                    ['name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'settings' => ['fields' => [
                        ['name' => 'text', 'label' => 'Item text', 'type' => 'text'],
                    ]], 'order' => 1],
                    ['name' => 'class', 'label' => 'CSS classes', 'type' => 'text', 'placeholder' => 'list-disc pl-6 space-y-1', 'order' => 2],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'order' => 3],
                ],
            ],

            // ─── inline <svg> / icon ─────────────────────────────────────────
            [
                'slug' => 'primitive-icon',
                'name' => 'Icon (SVG)',
                'description' => 'Inline SVG icon — paste any SVG markup. Useful for small inline icons.',
                'icon' => '★',
                'order' => 63,
                'html_template' => '<span id="{{id}}" class="inline-flex items-center {{class}}">{{svg}}</span>',
                'fields' => [
                    ['name' => 'svg',   'label' => 'SVG markup', 'type' => 'textarea', 'description' => 'Paste the full <svg>…</svg> markup', 'placeholder' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="..."/></svg>', 'order' => 0],
                    ['name' => 'class', 'label' => 'CSS classes', 'type' => 'text', 'placeholder' => 'w-5 h-5 text-brand', 'order' => 1],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'order' => 2],
                ],
            ],
        ];
    }
};

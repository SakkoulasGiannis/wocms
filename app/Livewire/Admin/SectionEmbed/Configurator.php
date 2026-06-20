<?php

namespace App\Livewire\Admin\SectionEmbed;

use App\Models\CardTemplate;
use App\Models\Template;
use App\Services\CardRenderer;
use Livewire\Component;

/**
 * Data provider for the SectionEmbed inline editor.
 *
 * After several iterations with modal-style UIs that fought with the
 * fullscreen wysiwyg overlay, the configurator is now rendered INSIDE
 * the EditorJS block placeholder by the SectionEmbedTool JS class —
 * no modal, no Alpine, no z-index battles.
 *
 * This Livewire component's only job is to expose the server-side
 * data (templates list, card library, token catalog) as a single
 * `window.SE_DATA` JSON blob so the tool can populate its dropdowns
 * client-side. Mounted once per admin page from the layout.
 */
class Configurator extends Component
{
    /**
     * Save a custom card template to the library so it can be reused
     * in future SectionEmbed blocks. Called from inside the block's
     * inline form via fetch(); keeps the UI in EditorJS instead of
     * forcing a Livewire round trip that would morph the editor away.
     */
    public function saveCardToLibrary(string $slug, string $name, string $html, ?string $sourceTemplateSlug = null): array
    {
        $slug = trim($slug);
        $name = trim($name);
        if ($slug === '' || $name === '' || $html === '') {
            return ['ok' => false, 'error' => 'Slug, name and HTML are all required.'];
        }
        CardTemplate::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'html' => $html,
                'is_system' => false,
                'source_template_slug' => $sourceTemplateSlug ?: null,
                'category' => 'Custom',
                'sort_order' => 100,
            ],
        );

        return ['ok' => true, 'slug' => $slug];
    }

    public function render()
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->whereNotNull('model_class')
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(fn ($t) => ['slug' => $t->slug, 'name' => $t->name])
            ->all();

        $cards = CardTemplate::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['slug', 'name', 'description', 'html', 'source_template_slug', 'is_system'])
            ->map(fn ($c) => [
                'slug' => $c->slug,
                'name' => $c->name,
                'description' => $c->description,
                'html' => $c->html,
                'source_template_slug' => $c->source_template_slug,
                'is_system' => (bool) $c->is_system,
            ])
            ->all();

        $tokensByTemplate = [];
        $renderer = app(CardRenderer::class);
        foreach (Template::query()->whereNotNull('model_class')->get() as $t) {
            $tokensByTemplate[$t->slug] = $renderer->availableTokens($t);
        }
        $tokensByTemplate['__generic__'] = $renderer->availableTokens(null);

        return view('livewire.admin.section-embed.configurator', [
            'templates' => $templates,
            'cards' => $cards,
            'tokensByTemplate' => $tokensByTemplate,
        ]);
    }
}

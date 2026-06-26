<?php

namespace App\VisualBuilder;

use App\Models\ContentNode;
use App\Models\Home;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Model;
use Weborange\VisualBuilder\Contracts\BuilderPersistence;

/**
 * Host implementation of the visual-builder persistence contract.
 *
 * The builder `target` is a ContentNode id (content_tree row). Every editable
 * content entity — the Home entry, Pages, and any model using the HasSections
 * trait — is reachable through its ContentNode, so the builder can target them
 * all uniformly instead of assuming the target is a Page.
 */
class CmsBuilderPersistence implements BuilderPersistence
{
    /**
     * ContentNode content types that expose section-based content and are
     * therefore editable in the visual builder.
     *
     * @var array<int, class-string>
     */
    private const EDITABLE_TYPES = [
        Home::class,
        Page::class,
    ];

    public function targets(): array
    {
        return ContentNode::query()
            ->whereIn('content_type', self::EDITABLE_TYPES)
            ->whereNotNull('content_id')
            ->orderByRaw("CASE WHEN url_path = '/' THEN 0 ELSE 1 END")
            ->orderBy('url_path')
            ->get(['id', 'title', 'url_path', 'content_type', 'content_id'])
            ->map(function (ContentNode $n): array {
                $model = $this->modelFor($n);
                $mode = $model ? ($model->getAttribute('render_mode') ?: 'sections') : 'sections';
                $isHome = $n->url_path === '/';
                $label = ($isHome ? '🏠 ' : '').($n->title ?: 'Untitled').' — '.($n->url_path ?: '/');
                if ($mode !== 'sections') {
                    $label .= ' (grapejs)';
                }

                return [
                    'id' => (int) $n->id,
                    'label' => $label,
                    'mode' => $mode,
                    'url' => $n->url_path ? url($n->url_path) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function sections(int|string $targetId): array
    {
        $model = $this->modelForTarget($targetId);
        if (! $model) {
            return [];
        }

        return $model->sections()
            ->whereIn('section_type', ['html', 'nb_loop'])
            ->orderBy('order')
            ->get(['id', 'name', 'section_type', 'content'])
            ->map(function (PageSection $s): array {
                $c = is_array($s->content) ? $s->content : [];
                $isLoop = $s->section_type === 'nb_loop';

                return [
                    'id' => $s->id,
                    'name' => ($s->name ?: ('Section #'.$s->id)).($isLoop ? ' [loop]' : ''),
                    'html' => $isLoop ? ($c['item_html'] ?? '') : ($c['html'] ?? ''),
                    'is_loop' => $isLoop,
                    'source' => $c['source'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    public function seedFor(int|string $targetId): ?string
    {
        $model = $this->modelForTarget($targetId);
        if (! $model) {
            return null;
        }

        if ($this->isSectionsMode($model)) {
            $parts = $model->sections()
                ->where('is_active', true)
                ->whereNull('parent_section_id')
                ->orderBy('order')
                ->get()
                ->map(function (PageSection $s): string {
                    $c = is_array($s->content) ? $s->content : [];
                    if ($s->section_type === 'html') {
                        return (string) ($c['html'] ?? '');
                    }
                    if ($s->section_type === 'nb_loop') {
                        $query = json_encode([
                            'source' => $c['source'] ?? '',
                            'limit' => (int) ($c['limit'] ?? 12),
                            'order_by' => $c['order_by'] ?? 'created_at',
                            'order_dir' => $c['order_dir'] ?? 'desc',
                        ]);

                        return '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-vb-loop=\''.$query.'\'>'.((string) ($c['item_html'] ?? '')).'</div>';
                    }

                    // Other section types: render the section to its frontend
                    // HTML so it becomes editable in the builder.
                    try {
                        $rendered = trim((string) view('pagebuilder::partials.render-section', ['section' => $s])->render());
                        if ($rendered !== '') {
                            return $rendered;
                        }
                    } catch (\Throwable $e) {
                        // fall through to cached HTML
                    }

                    return (string) ($s->rendered_html ?? '');
                })
                ->filter(fn (string $h): bool => trim($h) !== '')
                ->implode("\n");

            return $parts !== '' ? $parts : null;
        }

        // GrapesJS / simple pages: the raw body HTML is directly editable.
        $body = (string) $model->getAttribute('body');

        return trim($body) !== '' ? $body : null;
    }

    public function save(array $payload): array
    {
        $node = $this->resolveNode($payload['target_id'] ?? null);
        $model = $node ? $this->modelFor($node) : null;
        if (! $node || ! $model) {
            return ['success' => false, 'message' => 'Target content not found.'];
        }

        $title = $node->title ?: class_basename($model);
        $name = trim((string) ($payload['name'] ?? '')) ?: 'Builder section';
        $loop = $payload['loop'] ?? null;

        if (! $this->isSectionsMode($model)) {
            if (empty($payload['convert'])) {
                return [
                    'success' => false,
                    'needs_convert' => true,
                    'message' => "“{$title}” isn't in sections mode. Tick “Switch to sections mode” to convert it (existing GrapesJS content is hidden, not deleted).",
                ];
            }
            $model->forceFill(['render_mode' => 'sections'])->save();
        }

        $sectionType = $loop ? 'nb_loop' : 'html';
        $content = $loop
            ? [
                'item_html' => $payload['html'],
                'source' => $loop['source'],
                'columns' => (int) ($loop['columns'] ?? 3),
                'limit' => (int) ($loop['limit'] ?? 12),
                'order_by' => $loop['order_by'] ?? 'created_at',
                'order_dir' => ($loop['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
                'heading' => trim((string) ($loop['heading'] ?? '')),
            ]
            : ['html' => $payload['html']];

        $replace = ! empty($payload['replace']);

        if ($replace) {
            // Migration: soft-delete the entity's existing sections (recoverable),
            // then save the builder output as its single content section.
            $model->sections()->delete();
        }

        if (! $replace && ! empty($payload['section_id'])) {
            $section = $model->sections()->whereKey($payload['section_id'])->first();
            if (! $section) {
                return ['success' => false, 'message' => 'That section no longer exists on this content.'];
            }
            $section->update(['section_type' => $sectionType, 'name' => $name, 'content' => $content]);
            $verb = 'Updated';
        } else {
            $section = $model->sections()->create([
                'section_type' => $sectionType,
                'name' => $name,
                'content' => $content,
                'settings' => [],
                'order' => ((int) $model->sections()->max('order')) + 1,
                'is_active' => true,
                'is_visible' => true,
            ]);
            $verb = $replace ? 'Migrated — replaced content of' : 'Saved';
        }

        // Bust the cached frontend render so the new content shows immediately.
        \App\Services\CacheInvalidator::clearContentNode($node->id);

        return [
            'success' => true,
            'section_id' => $section->id,
            'url' => $node->url_path ? url($node->url_path) : null,
            'edit_url' => route('admin.page-sections.visual', [
                'sectionableType' => str_replace('\\', '-', $node->content_type),
                'sectionableId' => $model->getKey(),
            ]),
            'message' => "{$verb} ".($loop ? 'loop ' : '')."section “{$name}” on “{$title}”.",
        ];
    }

    private function resolveNode(int|string|null $targetId): ?ContentNode
    {
        if ($targetId === null || $targetId === '') {
            return null;
        }

        return ContentNode::query()->whereKey($targetId)->first();
    }

    private function modelForTarget(int|string $targetId): ?Model
    {
        $node = $this->resolveNode($targetId);

        return $node ? $this->modelFor($node) : null;
    }

    private function modelFor(ContentNode $node): ?Model
    {
        $class = $node->content_type;
        if (! $class || ! $node->content_id || ! class_exists($class)) {
            return null;
        }

        $model = $class::find($node->content_id);

        return ($model && method_exists($model, 'sections')) ? $model : null;
    }

    private function isSectionsMode(Model $model): bool
    {
        $mode = $model->getAttribute('render_mode');

        return $mode === null || $mode === 'sections';
    }
}

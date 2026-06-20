<?php

namespace App\VisualBuilder;

use App\Models\ContentNode;
use App\Models\Page;
use App\Models\PageSection;
use Weborange\VisualBuilder\Contracts\BuilderPersistence;

/**
 * Host implementation of the visual-builder persistence contract: stores builder
 * output as page_sections (type 'html' or 'nb_loop') on reachable Pages.
 */
class CmsBuilderPersistence implements BuilderPersistence
{
    public function targets(): array
    {
        $nodes = ContentNode::query()
            ->where('content_type', Page::class)
            ->whereNotNull('content_id')
            ->orderBy('url_path')
            ->get(['title', 'url_path', 'content_id']);

        $modes = Page::query()
            ->whereIn('id', $nodes->pluck('content_id'))
            ->pluck('render_mode', 'id');

        return $nodes->map(function (ContentNode $n) use ($modes): array {
            $mode = $modes[$n->content_id] ?? 'full_page_grapejs';
            $label = ($n->title ?: 'Untitled').' — '.($n->url_path ?: '/');
            if ($mode !== 'sections') {
                $label .= ' (grapejs)';
            }

            return ['id' => (int) $n->content_id, 'label' => $label, 'mode' => $mode];
        })->values()->all();
    }

    public function sections(int|string $targetId): array
    {
        $page = Page::find($targetId);
        if (! $page) {
            return [];
        }

        return $page->sections()
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

    public function save(array $payload): array
    {
        $page = Page::find($payload['target_id']);
        if (! $page) {
            return ['success' => false, 'message' => 'Target page not found.'];
        }

        $name = trim((string) ($payload['name'] ?? '')) ?: 'Builder section';
        $loop = $payload['loop'] ?? null;

        if ($page->render_mode !== 'sections') {
            if (empty($payload['convert'])) {
                return [
                    'success' => false,
                    'needs_convert' => true,
                    'message' => "“{$page->title}” isn't in sections mode. Tick “Switch to sections mode” to convert it (existing GrapesJS content is hidden, not deleted).",
                ];
            }
            $page->update(['render_mode' => 'sections']);
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

        if (! empty($payload['section_id'])) {
            $section = $page->sections()->whereKey($payload['section_id'])->first();
            if (! $section) {
                return ['success' => false, 'message' => 'That section no longer exists on this page.'];
            }
            $section->update(['section_type' => $sectionType, 'name' => $name, 'content' => $content]);
            $verb = 'Updated';
        } else {
            $section = $page->sections()->create([
                'section_type' => $sectionType,
                'name' => $name,
                'content' => $content,
                'settings' => [],
                'order' => ((int) $page->sections()->max('order')) + 1,
                'is_active' => true,
                'is_visible' => true,
            ]);
            $verb = 'Saved';
        }

        $node = ContentNode::query()
            ->where('content_type', Page::class)
            ->where('content_id', $page->id)
            ->first();

        return [
            'success' => true,
            'section_id' => $section->id,
            'url' => $node?->url_path ? url($node->url_path) : null,
            'edit_url' => route('admin.page-sections.visual', [
                'sectionableType' => 'App-Models-Page',
                'sectionableId' => $page->id,
            ]),
            'message' => "{$verb} ".($loop ? 'loop ' : '')."section “{$name}” on “{$page->title}”.",
        ];
    }
}

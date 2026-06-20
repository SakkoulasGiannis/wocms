<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Services\PageBuilderAgent;
use App\Services\PageCompiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\PageBuilder\Models\PageSection;
use Modules\PageBuilder\Models\SectionTemplate;

/**
 * HTTP surface for the PageCompiler / AI workflow. All admin-gated.
 *
 *   GET  /admin/api/pages/{id}/export           → full Page+sections JSON spec
 *   GET  /admin/api/templates/{slug}/skeleton   → single-template skeleton
 *   GET  /admin/api/templates/skeletons         → all-templates skeletons
 *   POST /admin/api/pages/compile               → compile JSON spec to DB
 *   POST /admin/api/pages/ai/create             → AI-generated new page
 *   POST /admin/api/pages/ai/edit               → AI edit on existing page
 */
class PageCompilerController extends Controller
{
    public function export(int|string $id): JsonResponse
    {
        $page = is_numeric($id) ? Page::find($id) : Page::where('slug', $id)->first();
        if (! $page) {
            return response()->json(['error' => "Page not found: {$id}"], 404);
        }

        $spec = [
            'type' => 'page',
            'page' => array_merge(
                $page->only(['title', 'slug', 'status', 'render_mode', 'featured_image', 'body', 'body_css']),
                ['seo' => $this->collectSeo($page)]
            ),
            'sections' => $this->exportSections(Page::class, $page->id, null),
        ];

        return response()->json($spec);
    }

    public function templateSkeleton(string $slug): JsonResponse
    {
        $tpl = SectionTemplate::with('fields')->where('slug', $slug)->first();
        if (! $tpl) {
            return response()->json(['error' => "Template not found: {$slug}"], 404);
        }

        return response()->json($this->buildSkeleton($tpl));
    }

    public function allSkeletons(): JsonResponse
    {
        $all = SectionTemplate::with('fields')
            ->where('is_active', true)
            ->orderBy('category')->orderBy('order')
            ->get()
            ->map(fn ($t) => $this->buildSkeleton($t));

        return response()->json($all);
    }

    public function compile(Request $request): JsonResponse
    {
        $spec = $request->input('spec') ?? $request->all();
        if (is_string($spec)) {
            $decoded = json_decode($spec, true);
            $spec = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($spec)) {
            return response()->json(['ok' => false, 'error' => 'Invalid spec'], 422);
        }

        $result = PageCompiler::fromArray($spec)->compile();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function aiCreate(Request $request, PageBuilderAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:5',
            'templates' => 'nullable|array',
        ]);
        $result = $agent->createPage($validated['prompt'], $validated['templates'] ?? []);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function aiEdit(Request $request, PageBuilderAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'prompt' => 'required|string|min:5',
        ]);
        $result = $agent->editPage($validated['page'], $validated['prompt']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * List AI revisions for a page (newest first, capped at 50).
     */
    public function revisions(int $pageId): JsonResponse
    {
        $page = Page::find($pageId);
        if (! $page) {
            return response()->json(['error' => "Page not found: {$pageId}"], 404);
        }

        $rows = PageRevision::with('user:id,name,email')
            ->where('page_id', $pageId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'source', 'prompt', 'user_id', 'created_at']);

        return response()->json([
            'page_id' => $pageId,
            'revisions' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'source' => $r->source,
                'source_label' => $r->sourceLabel(),
                'prompt' => $r->prompt,
                'user_name' => $r->user?->name,
                'created_at' => $r->created_at->toIso8601String(),
                'created_ago' => $r->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Restore a page to a snapshotted revision. The restore is itself
     * captured as a new pre-revision so it's undoable too.
     */
    public function restore(int $revisionId): JsonResponse
    {
        $rev = PageRevision::find($revisionId);
        if (! $rev) {
            return response()->json(['ok' => false, 'error' => "Revision not found: {$revisionId}"], 404);
        }

        try {
            $result = PageCompiler::fromArray($rev->spec)
                ->withRevisionMeta(
                    source: 'ai-edit',
                    prompt: "Restored from revision #{$rev->id} ({$rev->sourceLabel()})",
                    userId: Auth::id(),
                )
                ->compile();
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json($result + ['restored_from' => $rev->id], $result['ok'] ? 200 : 422);
    }

    /* ── helpers ─────────────────────────────────────────────────────── */
    protected function collectSeo(Page $page): array
    {
        $seo = [];
        foreach ($page->getAttributes() as $col => $val) {
            if (str_starts_with($col, 'seo_')) {
                $seo[substr($col, 4)] = $val;
            }
        }

        return $seo;
    }

    protected function exportSections(string $type, int $id, ?int $parent): array
    {
        $rows = PageSection::where('sectionable_type', $type)
            ->where('sectionable_id', $id)
            ->where('parent_section_id', $parent)
            ->orderBy('order')
            ->get();

        return $rows->map(function ($s) use ($type, $id) {
            $entry = $s->only([
                'id', 'section_type', 'name', 'order', 'scope',
                'is_active', 'is_visible', 'section_template_id', 'edit_mode',
                'content', 'settings', 'css',
            ]);
            $children = $this->exportSections($type, $id, $s->id);
            if (! empty($children)) {
                $entry['children'] = $children;
            }

            return $entry;
        })->all();
    }

    protected function buildSkeleton(SectionTemplate $tpl): array
    {
        $content = [];
        foreach ($tpl->fields as $f) {
            $content[$f->name] = match ($f->type) {
                'wysiwyg', 'editorjs' => ['time' => 0, 'blocks' => [], 'version' => '2.30.0'],
                'repeater', 'gallery' => [],
                'checkbox' => false,
                'number' => 0,
                'select' => $f->options[0]['value'] ?? '',
                default => '',
            };
        }

        return [
            'section_type' => $tpl->slug,
            'section_template_id' => $tpl->id,
            'name' => $tpl->name,
            'order' => 0,
            'is_active' => true,
            'is_visible' => true,
            'content' => $content,
            'settings' => $tpl->default_settings ?? [],
            '_meta' => [
                'category' => $tpl->category,
                'description' => $tpl->description,
            ],
        ];
    }
}

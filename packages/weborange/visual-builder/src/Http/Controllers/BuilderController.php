<?php

namespace Weborange\VisualBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Weborange\VisualBuilder\Contracts\AiGenerator;
use Weborange\VisualBuilder\Contracts\BuilderPersistence;
use Weborange\VisualBuilder\Contracts\TokenSource;

/**
 * Thin controller: renders the builder and delegates persistence / token
 * lookups to the host-provided contracts. Contains no app-specific logic.
 */
class BuilderController extends Controller
{
    public function __construct(
        private readonly BuilderPersistence $persistence,
        private readonly TokenSource $tokens,
        private readonly AiGenerator $ai,
    ) {}

    /**
     * Turn a natural-language prompt into section HTML via the host AI generator.
     */
    public function ai(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prompt' => 'nullable|string|max:4000',
            'current_html' => 'nullable|string',
            'mode' => 'nullable|string|in:generate,fix_seo,apply_template',
            'template_id' => 'nullable',
            'page_title' => 'nullable|string|max:300',
        ]);

        $mode = $data['mode'] ?? 'generate';
        $pageTitle = $data['page_title'] ?? null;

        if ($mode === 'fix_seo') {
            $result = $this->ai->fixStructure((string) ($data['current_html'] ?? ''), $pageTitle);
        } elseif ($mode === 'apply_template') {
            if (empty($data['template_id'])) {
                return response()->json(['ok' => false, 'error' => 'Pick a style template first.'], 422);
            }
            $reference = (string) ($this->persistence->seedFor($data['template_id']) ?? '');
            $result = $this->ai->restyleToTemplate((string) ($data['current_html'] ?? ''), $reference, $pageTitle);
        } else {
            if (trim((string) ($data['prompt'] ?? '')) === '') {
                return response()->json(['ok' => false, 'error' => 'Describe what you want first.'], 422);
            }
            $styleReference = ! empty($data['template_id'])
                ? $this->persistence->seedFor($data['template_id'])
                : null;
            $result = $this->ai->generate($data['prompt'], $data['current_html'] ?? null, $styleReference);
        }

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function index(Request $request)
    {
        $targetId = $request->query('target');
        $seed = ($targetId !== null && $targetId !== '') ? $this->persistence->seedFor($targetId) : null;

        $styleTemplates = $this->persistence->styleTemplates();
        $isTemplate = $targetId !== null && in_array(
            (string) $targetId,
            array_map(fn (array $t): string => (string) $t['id'], $styleTemplates),
            true
        );

        return view('visual-builder::builder', [
            'vbTargets' => $this->persistence->targets(),
            'vbSources' => $this->tokens->sources(),
            'vbForms' => $this->tokens->forms(),
            'vbSliders' => $this->tokens->sliders(),
            'vbNodes' => $this->tokens->nodes(),
            'vbSiteCss' => $this->tokens->siteCss(),
            'vbStyleTemplates' => $styleTemplates,
            'vbIsTemplate' => $isTemplate,
            'vbAssetVersion' => $this->assetVersion(),
            'vbSeedHtml' => $seed,
            'vbPreselectTarget' => $targetId,
        ]);
    }

    /** Latest mtime across the bundled JS, for cache-busting. */
    private function assetVersion(): string
    {
        $latest = 0;
        foreach (glob(__DIR__.'/../../../resources/js/new-builder/*.js') ?: [] as $file) {
            $latest = max($latest, (int) filemtime($file));
        }

        return (string) $latest;
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'html' => 'required|string',
            'target_id' => 'required',
            'section_id' => 'nullable',
            'name' => 'nullable|string|max:160',
            'convert' => 'sometimes|boolean',
            'replace' => 'sometimes|boolean',
            'loop_source' => 'nullable|string',
            'loop_columns' => 'nullable|integer',
            'loop_limit' => 'nullable|integer',
            'loop_order_by' => 'nullable|string|max:60',
            'loop_order_dir' => 'nullable|string|in:asc,desc',
            'loop_heading' => 'nullable|string|max:160',
            'site_css' => 'nullable|string',
            'is_template' => 'sometimes|boolean',
        ]);

        if ($request->has('site_css')) {
            $this->tokens->saveSiteCss((string) ($data['site_css'] ?? ''));
        }

        $loop = ! empty($data['loop_source']) ? [
            'source' => $data['loop_source'],
            'columns' => (int) ($data['loop_columns'] ?? 3),
            'limit' => (int) ($data['loop_limit'] ?? 12),
            'order_by' => $data['loop_order_by'] ?? 'created_at',
            'order_dir' => ($data['loop_order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
            'heading' => (string) ($data['loop_heading'] ?? ''),
        ] : null;

        $result = $this->persistence->save([
            'target_id' => $data['target_id'],
            'section_id' => $data['section_id'] ?? null,
            'html' => $data['html'],
            'name' => (string) ($data['name'] ?? ''),
            'convert' => (bool) ($data['convert'] ?? false),
            'replace' => (bool) ($data['replace'] ?? false),
            'loop' => $loop,
        ] + ($request->has('is_template') ? ['is_template' => (bool) $data['is_template']] : []));

        $status = ($result['success'] ?? false) ? 200 : (($result['needs_convert'] ?? false) ? 409 : 422);

        return response()->json($result, $status);
    }

    public function sections(Request $request): JsonResponse
    {
        $data = $request->validate(['target_id' => 'required']);

        return response()->json(['sections' => $this->persistence->sections($data['target_id'])]);
    }

    public function tokens(Request $request): JsonResponse
    {
        $data = $request->validate(['source' => 'required|string']);

        return response()->json(['tokens' => $this->tokens->tokens($data['source'])]);
    }

    /**
     * Render a repeater's item template against real entities, for the live
     * preview (returns resolved HTML per entity).
     */
    public function sample(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => 'required|string',
            'item_html' => 'required|string',
            'limit' => 'nullable|integer',
            'order_by' => 'nullable|string|max:60',
            'order_dir' => 'nullable|string|in:asc,desc',
            'offset' => 'nullable|integer',
            'filter_field' => 'nullable|string|max:60',
            'filter_value' => 'nullable|string|max:160',
            'ids' => 'nullable|array',
            'parent' => 'nullable',
        ]);

        $items = $this->tokens->renderLoop($data['source'], [
            'limit' => (int) ($data['limit'] ?? 6),
            'order_by' => $data['order_by'] ?? 'created_at',
            'order_dir' => $data['order_dir'] ?? 'desc',
            'offset' => (int) ($data['offset'] ?? 0),
            'filter_field' => $data['filter_field'] ?? null,
            'filter_value' => $data['filter_value'] ?? null,
            'ids' => $data['ids'] ?? null,
            'parent' => $data['parent'] ?? null,
        ], $data['item_html']);

        return response()->json(['items' => $items]);
    }

    public function renderSlider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|string|max:60',
        ]);

        return response()->json(['html' => $this->tokens->renderSlider($data['id'])]);
    }

    public function entries(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => 'required|string|max:80',
        ]);

        return response()->json(['entries' => $this->tokens->entries($data['source'])]);
    }
}

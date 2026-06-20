<?php

namespace Weborange\VisualBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
    ) {}

    public function index()
    {
        return view('visual-builder::builder', [
            'vbTargets' => $this->persistence->targets(),
            'vbSources' => $this->tokens->sources(),
            'vbAssetVersion' => $this->assetVersion(),
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
            'loop_source' => 'nullable|string',
            'loop_columns' => 'nullable|integer',
            'loop_limit' => 'nullable|integer',
            'loop_order_by' => 'nullable|string|max:60',
            'loop_order_dir' => 'nullable|string|in:asc,desc',
            'loop_heading' => 'nullable|string|max:160',
        ]);

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
            'loop' => $loop,
        ]);

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
}

<?php

namespace Weborange\VisualBuilder\Contracts;

/**
 * The host app implements this to let the builder's "AI" button turn a
 * natural-language prompt into page-section HTML. Bind a real implementation in
 * the host (e.g. backed by Gemini/Claude); the package ships a Null no-op.
 */
interface AiGenerator
{
    /**
     * Generate (or, when $currentHtml is given, modify) a page section from a
     * natural-language prompt. Return clean section HTML the builder can parse.
     *
     * @return array{ok:bool, html?:string, error?:string}
     */
    public function generate(string $prompt, ?string $currentHtml = null): array;
}

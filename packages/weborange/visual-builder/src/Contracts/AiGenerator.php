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
     * When $styleReference is given (the HTML/CSS of a "style template" page) the
     * generator should mimic its fonts, sizes, colours, spacing, Tailwind classes
     * and overall structure while inserting the user's requested content.
     *
     * @return array{ok:bool, html?:string, error?:string}
     */
    public function generate(string $prompt, ?string $currentHtml = null, ?string $styleReference = null): array;

    /**
     * Fix ONLY the semantic structure of the given page HTML for SEO/accessibility
     * (heading hierarchy: a single top-level h1, correctly ordered h2/h3, the main
     * title promoted to the right level). Visible text, CSS classes and styling are
     * preserved unchanged. When no h1 exists and $pageTitle is given, create one
     * from it. Return the corrected HTML the builder can re-parse.
     *
     * @return array{ok:bool, html?:string, error?:string}
     */
    public function fixStructure(string $html, ?string $pageTitle = null): array;

    /**
     * Re-style the given page HTML so it adopts the look of $styleReference (a
     * "style template" page): reuse its Tailwind classes, fonts, sizes, colours,
     * spacing and section structure, while KEEPING the current page's actual text
     * content. Also fixes the heading hierarchy (single h1, created from $pageTitle
     * when the content has none). Return the restyled HTML the builder can re-parse.
     *
     * @return array{ok:bool, html?:string, error?:string}
     */
    public function restyleToTemplate(string $html, string $styleReference, ?string $pageTitle = null): array;
}

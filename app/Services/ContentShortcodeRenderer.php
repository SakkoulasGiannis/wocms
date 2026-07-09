<?php

namespace App\Services;

use App\Models\Form;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

/**
 * Safely substitutes the `<x-form slug="..." />` embed token inside
 * database-stored content (Page bodies, GrapeJS HTML, simple_content
 * fields) with the real rendered form — WITHOUT ever compiling the
 * surrounding content as Blade.
 *
 * Why this exists: `FrontendController::renderNodeContent()` used to call
 * `Blade::render($rawHtml, $data)` on raw DB content for the
 * `simple_content` and `full_page_grapejs` render modes. That is a remote
 * code execution hole — any `@php`, `{{ ... }}`, or `<x-anything/>` an AI
 * generator or a content editor ever wrote into a page would be executed
 * server-side. This class replaces that: content HTML is *never* handed to
 * Blade::render(). Instead we scan for one exact, anchored token shape and
 * substitute only that, leaving everything else — including anything that
 * merely *looks* like Blade — completely inert.
 *
 * The one Blade call this class does make is `Blade::render('<x-form
 * :slug="$slug" />', ['slug' => $slug])` — a **fixed, hardcoded template
 * string** we wrote, not attacker/DB-controlled input. `$slug` is passed as
 * a bound PHP variable, never string-interpolated into the compiled
 * template, so there is no injection vector. The exact same pattern
 * already exists in `App\VisualBuilder\LoopRenderer::renderForm()` for the
 * new-builder's `data-vb-form` attribute — this is an established-safe
 * technique in this codebase, just applied to the `<x-form>` tag syntax
 * documented in CLAUDE.md / RECIPES.md as the sanctioned form embed.
 */
class ContentShortcodeRenderer
{
    /**
     * Matches ONLY the exact, self-closing `<x-form slug="..." />` (or
     * single-quoted) token. Anything else — unclosed tags, extra
     * attributes, `<x-form slug="x">...</x-form>` with children, any other
     * component tag — does not match and is left as inert literal text.
     * The slug character class doubles as the validation: only
     * `^[a-z][a-z0-9_-]*$` can ever be captured.
     */
    private const TOKEN_PATTERN = '/<x-form\s+slug=(["\'])([a-z][a-z0-9_-]*)\1\s*\/>/';

    /**
     * Replace every `<x-form slug="..." />` token in $html with the
     * rendered form, leaving everything else untouched. Never compiles
     * $html as Blade.
     */
    public function render(?string $html): string
    {
        if ($html === null || $html === '') {
            return (string) $html;
        }

        $result = preg_replace_callback(
            self::TOKEN_PATTERN,
            fn (array $m): string => $this->renderFormToken($m[2]),
            $html
        );

        // preg_replace_callback returns null only on a regex engine error
        // (e.g. backtrack limit) — fail safe to the original, un-substituted
        // HTML rather than blanking the page.
        return $result ?? $html;
    }

    /**
     * @param  string  $slug  already validated by TOKEN_PATTERN's character class
     */
    protected function renderFormToken(string $slug): string
    {
        // Defense in depth: re-validate even though the regex already
        // constrains this, in case the pattern is ever loosened later.
        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $slug)) {
            return '';
        }

        $exists = Form::where('slug', $slug)->where('is_active', true)->exists();

        if (! $exists) {
            // Tidy, escaped, invisible placeholder — never an error page,
            // never the raw token, never a stack trace.
            return '<!-- form not found: '.e($slug).' -->';
        }

        try {
            return Blade::render('<x-form :slug="$slug" />', ['slug' => $slug]);
        } catch (\Throwable $e) {
            Log::warning('ContentShortcodeRenderer: form render failed for '.$slug.': '.$e->getMessage());

            return '<!-- form render failed: '.e($slug).' -->';
        }
    }
}

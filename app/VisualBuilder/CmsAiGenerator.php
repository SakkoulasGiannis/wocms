<?php

namespace App\VisualBuilder;

use App\Services\AI\AIManager;
use Weborange\VisualBuilder\Contracts\AiGenerator;

/**
 * Host AI generator for the visual builder: turns a natural-language prompt into
 * a self-contained Tailwind HTML section using the app's active AI provider.
 */
class CmsAiGenerator implements AiGenerator
{
    public function __construct(private readonly AIManager $ai) {}

    public function generate(string $prompt, ?string $currentHtml = null, ?string $styleReference = null): array
    {
        try {
            $resp = $this->ai->getProvider()->chat($this->buildPrompt($prompt, $currentHtml, $styleReference));
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'AI request failed: '.$e->getMessage()];
        }

        if (! ($resp->success ?? true)) {
            return ['ok' => false, 'error' => $resp->error ?: 'AI request failed.'];
        }

        $html = $this->cleanHtml((string) $resp->content);

        if ($html === '') {
            return ['ok' => false, 'error' => 'The AI did not return usable HTML. Try rephrasing your request.'];
        }

        return ['ok' => true, 'html' => $html];
    }

    public function fixStructure(string $html, ?string $pageTitle = null): array
    {
        if (trim($html) === '') {
            return ['ok' => false, 'error' => 'Nothing to fix — the page is empty.'];
        }

        $titleRule = ($pageTitle !== null && trim($pageTitle) !== '')
            ? "\n        - If the content has NO <h1> at all, create one using this page title: \"".trim($pageTitle)."\", placed as the first heading of the main content."
            : '';

        $system = <<<SYS
        You are an SEO and accessibility expert. You are given the full HTML of a web page's content.
        Fix ONLY the semantic heading structure and tags. Rules:
        - There must be exactly ONE <h1>: the main title of the page content. If the main title currently uses the wrong tag (e.g. <h4>, <h2>, a styled <div> or <p>), convert it to <h1>.{$titleRule}
        - Headings below it must follow a correct, non-skipping order: <h1> then <h2>, then <h3> under an <h2>, etc. Never jump from <h1> straight to <h4>.
        - Keep ALL visible text exactly the same — do not rewrite, add, translate or remove any words.
        - Keep ALL CSS classes, inline styles, attributes, ids, images, links and the overall structure intact. Change ONLY tag names / heading levels where needed for correct semantics.
        - Do not restyle and do not add or remove elements other than changing a tag's level.
        - Output ONLY the corrected raw HTML. No markdown fences, no explanation.
        SYS;

        return $this->sendAndClean($system."\n\nHTML to fix:\n\n".$html);
    }

    public function restyleToTemplate(string $html, string $styleReference, ?string $pageTitle = null): array
    {
        if (trim($html) === '') {
            return ['ok' => false, 'error' => 'Nothing to restyle — the page is empty.'];
        }
        if (trim($styleReference) === '') {
            return ['ok' => false, 'error' => 'Pick a style template first.'];
        }

        $titleRule = ($pageTitle !== null && trim($pageTitle) !== '')
            ? "\n        - If the content has no main heading, add an <h1> using this page title: \"".trim($pageTitle)."\"."
            : '';

        $system = <<<SYS
        You are an expert web designer. You are given the CURRENT HTML content of a page and a REFERENCE template page. Rebuild the current content so it adopts the reference's visual design.
        Rules:
        - Reuse the reference's Tailwind utility-class patterns, fonts, font sizes/weights, colours, spacing, button styles, container widths and overall SECTION STRUCTURE.
        - KEEP the current page's actual text content, headings text, links and images — only change the markup/classes/structure around them so it looks like it belongs to the same site as the reference.
        - Fix the heading hierarchy: exactly one <h1> for the main title, then correctly ordered <h2>/<h3>.{$titleRule}
        - Style with Tailwind utility classes (no <style>, no inline style). Keep real image URLs; use https://placehold.co/WIDTHxHEIGHT only where the current content has no image.
        - Output ONLY the rebuilt raw HTML. No markdown fences, no explanation.
        SYS;

        return $this->sendAndClean(
            $system."\n\nREFERENCE TEMPLATE:\n".$this->trimReference($styleReference)."\n\nCURRENT CONTENT TO RESTYLE:\n".$html
        );
    }

    /**
     * Send a prompt to the active provider and clean the returned HTML — strip
     * markdown fences and any leading prose (the page HTML can start with any tag,
     * not just <section>).
     *
     * @return array{ok:bool, html?:string, error?:string}
     */
    private function sendAndClean(string $prompt): array
    {
        try {
            $resp = $this->ai->getProvider()->chat($prompt);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'AI request failed: '.$e->getMessage()];
        }

        if (! ($resp->success ?? true)) {
            return ['ok' => false, 'error' => $resp->error ?: 'AI request failed.'];
        }

        $out = trim((string) $resp->content);
        $out = preg_replace('/^```[a-zA-Z]*\s*/', '', $out);
        $out = preg_replace('/\s*```\s*$/', '', (string) $out);
        $pos = strpos((string) $out, '<');
        if ($pos !== false && $pos > 0) {
            $out = substr((string) $out, $pos);
        }
        $out = trim((string) $out);

        if ($out === '') {
            return ['ok' => false, 'error' => 'The AI did not return usable HTML. Try again.'];
        }

        return ['ok' => true, 'html' => $out];
    }

    private function buildPrompt(string $prompt, ?string $currentHtml, ?string $styleReference = null): string
    {
        $system = <<<'SYS'
        You are an expert web designer building ONE page section for a website.
        Output ONLY raw HTML for a single self-contained section. Rules:
        - Use semantic HTML5 with a <section> root element.
        - Style EVERYTHING with Tailwind CSS utility classes (no <style>, no inline style, no custom CSS).
        - Make it responsive and visually polished.
        - For images use https://placehold.co/WIDTHxHEIGHT placeholders.
        - Do NOT include <html>, <head>, <body>, scripts, or markdown code fences.
        - Do NOT add explanations or comments — output ONLY the HTML.
        SYS;

        if ($styleReference !== null && trim($styleReference) !== '') {
            $system .= "\n\nMATCH THE STYLE of the following REFERENCE page exactly: reuse the same"
                ." fonts, font sizes, font weights, colours, spacing, button styles, container widths,"
                ." Tailwind utility-class patterns and overall section structure. Produce NEW content for"
                ." the user's request, but make it look like it belongs to the same site as this reference."
                ." Do NOT copy the reference's text — only its visual style and structure.\n\nREFERENCE:\n"
                .$this->trimReference($styleReference);
        }

        if ($currentHtml !== null && trim($currentHtml) !== '') {
            $system .= "\n\nThe user is editing this EXISTING section. Modify it to satisfy the request and return the FULL updated HTML:\n\n".$currentHtml;
        }

        return $system."\n\nUser request: ".$prompt;
    }

    /** Keep the style reference within a sane token budget for the prompt. */
    private function trimReference(string $html): string
    {
        $html = trim($html);

        return mb_strlen($html) > 12000 ? mb_substr($html, 0, 12000) : $html;
    }

    /** Strip markdown fences / stray prose, keep the HTML. */
    private function cleanHtml(string $raw): string
    {
        $s = trim($raw);
        $s = preg_replace('/^```[a-zA-Z]*\s*/', '', $s);
        $s = preg_replace('/\s*```\s*$/', '', $s);

        // If the model wrapped prose around the HTML, keep from the first tag.
        $pos = stripos($s, '<section');
        if ($pos === false) {
            $pos = strpos($s, '<');
        }
        if ($pos !== false && $pos > 0) {
            $s = substr($s, $pos);
        }

        return trim($s);
    }
}

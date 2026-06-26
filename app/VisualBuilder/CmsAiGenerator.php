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

    public function generate(string $prompt, ?string $currentHtml = null): array
    {
        try {
            $resp = $this->ai->getProvider()->chat($this->buildPrompt($prompt, $currentHtml));
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

    private function buildPrompt(string $prompt, ?string $currentHtml): string
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

        if ($currentHtml !== null && trim($currentHtml) !== '') {
            $system .= "\n\nThe user is editing this EXISTING section. Modify it to satisfy the request and return the FULL updated HTML:\n\n".$currentHtml;
        }

        return $system."\n\nUser request: ".$prompt;
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

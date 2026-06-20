<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Glue between the active AI provider and the PageCompiler.
 *
 *   $agent = app(PageBuilderAgent::class);
 *
 *   // Create a brand-new page from a description
 *   $r = $agent->createPage(
 *       userPrompt:   'Φτιάξε σελίδα για completed villas στην Κρήτη',
 *       templateSlugs: ['wysiwyg', 'hero', 'gallery'],   // skeletons fed to AI
 *   );
 *
 *   // Edit an existing page
 *   $r = $agent->editPage(
 *       pageIdOrSlug: 'build-your-own-villa',
 *       userPrompt:   'Άλλαξε το "Building" σε "Έχτισε" στο πρώτο paragraph',
 *   );
 *
 *   $r => ['ok' => bool, 'page_id' => int, 'slug' => str, 'url' => str, 'warnings' => [...]]
 */
class PageBuilderAgent
{
    public function __construct(protected AIManager $ai) {}

    /**
     * Workflow A: create a new page from a natural-language description.
     */
    public function createPage(string $userPrompt, array $templateSlugs = [], array $context = []): array
    {
        // 1) Pull skeletons for the requested template slugs (or all active ones)
        $skeletons = $this->fetchSkeletons($templateSlugs);

        // 2) Build the system message: load configurable prompt + append skeletons
        $systemPrompt = Setting::get('prompt_page_compiler', config('ai-prompts.page_compiler', ''));
        $skeletonHint = "Available section types and their skeletons (use any combination):\n"
                      .json_encode($skeletons, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $system = trim($systemPrompt."\n\n".$skeletonHint);

        // 3) Ask the AI for a JSON spec
        $response = $this->ai->chatWithTools(
            messages: [
                ['role' => 'user', 'content' => $userPrompt],
            ],
            tools: [],
            system: $system
        );

        $json = $this->cleanJsonFence($response->text ?? '');
        if ($json === '') {
            return ['ok' => false, 'error' => 'AI returned empty response', 'raw' => $response->raw];
        }

        if ($this->wasTruncated($response->stopReason)) {
            return [
                'ok' => false,
                'error' => 'AI response was truncated (output token limit reached). Try a model with a bigger output window, or a simpler prompt.',
                'stop_reason' => $response->stopReason,
                'json' => mb_substr($json, 0, 1000),
            ];
        }

        // 4) Compile the JSON spec into a real Page (with audit revision)
        try {
            return PageCompiler::fromJson($json)
                ->withRevisionMeta(source: 'ai-create', prompt: $userPrompt, userId: Auth::id())
                ->compile()
                + ['ai_response_preview' => mb_substr($json, 0, 400)];
        } catch (\Throwable $e) {
            Log::warning('PageBuilderAgent createPage compile failed', [
                'error' => $e->getMessage(),
                'json' => mb_substr($json, 0, 1000),
            ]);

            return ['ok' => false, 'error' => 'Compile failed: '.$e->getMessage(), 'json' => $json];
        }
    }

    /**
     * Workflow B: edit an existing page in place.
     */
    public function editPage(int|string $pageIdOrSlug, string $userPrompt): array
    {
        // 1) Export the page as the current ground-truth spec
        $exportOutput = $this->runArtisan('page:export', ['identifier' => (string) $pageIdOrSlug]);
        if ($exportOutput === null) {
            return ['ok' => false, 'error' => "Could not export page {$pageIdOrSlug}"];
        }

        $currentSpec = json_decode($exportOutput, true);
        if (! is_array($currentSpec)) {
            return ['ok' => false, 'error' => 'Exported spec is not valid JSON'];
        }

        // 2) System message: editor prompt
        $systemPrompt = Setting::get('prompt_page_editor', config('ai-prompts.page_editor', ''));

        // 3) Conversation: include current spec + instruction
        $response = $this->ai->chatWithTools(
            messages: [
                ['role' => 'user', 'content' => "CURRENT PAGE SPEC:\n".json_encode($currentSpec, JSON_UNESCAPED_UNICODE)],
                ['role' => 'assistant', 'content' => 'Understood. I have the current spec. What edit should I apply?'],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            tools: [],
            system: $systemPrompt
        );

        $json = $this->cleanJsonFence($response->text ?? '');
        if ($json === '') {
            return ['ok' => false, 'error' => 'AI returned empty response'];
        }

        // If the model ran out of output budget, the response is incomplete
        // and json_decode would just see a chopped object. Surface a friendly
        // error so the user knows to switch model or simplify the page.
        if ($this->wasTruncated($response->stopReason)) {
            return [
                'ok' => false,
                'error' => 'AI response was truncated (output token limit reached). The page is too large for this model — try a model with a bigger output window (gemini-2.5-pro), or break the page into smaller pieces.',
                'stop_reason' => $response->stopReason,
                'json' => mb_substr($json, 0, 1000),
            ];
        }

        try {
            $result = PageCompiler::fromJson($json)
                ->withRevisionMeta(source: 'ai-edit', prompt: $userPrompt, userId: Auth::id())
                ->compile()
                + ['ai_response_preview' => mb_substr($json, 0, 400)];

            // Diagnostic: if the compile produced 0 touched sections but warnings,
            // log the raw AI JSON so we can see what shape the AI used and refine
            // the parser. Otherwise we'd be flying blind on "empty page" bug
            // reports.
            if (($result['sections_touched'] ?? 0) === 0 && ! empty($result['warnings'] ?? [])) {
                Log::warning('PageBuilderAgent editPage produced 0 sections', [
                    'page' => $pageIdOrSlug,
                    'prompt' => $userPrompt,
                    'warnings' => $result['warnings'],
                    'ai_json' => mb_substr($json, 0, 3000),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('PageBuilderAgent editPage compile failed', [
                'error' => $e->getMessage(),
                'json' => mb_substr($json, 0, 1000),
            ]);

            return ['ok' => false, 'error' => 'Compile failed: '.$e->getMessage(), 'json' => $json];
        }
    }

    /**
     * Pull JSON skeletons for the given slugs. If $slugs is empty, return ALL
     * active templates' skeletons.
     */
    protected function fetchSkeletons(array $slugs = []): array
    {
        if (empty($slugs)) {
            $output = $this->runArtisan('template:skeleton', ['--all' => true]);

            return json_decode((string) $output, true) ?: [];
        }

        $out = [];
        foreach ($slugs as $slug) {
            $j = $this->runArtisan('template:skeleton', ['slug' => $slug]);
            if ($j) {
                $decoded = json_decode($j, true);
                if (is_array($decoded)) {
                    $out[] = $decoded;
                }
            }
        }

        return $out;
    }

    /**
     * Run an Artisan command and capture its stdout as a string. Returns null
     * on failure.
     */
    protected function runArtisan(string $cmd, array $args = []): ?string
    {
        try {
            $output = new \Symfony\Component\Console\Output\BufferedOutput;
            $exitCode = Artisan::call($cmd, $args, $output);
            if ($exitCode !== 0) {
                Log::warning("Artisan {$cmd} returned exit code {$exitCode}");

                return null;
            }

            return $output->fetch();
        } catch (\Throwable $e) {
            Log::warning("Artisan {$cmd} threw exception", ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Did the provider stop because it hit the output token cap rather than
     * actually finishing? Each vendor uses a different vocabulary:
     *   Gemini   → "MAX_TOKENS"
     *   Anthropic → "max_tokens"
     *   OpenAI   → "length"
     */
    protected function wasTruncated(?string $stopReason): bool
    {
        if (! $stopReason) {
            return false;
        }
        $norm = strtolower($stopReason);

        return in_array($norm, ['max_tokens', 'length', 'output_too_long'], true);
    }

    /**
     * AI responses sometimes wrap JSON in ```json ... ``` markdown fences.
     * Strip them so the compiler can parse the JSON directly.
     */
    protected function cleanJsonFence(string $text): string
    {
        $text = trim($text);
        // ```json … ``` or ``` … ```
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $text, $m)) {
            return trim($m[1]);
        }

        return $text;
    }
}

<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini provider.
 *
 * Native implementation of the 3 critical methods (chat / chatWithTools /
 * testConnection). The remaining AIProviderInterface methods delegate to
 * chat() with appropriate prompts so the provider is fully usable across
 * all existing call sites without re-implementing Claude's 1700-line file.
 *
 * API docs: https://ai.google.dev/api/rest/v1beta/models/generateContent
 */
class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected string $apiBase = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = Setting::get('ai_gemini_api_key', '');
        // `gemini-flash-latest` auto-tracks the current stable flash model so
        // we don't get burned again when Google deprecates a specific version
        // (the previous default `gemini-2.0-flash-exp` was removed from v1beta).
        $this->model = Setting::get('ai_gemini_model', 'gemini-flash-latest');
    }

    /* ─────────────────────────────────────────────────────────────────
     *  NATIVE: chat()
     * ───────────────────────────────────────────────────────────────── */
    public function chat(string $message, array $context = []): AIResponse
    {
        if (empty($this->apiKey)) {
            return AIResponse::error('Missing Gemini API key');
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $this->buildPrompt($message, $context)]],
            ]],
            'generationConfig' => [
                // 32k is well within gemini-2.5/flash's 65k cap. The previous
                // 4k limit truncated PageCompiler edits mid-section because
                // the page+sections+EditorJS blocks easily exceed it.
                'maxOutputTokens' => 32768,
                'temperature' => 0.7,
            ],
        ];

        try {
            $response = Http::timeout(120)
                ->post($this->endpoint('generateContent'), $payload);

            if ($response->failed()) {
                Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);

                return AIResponse::error('Gemini API request failed: '.$response->body());
            }

            $text = $this->extractText($response->json());

            return AIResponse::success($text, ['raw' => $response->json()]);
        } catch (\Throwable $e) {
            Log::error('Gemini chat exception', ['error' => $e->getMessage()]);

            return AIResponse::error('Gemini error: '.$e->getMessage());
        }
    }

    /* ─────────────────────────────────────────────────────────────────
     *  NATIVE: chatWithTools()
     * ───────────────────────────────────────────────────────────────── */
    public function chatWithTools(array $messages, array $tools, ?string $system = null): ToolCallResponse
    {
        if (empty($this->apiKey)) {
            return new ToolCallResponse(
                text: 'ERROR: Missing Gemini API key',
                raw: ['error' => 'missing_api_key']
            );
        }

        $payload = [
            'contents' => $this->normalizeMessagesForGemini($messages),
            'generationConfig' => ['maxOutputTokens' => 32768, 'temperature' => 0.7],
        ];

        if (! empty($tools)) {
            $payload['tools'] = [['functionDeclarations' => $this->convertToolsToGemini($tools)]];
        }

        if ($system !== null && $system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        try {
            $response = Http::timeout(120)
                ->post($this->endpoint('generateContent'), $payload);

            if ($response->failed()) {
                Log::error('Gemini chatWithTools API Error', ['status' => $response->status(), 'body' => $response->body()]);

                return new ToolCallResponse(
                    text: 'ERROR: Gemini API request failed: '.$response->body(),
                    raw: ['status' => $response->status(), 'body' => $response->body()]
                );
            }

            $data = $response->json() ?? [];
            $candidate = $data['candidates'][0] ?? [];
            $parts = $candidate['content']['parts'] ?? [];
            $finishR = $candidate['finishReason'] ?? null;
            $text = '';
            $toolCalls = [];

            foreach ($parts as $i => $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
                if (isset($part['functionCall'])) {
                    $fc = $part['functionCall'];
                    $toolCalls[] = [
                        'id' => 'gemini_call_'.$i.'_'.substr(md5(json_encode($fc)), 0, 8),
                        'name' => $fc['name'] ?? '',
                        'arguments' => $fc['args'] ?? [],
                    ];
                }
            }

            return new ToolCallResponse(
                text: $text,
                toolCalls: $toolCalls,
                stopReason: $finishR,
                raw: $data
            );
        } catch (\Throwable $e) {
            Log::error('Gemini chatWithTools exception', ['error' => $e->getMessage()]);

            return new ToolCallResponse(
                text: 'ERROR: '.$e->getMessage(),
                raw: ['error' => $e->getMessage()]
            );
        }
    }

    /* ─────────────────────────────────────────────────────────────────
     *  NATIVE: testConnection()
     * ───────────────────────────────────────────────────────────────── */
    public function testConnection(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }
        try {
            $response = Http::timeout(30)
                ->post($this->endpoint('generateContent'), [
                    'contents' => [['role' => 'user', 'parts' => [['text' => 'ping']]]],
                    'generationConfig' => ['maxOutputTokens' => 10],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ─────────────────────────────────────────────────────────────────
     *  Delegating methods — each builds an appropriate prompt and uses
     *  chat() / chatWithTools() under the hood. Mirrors the contract of
     *  ClaudeProvider's specialised implementations but with a fraction
     *  of the code.
     * ───────────────────────────────────────────────────────────────── */
    public function detectIntent(string $message): string
    {
        $prompt = 'Classify the intent of the user message into ONE of: '
            .'create_template, modify_template, create_content, modify_frontend. '
            ."Reply with ONLY the label, nothing else.\n\nUser message: ".$message;
        $r = $this->chat($prompt);
        $label = strtolower(trim($r->content ?? 'create_content'));
        $allowed = ['create_template', 'modify_template', 'create_content', 'modify_frontend'];

        return in_array($label, $allowed, true) ? $label : 'create_content';
    }

    public function generateContent(string $templateName, array $fields, string $prompt): array
    {
        $schema = $this->formatFieldsForPrompt($fields);
        $full = "Generate content for template '{$templateName}'.\n\nField schema:\n{$schema}\n\n"
                ."Return ONLY a JSON object whose keys are the field names.\n\nUser prompt: {$prompt}";

        return $this->parseJsonChat($full);
    }

    public function updateContent(string $templateName, array $fields, array $currentData, string $prompt): array
    {
        $schema = $this->formatFieldsForPrompt($fields);
        $current = json_encode($currentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $full = "Update content for template '{$templateName}'.\n\nFields:\n{$schema}\n\n"
                 ."Current data:\n{$current}\n\nReturn ONLY a JSON object with the fields to change.\n\n"
                 ."User instruction: {$prompt}";

        return $this->parseJsonChat($full);
    }

    public function generateTemplate(string $prompt): array
    {
        $full = 'Generate a Template structure as JSON: {"name":"...","description":"...",'
              ."\"fields\":[{\"name\":\"...\",\"label\":\"...\",\"type\":\"text|textarea|wysiwyg|image|email|url\",\"is_required\":bool}]}.\n\n"
              ."User prompt: {$prompt}";

        return $this->parseJsonChat($full);
    }

    public function modifyTemplate(Template $template, string $prompt): array
    {
        $current = $template->toArray();
        $full = "Modify the following template JSON based on the instruction. Return the FULL updated template as JSON.\n\n"
                 .'Current: '.json_encode($current, JSON_UNESCAPED_UNICODE)."\n\n"
                 ."Instruction: {$prompt}";

        return $this->parseJsonChat($full);
    }

    public function generateSEO(array $contentData, string $additionalContext = ''): array
    {
        $text = $this->extractContentText($contentData);
        $full = 'Generate SEO metadata for the following content. Return ONLY JSON with keys: '
              .'meta_title (≤60 chars), meta_description (≤160 chars), meta_keywords (comma list), '
              ."og_title, og_description.\n\n"
              .($additionalContext ? "Context: {$additionalContext}\n\n" : '')
              ."Content:\n".substr($text, 0, 4000);

        $raw = $this->parseJsonChat($full);

        // Shape the response to match what EntryForm / SEO consumers expect:
        //   { success, data: {meta_title, meta_description, ...}, message }
        if (! ($raw['success'] ?? false) || ! isset($raw['meta_title'])) {
            return [
                'success' => false,
                'error' => $raw['error'] ?? 'Invalid SEO response from Gemini',
                'message' => 'Failed to generate SEO metadata',
                'raw' => $raw['raw'] ?? null,
            ];
        }

        // Strip the 'success' flag from the inner JSON since it's not real SEO data.
        $seoData = $raw;
        unset($seoData['success'], $seoData['error'], $seoData['raw']);

        return [
            'success' => true,
            'data' => $seoData,
            'message' => 'SEO metadata generated successfully',
        ];
    }

    public function improveContent(array $contextData, string $userPrompt): array
    {
        $ctx = json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $full = 'Improve the following content based on the user prompt. Preserve HTML markup tags exactly. '
              ."Return ONLY a JSON object containing the fields you changed.\n\n"
              ."Current data: {$ctx}\n\nUser prompt: {$userPrompt}";

        return $this->parseJsonChat($full);
    }

    public function improveCode(string $currentCode, string $userPrompt): array
    {
        $full = 'Improve the following HTML/CSS/JS code based on the user prompt. '
              ."Return ONLY JSON: {\"success\": true|false, \"code\": \"...the improved code...\"}.\n\n"
              ."Current code:\n```\n{$currentCode}\n```\n\nUser prompt: {$userPrompt}";

        return $this->parseJsonChat($full);
    }

    /* ─────────────────────────────────────────────────────────────────
     *  Helpers
     * ───────────────────────────────────────────────────────────────── */
    protected function endpoint(string $action): string
    {
        return "{$this->apiBase}/models/{$this->model}:{$action}?key={$this->apiKey}";
    }

    protected function extractText(array $data): string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $out = '';
        foreach ($parts as $p) {
            if (isset($p['text'])) {
                $out .= $p['text'];
            }
        }

        return trim($out);
    }

    protected function buildPrompt(string $message, array $context): string
    {
        if (empty($context)) {
            return $message;
        }
        $ctx = "Context:\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $ctx."\n\nUser: ".$message;
    }

    protected function formatFieldsForPrompt(array $fields): string
    {
        $lines = [];
        foreach ($fields as $f) {
            $name = $f['name'] ?? '';
            $type = $f['type'] ?? 'text';
            $label = $f['label'] ?? $name;
            $req = ! empty($f['is_required']) ? '(required)' : '';
            $lines[] = "- {$name} [{$type}] — {$label} {$req}";
        }

        return implode("\n", $lines);
    }

    protected function extractContentText(array $contentData): string
    {
        $text = '';
        array_walk_recursive($contentData, function ($v) use (&$text) {
            if (is_string($v)) {
                $text .= strip_tags($v).' ';
            }
        });

        return trim($text);
    }

    /**
     * Send a chat() call, expect a JSON object back, parse it.
     * Handles markdown-wrapped JSON (```json ... ```).
     */
    protected function parseJsonChat(string $prompt): array
    {
        $r = $this->chat($prompt);
        $raw = $r->content ?? '';
        if ($raw === '') {
            return ['success' => false, 'error' => $r->error ?? 'Empty response'];
        }
        // Strip ``` fences
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw)) ?: $raw;
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return ['success' => false, 'error' => 'Invalid JSON from Gemini', 'raw' => $raw];
        }

        return $decoded + ['success' => true];
    }

    /**
     * Convert provider-agnostic message format to Gemini's `contents` array.
     * Gemini uses 'user' / 'model' roles (not 'assistant') and parts arrays.
     */
    protected function normalizeMessagesForGemini(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? 'user') === 'assistant' ? 'model' : ($m['role'] ?? 'user');
            $parts = [];
            if (isset($m['content'])) {
                $c = $m['content'];
                if (is_string($c)) {
                    $parts[] = ['text' => $c];
                } elseif (is_array($c)) {
                    // Anthropic-style content blocks → flatten
                    foreach ($c as $block) {
                        if (is_array($block)) {
                            if (isset($block['text'])) {
                                $parts[] = ['text' => $block['text']];
                            } elseif (isset($block['type']) && $block['type'] === 'tool_result') {
                                $parts[] = [
                                    'functionResponse' => [
                                        'name' => $block['tool_use_id'] ?? 'tool',
                                        'response' => ['content' => $block['content'] ?? ''],
                                    ],
                                ];
                            }
                        } else {
                            $parts[] = ['text' => (string) $block];
                        }
                    }
                }
            }
            if (isset($m['tool_call_id']) && ($m['role'] ?? '') === 'tool') {
                $parts = [[
                    'functionResponse' => [
                        'name' => $m['tool_call_id'],
                        'response' => ['content' => is_string($m['content'] ?? null) ? $m['content'] : json_encode($m['content'])],
                    ],
                ]];
                $role = 'user'; // Gemini tool results go as user-role functionResponse
            }
            if (empty($parts)) {
                $parts = [['text' => '']];
            }
            $out[] = ['role' => $role, 'parts' => $parts];
        }

        return $out;
    }

    /**
     * Convert tool schemas from the registry (Anthropic-shaped) to Gemini's
     * functionDeclarations shape.
     */
    protected function convertToolsToGemini(array $tools): array
    {
        $out = [];
        foreach ($tools as $t) {
            $out[] = [
                'name' => $t['name'] ?? '',
                'description' => $t['description'] ?? '',
                'parameters' => $t['input_schema'] ?? $t['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass],
            ];
        }

        return $out;
    }
}

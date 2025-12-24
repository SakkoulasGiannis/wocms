<?php

namespace App\Services\AI;

use App\Models\Setting;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = Setting::get('ai_chatgpt_api_key', '');
        $this->model = Setting::get('ai_model', 'gpt-4-turbo-preview');
    }

    public function chat(string $message, array $context = []): AIResponse
    {
        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful AI assistant for a CMS. You help users create content, templates, and manage their website. Always respond in the same language as the user\'s question (Greek or English).'
                ]
            ];

            // Add conversation history from context
            if (isset($context['conversation_history'])) {
                foreach ($context['conversation_history'] as $msg) {
                    $messages[] = [
                        'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                        'content' => $msg['message']
                    ];
                }
            }

            // Add current message
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => 4096,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                Log::error('ChatGPT API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return AIResponse::error('Failed to communicate with ChatGPT: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return AIResponse::success($content, ['raw' => $data]);

        } catch (\Exception $e) {
            Log::error('ChatGPT Provider Exception', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
    }

    public function detectIntent(string $message): string
    {
        // Reuse Claude's intent detection logic (already well-tested)
        $provider = new ClaudeProvider();
        return $provider->detectIntent($message);
    }

    public function generateContent(string $templateName, array $fields, string $prompt): array
    {
        try {
            // Build structured prompt for content generation
            $systemPrompt = "You are a content generation AI. Generate content based on the user's request and the template structure provided. Always respond in JSON format with the exact field names specified.";

            $fieldsDescription = "Template fields:\n";
            foreach ($fields as $field) {
                $fieldsDescription .= "- {$field['name']} ({$field['type']}): {$field['label']}\n";
            }

            $userPrompt = "{$prompt}\n\nTemplate: {$templateName}\n{$fieldsDescription}\n\nGenerate content in JSON format with keys matching the field names exactly. For Greek content, use proper Greek characters.";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 4096,
                    'temperature' => 0.8,
                ]);

            if ($response->failed()) {
                throw new \Exception('ChatGPT API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Parse JSON response
            $generatedData = json_decode($content, true);

            if (!$generatedData) {
                throw new \Exception('Failed to parse ChatGPT response as JSON');
            }

            return [
                'success' => true,
                'data' => $generatedData,
                'message' => 'Content generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT Content Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to generate content: ' . $e->getMessage()
            ];
        }
    }

    public function updateContent(string $templateName, array $fields, array $currentData, string $prompt): array
    {
        try {
            $systemPrompt = "You are a content editing AI. Update the existing content based on the user's instructions. Return the complete updated content in JSON format.";

            $currentDataJson = json_encode($currentData, JSON_UNESCAPED_UNICODE);
            $userPrompt = "Current content:\n{$currentDataJson}\n\nInstructions: {$prompt}\n\nReturn the updated content as JSON with the same field structure.";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 4096,
                ]);

            if ($response->failed()) {
                throw new \Exception('ChatGPT API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $updatedData = json_decode($content, true);

            if (!$updatedData) {
                throw new \Exception('Failed to parse ChatGPT response');
            }

            return [
                'success' => true,
                'data' => $updatedData,
                'message' => 'Content updated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT Content Update Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateTemplate(string $prompt): array
    {
        try {
            $systemPrompt = "You are a template generation AI for a CMS. Create template structures with appropriate fields based on user requirements. Return JSON with: name, description, fields (array of {name, type, label, required}).";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 2048,
                ]);

            if ($response->failed()) {
                throw new \Exception('ChatGPT API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $templateData = json_decode($content, true);

            if (!$templateData) {
                throw new \Exception('Failed to parse template structure');
            }

            return [
                'success' => true,
                'data' => $templateData,
                'message' => 'Template structure generated'
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT Template Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function modifyTemplate(Template $template, string $prompt): array
    {
        try {
            $systemPrompt = "You are a template modification AI. Modify existing template structures based on user instructions. Return the complete updated template structure in JSON format.";

            $currentFields = $template->fields->map(fn($f) => [
                'name' => $f->name,
                'type' => $f->type,
                'label' => $f->label,
                'required' => $f->is_required
            ])->toArray();

            $currentStructure = [
                'name' => $template->name,
                'description' => $template->description,
                'fields' => $currentFields
            ];

            $userPrompt = "Current template:\n" . json_encode($currentStructure, JSON_UNESCAPED_UNICODE) . "\n\nModification instructions: {$prompt}\n\nReturn the updated template structure.";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 2048,
                ]);

            if ($response->failed()) {
                throw new \Exception('ChatGPT API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $updatedTemplate = json_decode($content, true);

            if (!$updatedTemplate) {
                throw new \Exception('Failed to parse modified template');
            }

            return [
                'success' => true,
                'data' => $updatedTemplate,
                'message' => 'Template modified successfully'
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT Template Modification Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateSEO(array $contentData, string $additionalContext = ''): array
    {
        try {
            // Extract text content from the data
            $contentText = $this->extractTextFromContent($contentData);

            $systemPrompt = "You are an SEO expert. Generate SEO metadata based on the content provided. Return ONLY valid JSON with these exact keys: meta_title (50-60 chars), meta_description (150-160 chars), meta_keywords (5-10 keywords, comma-separated), og_title, og_description. Use the same language as the content.";

            $userPrompt = "CONTENT TO ANALYZE:\n{$contentText}\n\n{$additionalContext}\n\nGenerate SEO metadata in JSON format.";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 1024,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                throw new \Exception('ChatGPT API Error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            $seoData = json_decode($content, true);

            if (!$seoData || !isset($seoData['meta_title'])) {
                throw new \Exception('Invalid SEO data returned');
            }

            return [
                'success' => true,
                'data' => $seoData,
                'message' => 'SEO metadata generated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT SEO Generation Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to generate SEO metadata'
            ];
        }
    }

    /**
     * Extract text content from entry data for SEO analysis
     */
    protected function extractTextFromContent(array $contentData): string
    {
        $textParts = [];

        foreach ($contentData as $key => $value) {
            // Skip SEO fields, IDs, timestamps, and other meta fields
            if (in_array($key, ['id', 'created_at', 'updated_at', 'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'slug', 'status'])) {
                continue;
            }

            if (is_string($value) && !empty(trim($value))) {
                // Clean HTML content thoroughly
                $cleanValue = $this->cleanHtmlContent($value);

                if (strlen($cleanValue) > 10) { // Only include meaningful content
                    // Limit to 1000 characters per field to avoid token overflow
                    $cleanValue = mb_substr($cleanValue, 0, 1000);
                    $textParts[] = "{$key}: {$cleanValue}";
                }
            }
        }

        return implode("\n\n", $textParts);
    }

    /**
     * Clean HTML content and extract plain text
     */
    protected function cleanHtmlContent(string $html): string
    {
        // Decode HTML entities first
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Add spaces before block-level tags to preserve word boundaries
        $text = preg_replace('/<(div|p|br|h[1-6]|li|tr|td)[^>]*>/i', ' $0', $text);

        // Remove script and style tags with their content
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $text);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $text);

        // Remove all HTML tags
        $text = strip_tags($text);

        // Remove multiple spaces, tabs, and newlines
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim whitespace
        $text = trim($text);

        return $text;
    }

    public function improveContent(array $contextData, string $userPrompt): array
    {
        try {
            // Build field descriptions with types
            $fieldsDescription = "";
            foreach ($contextData['fields_metadata'] as $fieldName => $fieldMeta) {
                $currentValue = $fieldMeta['current_value'] ?? '';

                // Clean HTML content for display
                if (in_array($fieldMeta['type'], ['wysiwyg', 'textarea', 'grapejs'])) {
                    $displayValue = $this->cleanHtmlContent($currentValue);
                    if (strlen($displayValue) > 500) {
                        $displayValue = substr($displayValue, 0, 500) . '...';
                    }
                } else {
                    $displayValue = $currentValue;
                }

                $fieldsDescription .= "\n- {$fieldName} ({$fieldMeta['label']})";
                $fieldsDescription .= "\n  Type: {$fieldMeta['type']}";
                $fieldsDescription .= "\n  Current: " . ($displayValue ?: '(empty)');
            }

            $systemPrompt = <<<SYSTEM
You are a content improvement assistant. Your task is to improve content based on user requests.

IMPORTANT RULES:
1. **Return ONLY the fields that need to be changed** based on the user's request
2. **Preserve field types and formats:**
   - For 'text' fields: Return plain text
   - For 'textarea' fields: Return plain text (can be multi-line)
   - For 'wysiwyg' fields: Return HTML (preserve HTML formatting and structure)
   - For 'grapejs' fields: Return valid JSON with {html: "", css: ""} structure
   - For 'email' fields: Return valid email addresses
   - For 'url' fields: Return valid URLs
   - For 'image' fields: Do not modify (return suggestions in plain text if asked)

3. **GrapeJS Fields:** If the field type is 'grapejs', the current value is JSON like:
   {"html": "<div>...</div>", "css": ".class {...}"}
   You MUST return the same structure. Improve the HTML content but keep the JSON structure intact.

4. **WYSIWYG Fields:** Preserve HTML tags and structure. Only improve the text content.

5. **Understand the request:** If user says "improve title", only return the title field.
   If user says "fix grammar in all fields", return all text fields with corrections.

6. **Response Format:** Return ONLY valid JSON object like:
   {
     "field_name": "improved value",
     "another_field": "improved value"
   }

7. Use the same language as the original content (Greek, English, etc.)

SYSTEM;

            $userMessage = <<<USERMSG
TEMPLATE: {$contextData['template_name']} ({$contextData['template_slug']})
ENTRY ID: {$contextData['entry_id']}

AVAILABLE FIELDS:
{$fieldsDescription}

USER REQUEST:
{$userPrompt}

Please improve the content according to the user's request. Return ONLY the fields that need to be changed in valid JSON format.
USERMSG;

            \Log::info('Calling ChatGPT API for content improvement...', [
                'fields_count' => count($contextData['fields_metadata']),
                'prompt_length' => strlen($userPrompt)
            ]);

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 4096,
                ]);

            if (!$response->successful()) {
                \Log::error('ChatGPT API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'API request failed: ' . $response->body()
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            \Log::info('ChatGPT API response received', [
                'content_length' => strlen($content)
            ]);

            $improvedFields = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                \Log::info('Successfully parsed improved fields', [
                    'improved_count' => count($improvedFields)
                ]);

                return [
                    'success' => true,
                    'data' => $improvedFields
                ];
            }

            // If JSON parsing failed, log and return error
            \Log::error('Failed to parse JSON from ChatGPT response', [
                'content' => $content
            ]);

            return [
                'success' => false,
                'message' => 'Failed to parse AI response as JSON'
            ];

        } catch (\Exception $e) {
            \Log::error('ChatGPT improveContent error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'test']
                    ],
                    'max_tokens' => 5,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ChatGPT Connection Test Failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

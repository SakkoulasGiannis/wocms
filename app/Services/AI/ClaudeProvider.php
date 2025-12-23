<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = Setting::get('ai_claude_api_key', '');
        $this->model = Setting::get('ai_model', 'claude-3-5-sonnet-20241022');
    }

    public function chat(string $message, array $context = []): AIResponse
    {
        try {
            $response = Http::timeout(120) // 2 minutes timeout
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($message, $context)
                        ]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Claude API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return AIResponse::error('Failed to communicate with Claude: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            return AIResponse::success($content, ['raw' => $data]);

        } catch (\Exception $e) {
            Log::error('Claude Provider Exception', ['error' => $e->getMessage()]);
            return AIResponse::error($e->getMessage());
        }
    }

    public function detectIntent(string $message): string
    {
        $message = mb_strtolower($message);

        // Content creation keywords (Greek & English)
        // Match patterns like "γράψε άρθρο", "μπορείς να γράψεις άρθρο", "2 άρθρα", etc.
        if (preg_match('/(γράψε|γράψεις|δημιούργησε|φτιάξε|φτιάξεις|create|write|generate).*(άρθρο|άρθρα|post|posts|article|articles|προϊόν|προϊόντα|product|products|σελίδα|σελίδες|page|pages|content)/u', $message)) {
            return 'create_content';
        }

        // Also detect if message contains numbers + content types (e.g., "2 άρθρα", "3 products")
        if (preg_match('/(\d+|δύο|τρία|τέσσερα|two|three|four).*(άρθρο|άρθρα|post|posts|article|articles|προϊόν|προϊόντα|product|products)/u', $message)) {
            return 'create_content';
        }

        // Content update/modification keywords
        if (preg_match('/(άλλαξε|αλλαξε|ενημέρωσε|ενημερωσε|τροποποίησε|τροποποιησε|διόρθωσε|διορθωσε|change|update|modify|edit|fix).*(τίτλο|τιτλο|περιεχόμενο|περιεχομενο|κείμενο|κειμενο|άρθρο|αρθρο|title|content|article|post)/u', $message)) {
            return 'update_content';
        }

        // Template creation keywords
        if (preg_match('/(φτιάξε|δημιούργησε|create).*(template|πρότυπο|δυνατότητα|functionality)/u', $message)) {
            return 'create_template';
        }

        // Template modification keywords
        if (preg_match('/(πρόσθεσε|αφαίρεσε|τροποποίησε|add|remove|modify).*(πεδίο|field|στοιχείο)/u', $message)) {
            return 'modify_template';
        }

        // Frontend design keywords
        if (preg_match('/(σχεδίασε|άλλαξε|τροποποίησε|design|change|modify).*(σελίδα|page|layout|εμφάνιση)/u', $message)) {
            return 'modify_frontend';
        }

        // Page section creation keywords
        if (preg_match('/(πρόσθεσε|φτιάξε|δημιούργησε|add|create).*(section|τμήμα|slider|hero|about|features|blog|gallery|testimonial|cta|call to action)/u', $message)) {
            return 'create_page_section';
        }

        // Page section modification keywords
        // Also detect "άλλαξε τον τίτλο", "βάλε το κείμενο", etc.
        if (preg_match('/(άλλαξε|αλλαξε|ενημέρωσε|ενημερωσε|τροποποίησε|τροποποιησε|βάλε|βαλε|change|update|modify|set).*(τίτλο|τιτλο|κείμενο|κειμενο|button|overlay|height|ύψος|υψος|στοίχιση|στοιχιση|text|heading|section|τμήμα|slider|hero|about|features|blog|cta|gallery)/u', $message)) {
            return 'modify_page_section';
        }

        // Page section reordering keywords
        if (preg_match('/(μετέφερε|μετακίνησε|βάλε|move|reorder).*(πάνω|κάτω|πρώτο|τελευταίο|μετά|πριν|up|down|top|bottom|before|after|above|below)/u', $message)) {
            return 'reorder_page_section';
        }

        return 'chat';
    }

    public function generateContent(string $templateName, array $fields, string $prompt): array
    {
        // Build tool schema from template fields
        $properties = [];
        $required = [];

        foreach ($fields as $field) {
            $fieldSchema = [
                'type' => $this->mapFieldTypeToJsonSchema($field['type']),
                'description' => $field['description'] ?: $field['label']
            ];

            // Add array items schema for repeater fields
            if ($field['type'] === 'repeater') {
                $fieldSchema['items'] = ['type' => 'string'];
                $fieldSchema['description'] .= ' (provide as array of strings)';
            }

            $properties[$field['name']] = $fieldSchema;

            if ($field['is_required']) {
                $required[] = $field['name'];
            }
        }

        $tool = [
            'name' => 'create_content',
            'description' => "Create content for the {$templateName} template",
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required
            ]
        ];

        // Load prompt from settings or config
        $systemPrompt = Setting::get('prompt_content_generation', config('ai-prompts.content_generation'));

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'tools' => [$tool],
                    'tool_choice' => ['type' => 'tool', 'name' => 'create_content'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Claude API Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'template' => $templateName
                ]);
                throw new \Exception('Claude API failed: ' . $response->body());
            }

            $data = $response->json();

            Log::info('Claude API Response', [
                'template' => $templateName,
                'response_structure' => array_keys($data),
                'content_types' => array_map(fn($c) => $c['type'] ?? 'unknown', $data['content'] ?? [])
            ]);

            // Extract tool use from response
            foreach ($data['content'] ?? [] as $content) {
                if ($content['type'] === 'tool_use' && $content['name'] === 'create_content') {
                    Log::info('Tool use found', ['input_keys' => array_keys($content['input'] ?? [])]);
                    return $this->processGeneratedContent($content['input'], $fields);
                }
            }

            Log::error('No tool use found in Claude response', [
                'response' => $data,
                'template' => $templateName
            ]);
            throw new \Exception('No tool use found in response');

        } catch (\Exception $e) {
            Log::error('Claude Content Generation Error', [
                'error' => $e->getMessage(),
                'template' => $templateName,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Map template field type to JSON schema type
     */
    protected function mapFieldTypeToJsonSchema(string $fieldType): string
    {
        return match($fieldType) {
            'number', 'integer' => 'number',
            'boolean', 'checkbox' => 'boolean',
            'repeater' => 'array',  // Repeater fields should be arrays
            'date', 'datetime', 'time' => 'string',
            default => 'string'
        };
    }

    /**
     * Process generated content and handle special field types
     */
    protected function processGeneratedContent(array $content, array $fields): array
    {
        $processed = [];

        foreach ($fields as $field) {
            $value = $content[$field['name']] ?? null;

            // Skip null values
            if ($value === null) {
                continue;
            }

            // Handle arrays/objects - convert to JSON string with proper encoding
            if (is_array($value) || is_object($value)) {
                $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // Verify the JSON is valid
                if ($jsonValue === false) {
                    Log::warning("Failed to encode JSON for field: {$field['name']}", [
                        'value' => $value,
                        'error' => json_last_error_msg()
                    ]);
                    continue;
                }

                $processed[$field['name']] = $jsonValue;
            } else {
                $processed[$field['name']] = $value;
            }
        }

        return $processed;
    }

    public function updateContent(string $templateName, array $fields, array $currentData, string $prompt): array
    {
        // Build tool schema with only the fields that can be updated
        $properties = [];
        $required = []; // Nothing is required for updates

        foreach ($fields as $field) {
            $fieldSchema = [
                'type' => $this->mapFieldTypeToJsonSchema($field['type']),
                'description' => $field['description'] ?: $field['label']
            ];

            if ($field['type'] === 'repeater') {
                $fieldSchema['items'] = ['type' => 'string'];
            }

            $properties[$field['name']] = $fieldSchema;
        }

        $tool = [
            'name' => 'update_content',
            'description' => "Update fields for the {$templateName} entry. Only include fields that should be changed.",
            'input_schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required // No required fields for updates
            ]
        ];

        // Build context with current data
        $currentDataStr = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYSTEM
You are updating content for a CMS. The user wants to modify an existing entry.

CURRENT DATA:
{$currentDataStr}

USER REQUEST:
{$prompt}

INSTRUCTIONS:
1. Use the update_content tool to return ONLY the fields that should be changed
2. Do NOT include fields that should remain unchanged
3. Keep the same language as the original content unless explicitly asked to change it
4. Preserve formatting and structure when making small changes
5. For arrays (like tags), return the complete new array if changing

Examples:
- "Change title to X" → Return only {'title': 'X'}
- "Add tag Y" → Return {'tags': [existing tags + 'Y']}
- "Fix typo in content" → Return only {'content': 'corrected content'}
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'tools' => [$tool],
                    'tool_choice' => ['type' => 'tool', 'name' => 'update_content'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Claude API Request Failed (update)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'template' => $templateName
                ]);
                throw new \Exception('Claude API failed: ' . $response->body());
            }

            $data = $response->json();

            // Extract tool use from response
            foreach ($data['content'] ?? [] as $content) {
                if ($content['type'] === 'tool_use' && $content['name'] === 'update_content') {
                    return $this->processGeneratedContent($content['input'], $fields);
                }
            }

            throw new \Exception('No tool use found in response');

        } catch (\Exception $e) {
            Log::error('Claude Content Update Error', [
                'error' => $e->getMessage(),
                'template' => $templateName
            ]);
            throw $e;
        }
    }

    public function generateTemplate(string $prompt): array
    {
        // Load prompt from settings or config
        $systemPrompt = Setting::get('prompt_template_generation', config('ai-prompts.template_generation'));

        try {
            $response = Http::timeout(120) // 2 minutes timeout
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                throw new \Exception('Claude API failed: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Extract JSON from response
            $content = trim($content);
            if (str_starts_with($content, '```json')) {
                $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
            }

            return json_decode($content, true) ?? [];

        } catch (\Exception $e) {
            Log::error('Claude Template Generation Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function modifyTemplate(Template $template, string $prompt): array
    {
        // Build current template structure
        $currentFields = $template->fields->map(fn($f) => [
            'name' => $f->name,
            'label' => $f->label,
            'type' => $f->type,
            'description' => $f->description,
            'is_required' => $f->is_required,
        ])->toArray();

        $currentStructure = [
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'fields' => $currentFields
        ];

        $currentStr = json_encode($currentStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYSTEM
You are modifying an existing CMS template.

CURRENT TEMPLATE STRUCTURE:
{$currentStr}

USER REQUEST:
{$prompt}

Return a JSON object with the modifications to make:
{
    "action": "add_fields" | "remove_fields" | "modify_fields",
    "fields_to_add": [...],     // Only for add_fields
    "fields_to_remove": [...],  // Only for remove_fields (field names)
    "fields_to_modify": [...],  // Only for modify_fields
    "reason": "Brief explanation of changes"
}

For add_fields, each field should have: name, label, type, description, is_required, show_in_table
For modify_fields, each field should have: name (to identify) + properties to change

Return ONLY the JSON, no additional text.
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                throw new \Exception('Claude API failed: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Extract JSON from response
            $content = trim($content);
            if (str_starts_with($content, '```json')) {
                $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
            }

            $modifications = json_decode($content, true) ?? [];

            Log::info('Template modification requested', [
                'template' => $template->slug,
                'modifications' => $modifications
            ]);

            return $modifications;

        } catch (\Exception $e) {
            Log::error('Claude Template Modification Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateFrontendModifications(string $prompt, ?string $currentFileContent = null): array
    {
        $contextStr = $currentFileContent ? "CURRENT FILE CONTENT:\n{$currentFileContent}\n\n" : "";

        $systemPrompt = <<<SYSTEM
You are a frontend designer for a CMS. Generate safe, atomic operations to modify Blade templates.

{$contextStr}USER REQUEST:
{$prompt}

Return a JSON array of operations. Each operation has:
{
    "action": "insert_before | insert_after | replace | remove | wrap_with | add_section",
    "target": "The exact string to find (for insert/replace/remove/wrap)",
    "content": "The content to insert (for insert/add_section)",
    "with": "Replacement content (for replace)",
    "wrapper": "HTML tag with attributes (for wrap_with, e.g., '<div class=\"container\">')",
    "name": "Section name (for add_section)",
    "position": "start | end | before:section_name (for add_section)",
    "description": "What this operation does"
}

IMPORTANT RULES:
1. Use EXACT strings from the file for "target" - must match character-for-character
2. Keep existing Blade directives intact unless explicitly asked to change them
3. Use proper indentation (4 spaces)
4. Don't remove important sections (layout, navigation, footer)
5. Add CSS classes using Tailwind where appropriate
6. For lists/loops, use @foreach properly

Example for "Add latest 4 blog posts":
[
    {
        "action": "insert_before",
        "target": "@endsection",
        "content": "    <!-- Latest Blog Posts -->\n    <div class=\"grid grid-cols-4 gap-4\">\n        @foreach(\$latestPosts as \$post)\n            <div class=\"card\">\n                <h3>{{ \$post->title }}</h3>\n            </div>\n        @endforeach\n    </div>",
        "description": "Add blog posts grid before section end"
    }
]

Return ONLY the JSON array, no additional text.
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                throw new \Exception('Claude API failed: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Extract JSON from response
            $content = trim($content);
            if (str_starts_with($content, '```json')) {
                $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
            }

            $operations = json_decode($content, true);

            if (!is_array($operations)) {
                throw new \Exception('Invalid JSON response from AI');
            }

            Log::info('Frontend modifications generated', [
                'operations_count' => count($operations)
            ]);

            return $operations;

        } catch (\Exception $e) {
            Log::error('Claude Frontend Modification Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->chat('Hello');
            return $response->success;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function buildPrompt(string $message, array $context): string
    {
        if (empty($context)) {
            return $message;
        }

        $contextStr = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return <<<PROMPT
Context:
{$contextStr}

User Message:
{$message}
PROMPT;
    }

    protected function formatFieldsForPrompt(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $required = $field['is_required'] ? '(required)' : '(optional)';
            $lines[] = "- {$field['name']} ({$field['type']}) {$required}: {$field['description']}";
        }
        return implode("\n", $lines);
    }

    /**
     * Generate page section content
     */
    public function generatePageSection(string $sectionType, string $prompt): array
    {
        $sectionTypes = \App\Models\PageSection::getSectionTypes();

        if (!isset($sectionTypes[$sectionType])) {
            return ['error' => 'Invalid section type'];
        }

        $typeInfo = $sectionTypes[$sectionType];
        $schema = $typeInfo['schema'];

        // Build system prompt
        $systemPrompt = <<<SYSTEM
You are a content creator for page sections. Generate content for a {$typeInfo['name']} section.

Section schema:
{$this->formatSectionSchema($schema)}

IMPORTANT:
- Return ONLY valid JSON matching the schema
- Use Greek language for content (unless specifically asked otherwise)
- Make content engaging and professional
- Fill all fields based on the user's request
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $jsonContent = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return ['content' => $jsonContent, 'settings' => $typeInfo['default_settings']];
                }
            }

            return ['error' => 'Failed to parse AI response'];

        } catch (\Exception $e) {
            Log::error('Page Section Generation Error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Format section schema for AI prompt
     */
    protected function formatSectionSchema(array $schema): string
    {
        $lines = [];
        foreach ($schema as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                if ($value['type'] === 'array' && isset($value['items'])) {
                    $items = is_array($value['items']) ? json_encode($value['items']) : $value['items'];
                    $lines[] = "- {$key}: array of {$items}";
                } else {
                    $lines[] = "- {$key}: {$value['type']}";
                }
            } else {
                $lines[] = "- {$key}: string";
            }
        }
        return implode("\n", $lines);
    }

    /**
     * Modify page section content/settings (partial update)
     */
    public function modifyPageSection(string $sectionType, array $currentContent, array $currentSettings, string $prompt, string $modificationType = 'content'): array
    {
        $sectionTypes = \App\Models\PageSection::getSectionTypes();

        if (!isset($sectionTypes[$sectionType])) {
            return ['error' => 'Invalid section type'];
        }

        $typeInfo = $sectionTypes[$sectionType];
        $schema = $modificationType === 'settings' ? $this->getSettingsSchema($sectionType) : $typeInfo['schema'];

        // Build system prompt for modification
        $systemPrompt = <<<SYSTEM
You are modifying a {$typeInfo['name']} section.

Current {$modificationType}:
{$this->formatJson($modificationType === 'settings' ? $currentSettings : $currentContent)}

Available {$modificationType} fields:
{$this->formatSectionSchema($schema)}

IMPORTANT:
- Return ONLY the fields that need to be changed
- Use Greek language for content (unless specifically asked otherwise)
- Return valid JSON with ONLY the modified fields
- Do NOT include fields that stay the same
- For arrays, return the complete new array if any item changes

User request: {$prompt}
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $changes = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return ['changes' => $changes];
                }
            }

            return ['error' => 'Failed to parse AI response'];

        } catch (\Exception $e) {
            Log::error('Page Section Modification Error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get settings schema for a section type
     */
    protected function getSettingsSchema(string $sectionType): array
    {
        $schemas = [
            'hero_slider' => [
                'autoplay' => 'boolean',
                'interval' => 'number (milliseconds)',
                'show_arrows' => 'boolean',
                'show_dots' => 'boolean',
            ],
            'hero_simple' => [
                'height' => 'string (screen, 500px, etc)',
                'overlay_opacity' => 'number (0.0 to 1.0)',
                'text_alignment' => 'string (left, center, right)',
            ],
            'about_us' => [
                'layout' => 'string (image_left, image_right)',
                'show_features' => 'boolean',
            ],
            'features_grid' => [
                'columns' => 'number (2, 3, 4)',
                'layout' => 'string (card, simple)',
            ],
            'blog_posts_list' => [
                'count' => 'number',
                'layout' => 'string (grid, list)',
                'columns' => 'number',
                'show_excerpt' => 'boolean',
                'show_date' => 'boolean',
                'show_author' => 'boolean',
            ],
            'testimonials' => [
                'layout' => 'string (carousel, grid)',
                'columns' => 'number',
                'show_rating' => 'boolean',
            ],
            'call_to_action' => [
                'style' => 'string (centered, left)',
                'overlay_opacity' => 'number (0.0 to 1.0)',
            ],
            'stats_counter' => [
                'columns' => 'number',
                'animated' => 'boolean',
            ],
            'gallery' => [
                'columns' => 'number',
                'lightbox' => 'boolean',
            ],
            'contact_form' => [
                'show_map' => 'boolean',
                'show_info' => 'boolean',
            ],
        ];

        return $schemas[$sectionType] ?? [];
    }

    /**
     * Generate custom HTML section with Tailwind CSS
     */
    public function generateCustomHTML(string $prompt): array
    {
        $systemPrompt = <<<SYSTEM
You are an expert web developer creating HTML sections with Tailwind CSS.

IMPORTANT RULES:
- Generate clean, semantic HTML
- Use ONLY Tailwind CSS utility classes (no custom CSS)
- Make it responsive (use md:, lg: prefixes)
- Use Greek language for content (unless specified otherwise)
- Return ONLY the HTML code, no explanations
- Include proper spacing, colors, and typography
- Make it production-ready and beautiful

TAILWIND CLASSES YOU CAN USE:
- Layout: container, mx-auto, px-4, py-16, flex, grid, grid-cols-*
- Spacing: p-*, m-*, space-x-*, space-y-*, gap-*
- Typography: text-*, font-*, leading-*, tracking-*
- Colors: bg-*, text-*, border-*
- Sizing: w-*, h-*, max-w-*, min-h-*
- Flexbox: flex, items-*, justify-*, flex-col, flex-row
- Grid: grid, grid-cols-*, gap-*
- Borders: border, border-*, rounded-*
- Effects: shadow-*, hover:*, transition, opacity-*
- Positioning: relative, absolute, top-*, left-*
- Responsive: sm:, md:, lg:, xl:

Example structure:
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-bold text-center mb-8">Τίτλος</h2>
        <p class="text-lg text-gray-600 text-center max-w-2xl mx-auto">Κείμενο...</p>
    </div>
</section>

User request: {$prompt}
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $systemPrompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return ['error' => 'Failed to generate HTML: ' . $response->body()];
            }

            $data = $response->json();
            $html = $data['content'][0]['text'] ?? '';

            // Clean up the HTML (remove markdown code blocks if present)
            $html = preg_replace('/^```html\s*/m', '', $html);
            $html = preg_replace('/\s*```$/m', '', $html);
            $html = trim($html);

            return [
                'html' => $html,
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('HTML Generation Error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Generate structured JSON that describes HTML with Tailwind classes
     * This is the unified approach for all sections
     */
    public function generateStructuredHTML(string $prompt): array
    {
        // Load prompt from settings or config and inject user request
        $basePrompt = Setting::get('prompt_structured_html', config('ai-prompts.structured_html'));
        $systemPrompt = $basePrompt . "\n\nUser request: {$prompt}\n\nReturn ONLY the JSON structure, no markdown, no explanations.";

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $systemPrompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return ['error' => 'Failed to generate structured HTML: ' . $response->body()];
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Clean up the response (remove markdown code blocks if present)
            $content = preg_replace('/^```json\s*/m', '', $content);
            $content = preg_replace('/\s*```$/m', '', $content);
            $content = trim($content);

            // Parse JSON
            $structure = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse structured HTML JSON', [
                    'content' => $content,
                    'error' => json_last_error_msg()
                ]);
                return ['error' => 'Invalid JSON response from AI: ' . json_last_error_msg()];
            }

            // Validate the structure
            $validation = \App\Helpers\StructuredHTMLRenderer::validate($structure);
            if (!$validation['valid']) {
                return ['error' => 'Invalid HTML structure: ' . ($validation['error'] ?? 'Unknown error')];
            }

            return [
                'structure' => $structure,
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Structured HTML Generation Error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Modify existing structured HTML JSON
     * This keeps the existing structure and only modifies what the user requests
     */
    public function modifyStructuredHTML(array $currentStructure, string $prompt): array
    {
        $currentJson = json_encode($currentStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYSTEM
You are modifying an existing structured HTML JSON. The user wants to make changes to it.

CURRENT STRUCTURE:
{$currentJson}

IMPORTANT RULES:
- Return the COMPLETE modified JSON structure
- Keep everything that the user didn't ask to change
- Only modify the specific parts requested
- Maintain the same structure format: {"type", "classes", "content", "children", "attributes", "icon"}
- Use Greek language for content (unless specified otherwise)
- Return ONLY valid JSON, no markdown, no explanations
- For icons, use only these names: users, check, star, heart, lightning, fire, shield, rocket, globe, chart, cog, phone, mail, location, clock, calendar, document, camera, gift, briefcase, code, cube, chip

User request: {$prompt}

Return the COMPLETE modified JSON structure:
SYSTEM;

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post($this->apiUrl, [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $systemPrompt
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return ['error' => 'Failed to modify structured HTML: ' . $response->body()];
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Clean up the response (remove markdown code blocks if present)
            $content = preg_replace('/^```json\s*/m', '', $content);
            $content = preg_replace('/\s*```$/m', '', $content);
            $content = trim($content);

            // Parse JSON
            $structure = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse modified structured HTML JSON', [
                    'content' => $content,
                    'error' => json_last_error_msg()
                ]);
                return ['error' => 'Invalid JSON response from AI: ' . json_last_error_msg()];
            }

            // Validate the structure
            $validation = \App\Helpers\StructuredHTMLRenderer::validate($structure);
            if (!$validation['valid']) {
                return ['error' => 'Invalid HTML structure: ' . ($validation['error'] ?? 'Unknown error')];
            }

            return [
                'structure' => $structure,
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('Structured HTML Modification Error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Format JSON for AI prompt
     */
    protected function formatJson($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

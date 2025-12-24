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

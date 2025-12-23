<?php

namespace App\Services\AI;

use App\Models\Setting;

class ChatGPTProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = Setting::get('ai_chatgpt_api_key', '');
        $this->model = Setting::get('ai_model', 'gpt-4-turbo-preview');
    }

    public function chat(string $message, array $context = []): AIResponse
    {
        // TODO: Implement ChatGPT API integration
        return AIResponse::error('ChatGPT provider not yet implemented');
    }

    public function detectIntent(string $message): string
    {
        // Reuse Claude's intent detection logic
        $provider = new ClaudeProvider();
        return $provider->detectIntent($message);
    }

    public function generateContent(string $templateName, array $fields, string $prompt): array
    {
        // TODO: Implement ChatGPT content generation
        throw new \Exception('ChatGPT provider not yet implemented');
    }

    public function generateTemplate(string $prompt): array
    {
        // TODO: Implement ChatGPT template generation
        throw new \Exception('ChatGPT provider not yet implemented');
    }

    public function testConnection(): bool
    {
        return false;
    }
}

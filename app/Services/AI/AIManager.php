<?php

namespace App\Services\AI;

use App\Models\Setting;

class AIManager
{
    protected AIProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    protected function resolveProvider(): AIProviderInterface
    {
        $providerName = Setting::get('ai_provider', 'claude');

        return match($providerName) {
            'claude' => new ClaudeProvider(),
            'chatgpt' => new ChatGPTProvider(),
            'ollama' => new OllamaProvider(),
            default => new ClaudeProvider(),
        };
    }

    public function chat(string $message, array $context = []): AIResponse
    {
        return $this->provider->chat($message, $context);
    }

    public function detectIntent(string $message): string
    {
        return $this->provider->detectIntent($message);
    }

    public function generateContent(string $templateName, array $fields, string $prompt): array
    {
        return $this->provider->generateContent($templateName, $fields, $prompt);
    }

    public function generateTemplate(string $prompt): array
    {
        return $this->provider->generateTemplate($prompt);
    }

    public function testConnection(): bool
    {
        return $this->provider->testConnection();
    }

    public function getProvider(): AIProviderInterface
    {
        return $this->provider;
    }
}

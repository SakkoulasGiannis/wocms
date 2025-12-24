<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Send a chat message and get a response
     */
    public function chat(string $message, array $context = []): AIResponse;

    /**
     * Detect the intent of a user message
     * Returns: 'create_template' | 'modify_template' | 'create_content' | 'modify_frontend'
     */
    public function detectIntent(string $message): string;

    /**
     * Generate content for a specific template
     */
    public function generateContent(string $templateName, array $fields, string $prompt): array;

    /**
     * Update existing content based on user instructions
     */
    public function updateContent(string $templateName, array $fields, array $currentData, string $prompt): array;

    /**
     * Generate a template structure from natural language
     */
    public function generateTemplate(string $prompt): array;

    /**
     * Modify an existing template (add/remove/edit fields)
     */
    public function modifyTemplate(Template $template, string $prompt): array;

    /**
     * Generate SEO metadata based on content
     * Returns array with: meta_title, meta_description, meta_keywords, og_title, og_description
     */
    public function generateSEO(array $contentData, string $additionalContext = ''): array;

    /**
     * Improve content based on user prompt
     * Returns array with only the fields that were improved
     * Handles different field types: text, textarea, wysiwyg, grapejs, image, email, url
     */
    public function improveContent(array $contextData, string $userPrompt): array;

    /**
     * Test the API connection
     */
    public function testConnection(): bool;
}

<?php

namespace App\Services;

use App\Services\AI\AIManager;

class AISectionGenerator
{
    protected AIManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIManager;
    }

    /**
     * Generate section template from description or HTML
     */
    public function generateSection(string $input, string $type = 'description'): array
    {
        if ($type === 'html') {
            return $this->convertHtmlToTailwind($input);
        } else {
            return $this->generateFromDescription($input);
        }
    }

    /**
     * Convert Bootstrap/plain HTML to Tailwind CSS 4.1
     */
    protected function convertHtmlToTailwind(string $html): array
    {
        $prompt = $this->getSystemPrompt()."\n\n".$this->buildConversionPrompt($html);

        $response = $this->aiManager->getProvider()->chat($prompt);

        if (! $response->success) {
            throw new \Exception('AI API request failed: '.$response->error);
        }

        return $this->parseAIResponse($response->content);
    }

    /**
     * Generate section from natural language description
     */
    protected function generateFromDescription(string $description): array
    {
        $prompt = $this->getSystemPrompt()."\n\n".
                  "Create a beautiful, modern section component based on this description:\n\n{$description}\n\n".
                  'The section should be production-ready and follow modern web design principles.';

        $response = $this->aiManager->getProvider()->chat($prompt);

        if (! $response->success) {
            throw new \Exception('AI API request failed: '.$response->error);
        }

        return $this->parseAIResponse($response->content);
    }

    /**
     * System prompt for AI
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert frontend developer specializing in Tailwind CSS 4.1 and creating reusable section components.

Your task is to convert HTML sections to modern Tailwind CSS 4.1 or create new sections from descriptions.

REQUIREMENTS:
1. Use Tailwind CSS 4.1 utility classes ONLY (no custom CSS unless absolutely necessary)
2. Make the section responsive (mobile-first approach)
3. Identify dynamic content and replace with placeholders: {{variable_name}}
4. Use semantic HTML5 tags
5. Ensure accessibility (ARIA labels, alt text, etc.)
6. Follow modern design principles (proper spacing, typography, colors)
7. Use Tailwind's default color palette (gray, blue, green, red, etc.)

DYNAMIC FIELDS:
- Replace any text content that should be editable with {{field_name}}
- Common fields: {{heading}}, {{subheading}}, {{description}}, {{button_text}}, {{button_url}}, {{image}}, {{icon}}
- For lists/repeating items, use {{items}} and structure them properly

OUTPUT FORMAT (JSON):
{
  "name": "Section Name",
  "description": "Brief description of the section",
  "category": "hero|content|features|cta|testimonials|stats|pricing|faq|team|contact|gallery|custom",
  "html_template": "Complete HTML with Tailwind classes and {{placeholders}}",
  "fields": [
    {
      "name": "field_name",
      "label": "Field Label",
      "type": "text|textarea|wysiwyg|image|url|repeater",
      "default_value": "Default value if any",
      "is_required": true|false,
      "order": 0
    }
  ]
}

EXAMPLE:
Input: "A hero section with heading, subheading, CTA button and background image"
Output:
{
  "name": "Hero with CTA",
  "description": "Modern hero section with call-to-action",
  "category": "hero",
  "html_template": "<section class=\"relative bg-gradient-to-r from-blue-600 to-purple-600 py-20 px-4 sm:px-6 lg:px-8\">\n  <div class=\"max-w-7xl mx-auto text-center\">\n    <h1 class=\"text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6\">{{heading}}</h1>\n    <p class=\"text-xl sm:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto\">{{subheading}}</p>\n    <a href=\"{{button_url}}\" class=\"inline-block bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-50 transition\">{{button_text}}</a>\n  </div>\n</section>",
  "fields": [
    {"name": "heading", "label": "Heading", "type": "text", "default_value": "Welcome to Our Platform", "is_required": true, "order": 0},
    {"name": "subheading", "label": "Subheading", "type": "textarea", "default_value": "Build amazing things with our tools", "is_required": false, "order": 1},
    {"name": "button_text", "label": "Button Text", "type": "text", "default_value": "Get Started", "is_required": true, "order": 2},
    {"name": "button_url", "label": "Button URL", "type": "url", "default_value": "#", "is_required": true, "order": 3}
  ]
}

Return ONLY valid JSON, no markdown formatting, no explanations.
PROMPT;
    }

    /**
     * Build conversion prompt for HTML input
     */
    protected function buildConversionPrompt(string $html): string
    {
        return <<<PROMPT
Convert this HTML to modern Tailwind CSS 4.1:

```html
{$html}
```

Instructions:
1. Replace all Bootstrap classes with Tailwind CSS 4.1 equivalents
2. Remove any inline styles, replace with Tailwind utilities
3. Identify dynamic content and use {{placeholder}} syntax
4. Make it responsive and accessible
5. Use modern Tailwind patterns (flexbox, grid, spacing scale, etc.)
6. Return JSON output as specified in system prompt

PROMPT;
    }

    /**
     * Parse AI response and extract JSON
     */
    protected function parseAIResponse(string $content): array
    {
        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI response as JSON: '.json_last_error_msg());
        }

        // Validate required fields
        $required = ['name', 'html_template', 'fields'];
        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new \Exception("AI response missing required field: {$field}");
            }
        }

        // Set defaults
        $data['description'] = $data['description'] ?? '';
        $data['category'] = $data['category'] ?? 'custom';

        return $data;
    }

    /**
     * Extract fields from HTML template
     */
    public function extractFields(string $html): array
    {
        $fields = [];
        preg_match_all('/\{\{(\w+)\}\}/', $html, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $uniqueFields = array_unique($matches[1]);
        $order = 0;

        foreach ($uniqueFields as $fieldName) {
            $fields[] = [
                'name' => $fieldName,
                'label' => ucwords(str_replace('_', ' ', $fieldName)),
                'type' => $this->guessFieldType($fieldName),
                'default_value' => '',
                'is_required' => true,
                'order' => $order++,
            ];
        }

        return $fields;
    }

    /**
     * Guess field type from field name
     */
    protected function guessFieldType(string $fieldName): string
    {
        $fieldName = strtolower($fieldName);

        if (str_contains($fieldName, 'image') || str_contains($fieldName, 'photo') || str_contains($fieldName, 'picture')) {
            return 'image';
        }

        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'link') || str_contains($fieldName, 'href')) {
            return 'url';
        }

        if (str_contains($fieldName, 'email')) {
            return 'email';
        }

        if (str_contains($fieldName, 'description') || str_contains($fieldName, 'content') || str_contains($fieldName, 'body')) {
            return 'wysiwyg';
        }

        if (str_contains($fieldName, 'text') || str_contains($fieldName, 'paragraph')) {
            return 'textarea';
        }

        if (str_contains($fieldName, 'items') || str_contains($fieldName, 'list')) {
            return 'repeater';
        }

        return 'text';
    }
}

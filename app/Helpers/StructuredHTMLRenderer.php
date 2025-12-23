<?php

namespace App\Helpers;

class StructuredHTMLRenderer
{
    /**
     * Render a structured JSON tree to HTML
     *
     * @param array $node The JSON node with structure: {type, classes?, attributes?, content?, children?, icon?}
     * @return string The rendered HTML
     */
    public static function render(array $node): string
    {
        if (!isset($node['type'])) {
            return '';
        }

        $type = $node['type'];
        $classes = $node['classes'] ?? '';
        $attributes = $node['attributes'] ?? [];
        $content = $node['content'] ?? '';
        $children = $node['children'] ?? [];
        $icon = $node['icon'] ?? null;

        // Build opening tag
        $html = "<{$type}";

        // Add classes
        if (!empty($classes)) {
            $html .= " class=\"{$classes}\"";
        }

        // Add other attributes
        foreach ($attributes as $key => $value) {
            $html .= " {$key}=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"";
        }

        $html .= ">";

        // Add icon if specified
        if (!empty($icon)) {
            $iconClasses = 'w-6 h-6'; // Default icon size
            // Extract icon size from classes if specified
            if (strpos($classes, 'w-') !== false) {
                preg_match('/w-(\d+)/', $classes, $matches);
                if (isset($matches[1])) {
                    $iconClasses = "w-{$matches[1]} h-{$matches[1]}";
                }
            }
            $html .= IconHelper::get($icon, $iconClasses);
        }

        // Add content
        if (!empty($content)) {
            $html .= htmlspecialchars($content, ENT_NOQUOTES);
        }

        // Recursively render children
        if (!empty($children)) {
            foreach ($children as $child) {
                $html .= self::render($child);
            }
        }

        // Close tag
        $html .= "</{$type}>";

        return $html;
    }

    /**
     * Validate a structured JSON tree
     *
     * @param array $node The JSON node to validate
     * @return array {valid: bool, error?: string}
     */
    public static function validate(array $node): array
    {
        // Check required 'type' field
        if (!isset($node['type'])) {
            return ['valid' => false, 'error' => 'Missing required field: type'];
        }

        // Validate HTML element type
        $validElements = [
            'div', 'section', 'article', 'header', 'footer', 'nav', 'aside', 'main',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'p', 'span', 'a', 'button',
            'ul', 'ol', 'li',
            'img', 'svg', 'path',
            'form', 'input', 'textarea', 'select', 'option', 'label',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'strong', 'em', 'small', 'mark',
        ];

        if (!in_array($node['type'], $validElements)) {
            return ['valid' => false, 'error' => "Invalid element type: {$node['type']}"];
        }

        // Validate children recursively
        if (isset($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as $child) {
                if (!is_array($child)) {
                    return ['valid' => false, 'error' => 'Child must be an array'];
                }
                $childValidation = self::validate($child);
                if (!$childValidation['valid']) {
                    return $childValidation;
                }
            }
        }

        return ['valid' => true];
    }
}

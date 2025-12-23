<?php

namespace App\Services;

use App\Models\Template;

class ModelMethodsGenerator
{
    /**
     * Generate methods code for a model based on template fields
     */
    public function generateMethods(Template $template): string
    {
        $methods = [];

        foreach ($template->fields as $field) {
            $fieldMethods = $this->generateMethodsForField($field);
            if ($fieldMethods) {
                $methods[] = $fieldMethods;
            }
        }

        return !empty($methods) ? "\n" . implode("\n\n", $methods) : '';
    }

    /**
     * Generate methods for a specific field type
     */
    protected function generateMethodsForField($field): ?string
    {
        $methods = [];

        switch ($field->type) {
            case 'image':
                $methods[] = $this->generateImageMethods($field);
                break;

            case 'gallery':
                $methods[] = $this->generateGalleryMethods($field);
                break;

            case 'relation':
                $methods[] = $this->generateRelationMethod($field);
                break;

            case 'boolean':
            case 'checkbox':
                $methods[] = $this->generateBooleanMethods($field);
                break;

            case 'date':
            case 'datetime':
                $methods[] = $this->generateDateMethods($field);
                break;

            case 'repeater':
            case 'json':
                $methods[] = $this->generateJsonMethods($field);
                break;
        }

        return !empty($methods) ? implode("\n\n", array_filter($methods)) : null;
    }

    /**
     * Generate image field methods
     */
    protected function generateImageMethods($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));

        return <<<PHP
    /**
     * Get the URL for {$field->label}
     */
    public function get{$methodName}Url(): ?string
    {
        if (!\$this->{$fieldName}) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var(\$this->{$fieldName}, FILTER_VALIDATE_URL)) {
            return \$this->{$fieldName};
        }

        // Otherwise, assume it's in storage
        return asset('storage/' . \$this->{$fieldName});
    }

    /**
     * Check if {$field->label} exists
     */
    public function has{$methodName}(): bool
    {
        return !empty(\$this->{$fieldName});
    }
PHP;
    }

    /**
     * Generate gallery field methods
     */
    protected function generateGalleryMethods($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));

        return <<<PHP
    /**
     * Get URLs for {$field->label}
     */
    public function get{$methodName}Urls(): array
    {
        if (!is_array(\$this->{$fieldName})) {
            return [];
        }

        return array_map(function (\$image) {
            if (filter_var(\$image, FILTER_VALIDATE_URL)) {
                return \$image;
            }
            return asset('storage/' . \$image);
        }, \$this->{$fieldName});
    }

    /**
     * Get first image from {$field->label}
     */
    public function getFirst{$methodName}Url(): ?string
    {
        \$urls = \$this->get{$methodName}Urls();
        return !empty(\$urls) ? \$urls[0] : null;
    }

    /**
     * Check if {$field->label} has images
     */
    public function has{$methodName}(): bool
    {
        return is_array(\$this->{$fieldName}) && count(\$this->{$fieldName}) > 0;
    }

    /**
     * Get count of images in {$field->label}
     */
    public function get{$methodName}Count(): int
    {
        return is_array(\$this->{$fieldName}) ? count(\$this->{$fieldName}) : 0;
    }
PHP;
    }

    /**
     * Generate relation field method
     */
    protected function generateRelationMethod($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::camel(\Illuminate\Support\Str::singular($fieldName)); // Keep camel for relation methods

        // Try to guess the related model from field name
        $relatedModel = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));

        return <<<PHP
    /**
     * Get the {$field->label} relation
     */
    public function {$methodName}()
    {
        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$fieldName}');
    }
PHP;
    }

    /**
     * Generate boolean field methods
     */
    protected function generateBooleanMethods($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));
        $toggleMethodName = 'toggle' . $methodName;

        return <<<PHP
    /**
     * Check if {$field->label} is true
     */
    public function is{$methodName}(): bool
    {
        return (bool) \$this->{$fieldName};
    }

    /**
     * Toggle {$field->label}
     */
    public function {$toggleMethodName}(): bool
    {
        \$this->{$fieldName} = !\$this->{$fieldName};
        \$this->save();
        return \$this->{$fieldName};
    }
PHP;
    }

    /**
     * Generate date field methods
     */
    protected function generateDateMethods($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));

        return <<<PHP
    /**
     * Get formatted {$field->label}
     */
    public function get{$methodName}Formatted(\$format = 'Y-m-d'): ?string
    {
        return \$this->{$fieldName} ? \$this->{$fieldName}->format(\$format) : null;
    }

    /**
     * Get {$field->label} for humans
     */
    public function get{$methodName}ForHumans(): ?string
    {
        return \$this->{$fieldName} ? \$this->{$fieldName}->diffForHumans() : null;
    }
PHP;
    }

    /**
     * Generate JSON/Repeater field methods
     */
    protected function generateJsonMethods($field): string
    {
        $fieldName = $field->name;
        $methodName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($fieldName));

        return <<<PHP
    /**
     * Add item to {$field->label}
     */
    public function add{$methodName}(\$item): void
    {
        \$items = \$this->{$fieldName} ?? [];
        \$items[] = \$item;
        \$this->{$fieldName} = \$items;
        \$this->save();
    }

    /**
     * Remove item from {$field->label} by index
     */
    public function remove{$methodName}(\$index): void
    {
        \$items = \$this->{$fieldName} ?? [];
        if (isset(\$items[\$index])) {
            unset(\$items[\$index]);
            \$this->{$fieldName} = array_values(\$items);
            \$this->save();
        }
    }

    /**
     * Get count of items in {$field->label}
     */
    public function get{$methodName}Count(): int
    {
        return is_array(\$this->{$fieldName}) ? count(\$this->{$fieldName}) : 0;
    }
PHP;
    }
}

<?php

namespace Modules\PageBuilder\Models;

use Illuminate\Database\Eloquent\Model;

class SectionTemplateField extends Model
{
    protected $fillable = [
        'section_template_id',
        'name',
        'label',
        'type',
        'description',
        'placeholder',
        'default_value',
        'is_required',
        'order',
        'options',
        'validation_rules',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
        'options' => 'array',
        'validation_rules' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the section template that owns this field
     */
    public function sectionTemplate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SectionTemplate::class);
    }

    /**
     * Get available field types
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => [
                'label' => 'Text Input',
                'icon' => 'T',
                'description' => 'Single line text input',
            ],
            'textarea' => [
                'label' => 'Textarea',
                'icon' => 'T',
                'description' => 'Multi-line text input',
            ],
            'wysiwyg' => [
                'label' => 'WYSIWYG Editor',
                'icon' => '✎',
                'description' => 'Rich text editor with formatting',
            ],
            'image' => [
                'label' => 'Image',
                'icon' => '🖼',
                'description' => 'Single image upload',
            ],
            'gallery' => [
                'label' => 'Gallery',
                'icon' => '🖼',
                'description' => 'Multiple images upload',
            ],
            'url' => [
                'label' => 'URL',
                'icon' => '🔗',
                'description' => 'URL/Link input',
            ],
            'email' => [
                'label' => 'Email',
                'icon' => '@',
                'description' => 'Email address input',
            ],
            'number' => [
                'label' => 'Number',
                'icon' => '#',
                'description' => 'Numeric input',
            ],
            'select' => [
                'label' => 'Select Dropdown',
                'icon' => '▼',
                'description' => 'Dropdown select field',
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'icon' => '☑',
                'description' => 'Single checkbox (true/false)',
            ],
            'radio' => [
                'label' => 'Radio Buttons',
                'icon' => '◉',
                'description' => 'Radio button group',
            ],
            'color' => [
                'label' => 'Color Picker',
                'icon' => '🎨',
                'description' => 'Color selection',
            ],
            'date' => [
                'label' => 'Date',
                'icon' => '📅',
                'description' => 'Date picker',
            ],
            'repeater' => [
                'label' => 'Repeater',
                'icon' => '⟳',
                'description' => 'Repeating group of fields',
            ],
        ];
    }
}

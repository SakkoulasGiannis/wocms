<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TemplateTableGenerator;

class TemplateField extends Model
{
    protected $fillable = [
        'template_id',
        'name',
        'label',
        'type',
        'description',
        'validation_rules',
        'default_value',
        'settings',
        'order',
        'is_required',
        'show_in_table',
        'column_position',
        'adapts_to_render_mode',
        'is_searchable',
        'is_filterable',
        'is_url_identifier',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'settings' => 'array',
        'is_required' => 'boolean',
        'show_in_table' => 'boolean',
        'adapts_to_render_mode' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_url_identifier' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // When a field is created or deleted, sync the template table
        static::created(function ($field) {
            if ($field->template && $field->template->table_name) {
                $tableGenerator = new TemplateTableGenerator();
                $tableGenerator->createTableAndModel($field->template->fresh());
            }
        });

        static::deleted(function ($field) {
            if ($field->template && $field->template->table_name) {
                $tableGenerator = new TemplateTableGenerator();
                $tableGenerator->createTableAndModel($field->template->fresh());
            }
        });

        static::updated(function ($field) {
            // Only sync if field name or type changed
            if ($field->isDirty(['name', 'type']) && $field->template && $field->template->table_name) {
                $tableGenerator = new TemplateTableGenerator();
                $tableGenerator->createTableAndModel($field->template->fresh());
            }
        });
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text',
            'number' => 'Number',
            'email' => 'Email',
            'url' => 'URL',
            'tel' => 'Telephone',
            'date' => 'Date',
            'datetime' => 'Date & Time',
            'textarea' => 'Textarea',
            'wysiwyg' => 'WYSIWYG Editor',
            'grapejs' => 'GrapeJS Page Builder',
            'markdown' => 'Markdown',
            'code' => 'Code Editor',
            'select' => 'Select Dropdown',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio Buttons',
            'image' => 'Image Upload',
            'gallery' => 'Image Gallery',
            'file' => 'File Upload',
            'color' => 'Color Picker',
            'icon_picker' => 'Icon Picker',
            'repeater' => 'Repeater',
            'group' => 'Group',
            'relation' => 'Relation',
            'json' => 'JSON Editor',
        ];
    }

    public function getValidationRulesString(): string
    {
        if (!$this->validation_rules) {
            return '';
        }

        return implode('|', $this->validation_rules);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'name',
        'label',
        'type',
        'placeholder',
        'default_value',
        'help_text',
        'is_required',
        'validation_rules',
        'options',
        'order',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'settings' => 'array',
        'validation_rules' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get validation rules for this field
     */
    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        }

        // Add type-specific validation
        switch ($this->type) {
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'file':
                $rules[] = 'file';
                if ($this->settings['max_size'] ?? null) {
                    $rules[] = 'max:' . $this->settings['max_size'];
                }
                if ($this->settings['mime_types'] ?? null) {
                    $rules[] = 'mimes:' . $this->settings['mime_types'];
                }
                break;
        }

        // Add custom validation rules
        if ($this->validation_rules && is_array($this->validation_rules)) {
            $rules = array_merge($rules, $this->validation_rules);
        }

        return $rules;
    }
}

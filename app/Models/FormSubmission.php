<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_id',
        'data',
        'ip_address',
        'user_agent',
        'referer',
        'is_read',
        'is_spam',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_spam' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Mark submission as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark submission as spam
     */
    public function markAsSpam()
    {
        $this->update(['is_spam' => true]);
    }

    /**
     * Get formatted submission data
     */
    public function getFormattedData(): array
    {
        $formatted = [];

        foreach ($this->data as $key => $value) {
            $field = $this->form->fields()->where('name', $key)->first();
            $label = $field ? $field->label : ucfirst(str_replace('_', ' ', $key));

            $formatted[] = [
                'label' => $label,
                'value' => is_array($value) ? implode(', ', $value) : $value,
            ];
        }

        return $formatted;
    }
}

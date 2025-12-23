<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Form extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'submit_button_text',
        'success_message',
        'redirect_url',
        'send_email_notification',
        'notification_recipients',
        'notification_subject',
        'notification_message',
        'send_auto_reply',
        'auto_reply_email_field',
        'auto_reply_subject',
        'auto_reply_message',
        'store_submissions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_email_notification' => 'boolean',
        'send_auto_reply' => 'boolean',
        'store_submissions' => 'boolean',
        'notification_recipients' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->name);
            }
        });
    }

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class)->latest();
    }

    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text Input',
            'email' => 'Email',
            'tel' => 'Phone Number',
            'number' => 'Number',
            'url' => 'URL',
            'textarea' => 'Textarea',
            'select' => 'Select Dropdown',
            'radio' => 'Radio Buttons',
            'checkbox' => 'Checkboxes',
            'file' => 'File Upload',
            'date' => 'Date',
            'time' => 'Time',
            'datetime' => 'Date & Time',
            'hidden' => 'Hidden Field',
        ];
    }

    /**
     * Get recipients as array
     */
    public function getRecipientsArray(): array
    {
        if (is_array($this->notification_recipients)) {
            return $this->notification_recipients;
        }

        if (is_string($this->notification_recipients)) {
            return array_map('trim', explode(',', $this->notification_recipients));
        }

        return [];
    }
}

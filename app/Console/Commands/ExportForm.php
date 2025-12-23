<?php

namespace App\Console\Commands;

use App\Models\Form;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'form:export {slug : The form slug} {--output= : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a form to JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slug = $this->argument('slug');
        $outputPath = $this->option('output') ?: base_path("{$slug}.json");

        // Find form
        $form = Form::with('fields')->where('slug', $slug)->first();

        if (!$form) {
            $this->error("Form not found: {$slug}");
            return 1;
        }

        // Prepare export data
        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'form' => [
                'name' => $form->name,
                'slug' => $form->slug,
                'description' => $form->description,
                'is_active' => $form->is_active,
                'submit_button_text' => $form->submit_button_text,
                'success_message' => $form->success_message,
                'redirect_url' => $form->redirect_url,
                'send_email_notification' => $form->send_email_notification,
                'notification_recipients' => $form->notification_recipients,
                'notification_subject' => $form->notification_subject,
                'notification_message' => $form->notification_message,
                'send_auto_reply' => $form->send_auto_reply,
                'auto_reply_email_field' => $form->auto_reply_email_field,
                'auto_reply_subject' => $form->auto_reply_subject,
                'auto_reply_message' => $form->auto_reply_message,
                'store_submissions' => $form->store_submissions,
                'fields' => $form->fields->map(function ($field) {
                    return [
                        'name' => $field->name,
                        'label' => $field->label,
                        'type' => $field->type,
                        'placeholder' => $field->placeholder,
                        'default_value' => $field->default_value,
                        'help_text' => $field->help_text,
                        'is_required' => $field->is_required,
                        'validation_rules' => $field->validation_rules,
                        'options' => $field->options,
                        'order' => $field->order,
                        'settings' => $field->settings,
                    ];
                })->toArray(),
            ],
        ];

        // Write to file
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputPath, $json);

        $this->info("✅ Successfully exported form to: {$outputPath}");
        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $form->name],
                ['Slug', $form->slug],
                ['Fields', $form->fields->count()],
                ['File Size', $this->formatBytes(strlen($json))],
            ]
        );

        return 0;
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}

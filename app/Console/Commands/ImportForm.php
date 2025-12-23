<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportForm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'form:import {file : The JSON file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a form from JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Read and parse JSON
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());
            return 1;
        }

        // Validate structure
        if (!isset($data['form'])) {
            $this->error('Invalid form JSON structure. Missing "form" key.');
            return 1;
        }

        $formData = $data['form'];
        $fields = $formData['fields'] ?? [];
        unset($formData['fields']);

        // Start transaction
        DB::beginTransaction();

        try {
            // Check if form already exists
            $existingForm = Form::where('slug', $formData['slug'])->first();

            if ($existingForm) {
                if (!$this->confirm("Form '{$formData['slug']}' already exists. Update it?", true)) {
                    $this->info('Import cancelled.');
                    return 0;
                }

                // Delete existing fields
                $existingForm->fields()->delete();

                // Update form
                $existingForm->update($formData);
                $form = $existingForm;

                $this->info("Updated form: {$form->name}");
            } else {
                // Create new form
                $form = Form::create($formData);
                $this->info("Created form: {$form->name}");
            }

            // Import fields
            foreach ($fields as $fieldData) {
                $fieldData['form_id'] = $form->id;
                FormField::create($fieldData);
            }

            DB::commit();

            $this->info("✅ Successfully imported form with " . count($fields) . " fields!");
            $this->newLine();
            $this->table(
                ['Property', 'Value'],
                [
                    ['Name', $form->name],
                    ['Slug', $form->slug],
                    ['Fields', count($fields)],
                    ['Active', $form->is_active ? 'Yes' : 'No'],
                    ['Email Notifications', $form->send_email_notification ? 'Yes' : 'No'],
                    ['Auto-Reply', $form->send_auto_reply ? 'Yes' : 'No'],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;
use Illuminate\Support\Facades\File;

class ImportTemplates extends Command
{
    protected $signature = 'template:import {file : JSON file path} {--update : Update existing templates}';
    protected $description = 'Import templates from JSON file';

    public function handle()
    {
        $this->info('📥 Template Import Tool');
        $this->newLine();

        $filePath = $this->argument('file');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        try {
            $jsonContent = File::get($filePath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON file: ' . json_last_error_msg());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error reading file: ' . $e->getMessage());
            return 1;
        }

        if (!isset($data['templates']) || !is_array($data['templates'])) {
            $this->error('Invalid template export format. Missing "templates" array.');
            return 1;
        }

        $this->info("Found {$data['version']} format");
        $this->info("Exported at: {$data['exported_at']}");
        $this->info("Templates to import: " . count($data['templates']));
        $this->newLine();

        $this->table(
            ['Template', 'Slug', 'Fields'],
            collect($data['templates'])->map(function ($template) {
                return [
                    $template['name'],
                    $template['slug'],
                    count($template['fields'] ?? []),
                ];
            })->toArray()
        );

        if (!$this->confirm('Do you want to proceed with the import?', true)) {
            $this->warn('Import cancelled.');
            return 0;
        }

        $this->newLine();

        $updateMode = $this->option('update');
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        try {
            $templateMap = [];

            foreach ($data['templates'] as $templateData) {
                $slug = $templateData['slug'];
                $existingTemplate = Template::where('slug', $slug)->first();

                if ($existingTemplate && !$updateMode) {
                    $this->warn("⊘ Skipped: {$templateData['name']} (already exists)");
                    $skipped++;
                    $templateMap[$slug] = $existingTemplate;
                    continue;
                }

                if ($existingTemplate && $updateMode) {
                    $this->updateTemplate($existingTemplate, $templateData);
                    $this->info("↻ Updated: {$templateData['name']}");
                    $updated++;
                    $templateMap[$slug] = $existingTemplate;
                } else {
                    $template = $this->createTemplate($templateData);
                    $this->info("✓ Created: {$templateData['name']}");
                    $imported++;
                    $templateMap[$slug] = $template;
                }
            }

            foreach ($data['templates'] as $templateData) {
                if (isset($templateData['parent_slug']) && $templateData['parent_slug']) {
                    $template = $templateMap[$templateData['slug']];
                    $parentTemplate = $templateMap[$templateData['parent_slug']] ?? null;

                    if ($parentTemplate) {
                        $template->parent_id = $parentTemplate->id;
                        $template->save();
                    }
                }
            }

            $this->newLine();
            $this->info('✅ Import completed successfully!');
            $this->info("Created: {$imported}");
            $this->info("Updated: {$updated}");
            $this->info("Skipped: {$skipped}");

            return 0;

        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    protected function createTemplate($data)
    {
        $template = Template::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'has_physical_file' => $data['has_physical_file'] ?? false,
            'requires_database' => $data['requires_database'] ?? true,
            'file_path' => $data['file_path'] ?? null,
            'html_content' => $data['html_content'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_public' => $data['is_public'] ?? true,
            'show_in_menu' => $data['show_in_menu'] ?? false,
            'menu_label' => $data['menu_label'] ?? null,
            'menu_icon' => $data['menu_icon'] ?? null,
            'menu_order' => $data['menu_order'] ?? 0,
            'allow_children' => $data['allow_children'] ?? true,
            'allow_new_pages' => $data['allow_new_pages'] ?? true,
            'allowed_parent_templates' => $data['allowed_parent_templates'] ?? null,
            'allowed_child_templates' => $data['allowed_child_templates'] ?? null,
            'use_custom_access' => $data['use_custom_access'] ?? false,
            'allowed_roles' => $data['allowed_roles'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $fieldData) {
                TemplateField::create([
                    'template_id' => $template->id,
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label'],
                    'type' => $fieldData['type'],
                    'description' => $fieldData['description'] ?? null,
                    'is_required' => $fieldData['is_required'] ?? false,
                    'show_in_table' => $fieldData['show_in_table'] ?? true,
                    'default_value' => $fieldData['default_value'] ?? null,
                    'validation_rules' => $fieldData['validation_rules'] ?? null,
                    'settings' => $fieldData['settings'] ?? null,
                    'order' => $fieldData['order'] ?? 0,
                ]);
            }
        }

        $template->refresh();
        $template->load('fields');

        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->createTableAndModel($template);

        if ($template->has_physical_file) {
            $template->createPhysicalFile();
        }

        return $template;
    }

    protected function updateTemplate($template, $data)
    {
        $template->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'has_physical_file' => $data['has_physical_file'] ?? false,
            'requires_database' => $data['requires_database'] ?? true,
            'file_path' => $data['file_path'] ?? null,
            'html_content' => $data['html_content'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_public' => $data['is_public'] ?? true,
            'show_in_menu' => $data['show_in_menu'] ?? false,
            'menu_label' => $data['menu_label'] ?? null,
            'menu_icon' => $data['menu_icon'] ?? null,
            'menu_order' => $data['menu_order'] ?? 0,
            'allow_children' => $data['allow_children'] ?? true,
            'allow_new_pages' => $data['allow_new_pages'] ?? true,
            'allowed_parent_templates' => $data['allowed_parent_templates'] ?? null,
            'allowed_child_templates' => $data['allowed_child_templates'] ?? null,
            'use_custom_access' => $data['use_custom_access'] ?? false,
            'allowed_roles' => $data['allowed_roles'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        if (isset($data['fields']) && is_array($data['fields'])) {
            $fieldNames = collect($data['fields'])->pluck('name');
            $template->fields()->whereNotIn('name', $fieldNames)->delete();

            foreach ($data['fields'] as $fieldData) {
                TemplateField::updateOrCreate(
                    [
                        'template_id' => $template->id,
                        'name' => $fieldData['name'],
                    ],
                    [
                        'label' => $fieldData['label'],
                        'type' => $fieldData['type'],
                        'description' => $fieldData['description'] ?? null,
                        'is_required' => $fieldData['is_required'] ?? false,
                        'show_in_table' => $fieldData['show_in_table'] ?? true,
                        'default_value' => $fieldData['default_value'] ?? null,
                        'validation_rules' => $fieldData['validation_rules'] ?? null,
                        'settings' => $fieldData['settings'] ?? null,
                        'order' => $fieldData['order'] ?? 0,
                    ]
                );
            }
        }

        $template->refresh();
        $template->load('fields');

        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->createTableAndModel($template);

        if ($template->has_physical_file) {
            $template->createPhysicalFile();
        }

        return $template;
    }
}

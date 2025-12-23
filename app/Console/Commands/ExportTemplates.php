<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Template;
use Illuminate\Support\Facades\File;

class ExportTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:export {--file= : Output file path} {--template= : Specific template slug to export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export templates to JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📤 Template Export Tool');
        $this->newLine();

        // Get templates to export
        $templateSlug = $this->option('template');

        if ($templateSlug) {
            $templates = Template::where('slug', $templateSlug)
                ->with(['fields' => function($query) {
                    $query->orderBy('order');
                }])
                ->get();

            if ($templates->isEmpty()) {
                $this->error("Template '{$templateSlug}' not found!");
                return 1;
            }
        } else {
            $templates = Template::with(['fields' => function($query) {
                $query->orderBy('order');
            }])
            ->orderBy('name')
            ->get();
        }

        if ($templates->isEmpty()) {
            $this->error('No templates found to export!');
            return 1;
        }

        // Prepare export data
        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'templates' => []
        ];

        foreach ($templates as $template) {
            $templateData = [
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'has_physical_file' => $template->has_physical_file,
                'requires_database' => $template->requires_database,
                'file_path' => $template->file_path,
                'html_content' => $template->html_content,
                'is_active' => $template->is_active,
                'is_public' => $template->is_public,
                'show_in_menu' => $template->show_in_menu,
                'menu_label' => $template->menu_label,
                'menu_icon' => $template->menu_icon,
                'menu_order' => $template->menu_order,
                'allow_children' => $template->allow_children,
                'allow_new_pages' => $template->allow_new_pages,
                'allowed_parent_templates' => $template->allowed_parent_templates,
                'allowed_child_templates' => $template->allowed_child_templates,
                'use_custom_access' => $template->use_custom_access,
                'allowed_roles' => $template->allowed_roles,
                'icon' => $template->icon,
                'parent_slug' => $template->parent ? $template->parent->slug : null,
                'fields' => []
            ];

            foreach ($template->fields as $field) {
                $templateData['fields'][] = [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'description' => $field->description,
                    'is_required' => $field->is_required,
                    'show_in_table' => $field->show_in_table,
                    'default_value' => $field->default_value,
                    'validation_rules' => $field->validation_rules,
                    'settings' => $field->settings,
                    'order' => $field->order,
                ];
            }

            $exportData['templates'][] = $templateData;
        }

        // Determine output file
        $outputFile = $this->option('file');

        if (!$outputFile) {
            $defaultName = $templateSlug
                ? "template-{$templateSlug}-" . date('Y-m-d') . '.json'
                : 'templates-' . date('Y-m-d') . '.json';

            $outputFile = storage_path('app/exports/' . $defaultName);
        }

        // Create directory if it doesn't exist
        $directory = dirname($outputFile);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write JSON file
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($outputFile, $json);

        $this->newLine();
        $this->info("✅ Exported {$templates->count()} template(s)");
        $this->info("File: {$outputFile}");
        $this->newLine();

        // Show summary
        $this->table(
            ['Template', 'Fields', 'Public', 'Database'],
            $templates->map(function ($template) {
                return [
                    $template->name,
                    $template->fields->count(),
                    $template->is_public ? 'Yes' : 'No',
                    $template->requires_database ? 'Yes' : 'No',
                ];
            })->toArray()
        );

        return 0;
    }
}

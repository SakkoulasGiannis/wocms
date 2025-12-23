<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Template;
use App\Models\ContentNode;
use App\Services\PageCssGenerator;
use Illuminate\Support\Str;

class ImportContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:import {--template= : Template slug} {--id= : Entry ID to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or update content for a template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📝 Content Import Tool');
        $this->newLine();

        // Get template
        $templateSlug = $this->option('template');

        if (!$templateSlug) {
            $templates = Template::where('is_active', true)
                ->where('requires_database', true)
                ->orderBy('name')
                ->get();

            if ($templates->isEmpty()) {
                $this->error('No templates found!');
                return 1;
            }

            $templateChoices = $templates->pluck('name', 'slug')->toArray();
            $templateSlug = $this->choice('Select template:', $templateChoices);
        }

        $template = Template::where('slug', $templateSlug)
            ->where('is_active', true)
            ->with(['fields' => function($query) {
                $query->orderBy('order');
            }])
            ->first();

        if (!$template) {
            $this->error("Template '{$templateSlug}' not found!");
            return 1;
        }

        // Check if model exists
        if (!$template->model_class || !class_exists("App\\Models\\{$template->model_class}")) {
            $this->error('Template model not found. Please save the template again to generate the model.');
            return 1;
        }

        $this->info("Template: {$template->name}");
        $this->newLine();

        // Check if updating existing entry
        $entryId = $this->option('id');
        $modelClass = "App\\Models\\{$template->model_class}";
        $entry = null;

        if ($entryId) {
            $entry = $modelClass::find($entryId);
            if (!$entry) {
                $this->error("Entry with ID {$entryId} not found!");
                return 1;
            }
            $entryTitle = $entry->title ?? ($entry->name ?? 'ID ' . $entryId);
            $this->warn("Updating existing entry: {$entryTitle}");
            $this->newLine();
        } else {
            $this->info("Creating new entry");
            $this->newLine();
        }

        // Collect field values
        $fieldValues = [];

        foreach ($template->fields as $field) {
            // Skip auto-generated CSS fields
            if (Str::endsWith($field->name, '_css')) {
                continue;
            }

            $currentValue = $entry ? ($entry->{$field->name} ?? '') : '';

            $this->info("Field: {$field->label} ({$field->name})");
            if ($field->description) {
                $this->comment("  {$field->description}");
            }

            if ($currentValue) {
                $this->comment("  Current: " . Str::limit($currentValue, 100));
            }

            switch ($field->type) {
                case 'textarea':
                case 'wysiwyg':
                case 'markdown':
                case 'code':
                case 'grapejs':
                    $value = $this->anticipate(
                        $field->is_required ? "{$field->label} *" : $field->label,
                        [$currentValue],
                        $currentValue
                    );
                    break;

                case 'checkbox':
                case 'boolean':
                    $value = $this->confirm($field->label, $currentValue ? true : false);
                    break;

                case 'number':
                case 'integer':
                    $value = $this->ask(
                        $field->is_required ? "{$field->label} *" : $field->label,
                        $currentValue
                    );
                    break;

                default:
                    $value = $this->ask(
                        $field->is_required ? "{$field->label} *" : $field->label,
                        $currentValue
                    );
            }

            $fieldValues[$field->name] = $value;
            $this->newLine();
        }

        // Confirm before saving
        $this->table(
            ['Field', 'Value'],
            collect($fieldValues)->map(function ($value, $key) {
                return [
                    $key,
                    is_bool($value) ? ($value ? 'Yes' : 'No') : Str::limit($value, 50)
                ];
            })->toArray()
        );

        if (!$this->confirm('Save this content?', true)) {
            $this->warn('Cancelled.');
            return 0;
        }

        try {
            if ($entry) {
                // Update existing
                $entry->update($fieldValues);
                $this->info("✅ Entry updated successfully!");
            } else {
                // Create new
                $entry = $modelClass::create($fieldValues);
                $this->info("✅ Entry created successfully! ID: {$entry->id}");

                // Create ContentNode if template is public
                if ($template->is_public) {
                    $this->createContentNode($template, $entry);
                }
            }

            // Generate CSS files for GrapeJS fields
            $this->generateCssFiles($template, $entry);

            $this->newLine();
            $this->info("Entry ID: {$entry->id}");

            if ($template->is_public) {
                $contentNode = ContentNode::where('content_type', get_class($entry))
                    ->where('content_id', $entry->id)
                    ->first();

                if ($contentNode) {
                    $this->info("URL: /{$contentNode->getPath()}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    protected function createContentNode($template, $entry)
    {
        $title = $entry->title ?? ($entry->name ?? 'Untitled');
        $slug = $entry->slug ?? Str::slug($title);

        $node = new ContentNode([
            'template_id' => $template->id,
            'content_type' => get_class($entry),
            'content_id' => $entry->id,
            'title' => $title,
            'slug' => $slug,
            'is_published' => true,
        ]);

        // Auto-detect parent node if template has parent
        if ($template->parent_id) {
            $parentNode = ContentNode::where('template_id', $template->parent_id)
                ->where('is_published', true)
                ->first();

            if ($parentNode) {
                $node->parent_id = $parentNode->id;
            }
        }

        $node->save();

        $this->info("ContentNode created with slug: {$slug}");
    }

    protected function generateCssFiles($template, $entry)
    {
        $cssGenerator = new PageCssGenerator();
        $slug = $entry->slug ?? $entry->id;

        foreach ($template->fields as $field) {
            if ($field->type === 'grapejs') {
                $cssFieldName = $field->name . '_css';

                if (isset($entry->$cssFieldName) && !empty($entry->$cssFieldName)) {
                    $cssGenerator->generateCssFile($slug, $entry->$cssFieldName);
                    $this->comment("CSS file generated for {$field->name}");
                }
            }
        }
    }
}

<?php

namespace App\Services;

use App\Models\Template;

class TemplateExporter
{
    /**
     * Export all templates with their fields.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportAll(): array
    {
        return Template::with('fields')
            ->orderBy('tree_level')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => $this->templateToArray($t))
            ->toArray();
    }

    /**
     * Export specific template by slug.
     */
    public function exportBySlug(string $slug): ?array
    {
        $template = Template::with('fields')->where('slug', $slug)->first();

        return $template ? $this->templateToArray($template) : null;
    }

    /**
     * Import templates from JSON array.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(array $data, bool $overwrite = false): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($data as $templateData) {
            $existing = Template::where('slug', $templateData['slug'])->first();

            if ($existing && ! $overwrite) {
                $stats['skipped']++;

                continue;
            }

            $fields = $templateData['fields'] ?? [];
            unset($templateData['fields'], $templateData['id'], $templateData['parent_slug']);

            // Resolve parent by slug if provided
            if (! empty($templateData['parent_slug'])) {
                $parent = Template::where('slug', $templateData['parent_slug'])->first();
                $templateData['parent_id'] = $parent?->id;
                unset($templateData['parent_slug']);
            }

            if ($existing) {
                $existing->update($templateData);
                $template = $existing;
                $stats['updated']++;
            } else {
                $template = Template::create($templateData);
                $stats['created']++;
            }

            if (! empty($fields)) {
                $this->syncFields($template, $fields);
            }
        }

        return $stats;
    }

    /**
     * Convert a template to export array.
     *
     * @return array<string, mixed>
     */
    protected function templateToArray(Template $template): array
    {
        return [
            'name' => $template->name,
            'slug' => $template->slug,
            'parent_slug' => $template->parent?->slug,
            'use_slug_prefix' => $template->use_slug_prefix,
            'url_segment' => $template->url_segment,
            'table_name' => $template->table_name,
            'model_class' => $template->model_class,
            'description' => $template->description,
            'has_physical_file' => $template->has_physical_file,
            'requires_database' => $template->requires_database,
            'has_seo' => $template->has_seo,
            'file_path' => $template->file_path,
            'render_mode' => $template->render_mode,
            'is_active' => $template->is_active,
            'is_public' => $template->is_public,
            'is_system' => $template->is_system,
            'allow_children' => $template->allow_children,
            'allow_new_pages' => $template->allow_new_pages,
            'fields' => $template->fields->map(function ($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'description' => $field->description,
                    'validation_rules' => $field->validation_rules,
                    'default_value' => $field->default_value,
                    'adapts_to_render_mode' => $field->adapts_to_render_mode,
                    'settings' => $field->settings,
                    'order' => $field->order,
                    'is_required' => $field->is_required,
                    'is_searchable' => $field->is_searchable,
                    'is_filterable' => $field->is_filterable,
                    'is_url_identifier' => $field->is_url_identifier,
                    'show_in_table' => $field->show_in_table,
                    'column_position' => $field->column_position,
                ];
            })->toArray(),
        ];
    }

    /**
     * Sync fields for a template from import data.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncFields(Template $template, array $fields): void
    {
        $template->fields()->delete();

        foreach ($fields as $order => $fieldData) {
            unset($fieldData['id']);
            $fieldData['order'] = $fieldData['order'] ?? $order;
            $template->fields()->create($fieldData);
        }
    }
}

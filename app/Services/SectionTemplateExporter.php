<?php

namespace App\Services;

use App\Models\SectionTemplate;

class SectionTemplateExporter
{
    /**
     * Export all section templates with their fields to JSON.
     *
     * @return array<string, mixed>
     */
    public function exportAll(): array
    {
        return SectionTemplate::with('fields')
            ->get()
            ->map(fn ($t) => $this->templateToArray($t))
            ->toArray();
    }

    /**
     * Export specific template by slug.
     */
    public function exportBySlug(string $slug): ?array
    {
        $template = SectionTemplate::with('fields')->where('slug', $slug)->first();

        return $template ? $this->templateToArray($template) : null;
    }

    /**
     * Export multiple templates by slugs.
     *
     * @param  array<string>  $slugs
     * @return array<int, array<string, mixed>>
     */
    public function exportBySlugs(array $slugs): array
    {
        return SectionTemplate::with('fields')
            ->whereIn('slug', $slugs)
            ->get()
            ->map(fn ($t) => $this->templateToArray($t))
            ->toArray();
    }

    /**
     * Import section templates from JSON array.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(array $data, bool $overwrite = false): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($data as $templateData) {
            $existing = SectionTemplate::where('slug', $templateData['slug'])->first();

            if ($existing && ! $overwrite) {
                $stats['skipped']++;

                continue;
            }

            $fields = $templateData['fields'] ?? [];
            unset($templateData['fields'], $templateData['id']);

            if ($existing) {
                $existing->update($templateData);
                $template = $existing;
                $stats['updated']++;
            } else {
                $template = SectionTemplate::create($templateData);
                $stats['created']++;
            }

            // Sync fields
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
    protected function templateToArray(SectionTemplate $template): array
    {
        return [
            'name' => $template->name,
            'slug' => $template->slug,
            'category' => $template->category,
            'description' => $template->description,
            'html_template' => $template->html_template,
            'is_active' => $template->is_active,
            'is_system' => $template->is_system,
            'order' => $template->order,
            'fields' => $template->fields->map(function ($field) {
                return [
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'default_value' => $field->default_value,
                    'is_required' => $field->is_required,
                    'options' => $field->options,
                    'order' => $field->order,
                    'settings' => $field->settings,
                ];
            })->toArray(),
        ];
    }

    /**
     * Sync fields for a template from import data.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function syncFields(SectionTemplate $template, array $fields): void
    {
        // Delete existing fields
        $template->fields()->delete();

        // Create new fields
        foreach ($fields as $order => $fieldData) {
            unset($fieldData['id']);
            $fieldData['order'] = $fieldData['order'] ?? $order;
            $template->fields()->create($fieldData);
        }
    }
}

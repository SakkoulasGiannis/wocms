<?php

namespace App\Traits;

use App\Models\PageSection;
use App\Models\SectionTemplate;

trait HasSections
{
    /**
     * Get all sections for this model
     */
    public function sections()
    {
        return $this->morphMany(PageSection::class, 'sectionable')
            ->orderBy('order');
    }

    /**
     * Get only active sections
     */
    public function activeSections()
    {
        return $this->sections()->where('is_active', true);
    }

    /**
     * Add a new section by template (ID or slug) or legacy section type string.
     */
    public function addSection(string|int $templateOrType, array $content = [], array $settings = [], ?string $name = null, int $order = 0): PageSection
    {
        $template = null;
        $sectionType = null;

        // Try to resolve as SectionTemplate
        if (is_int($templateOrType)) {
            $template = SectionTemplate::findOrFail($templateOrType);
        } else {
            // Try slug first (e.g. 'hero-simple')
            $template = SectionTemplate::where('slug', $templateOrType)->first();

            // Try converting underscore type to slug (e.g. 'hero_simple' → 'hero-simple')
            if (! $template) {
                $slug = str_replace('_', '-', $templateOrType);
                $template = SectionTemplate::where('slug', $slug)->first();
            }
        }

        if ($template) {
            $sectionType = str_replace('-', '_', $template->slug);

            // Build default content from template fields if no content provided
            if (empty($content)) {
                foreach ($template->fields as $field) {
                    $content[$field->name] = $field->default_value ?? '';
                }
            }

            $defaultSettings = $template->default_settings ?? [];
        } else {
            // Fallback to legacy getSectionTypes()
            $sectionTypes = PageSection::getSectionTypes();

            if (! isset($sectionTypes[$templateOrType])) {
                throw new \InvalidArgumentException("Invalid section type or template: {$templateOrType}");
            }

            $sectionType = $templateOrType;
            $content = empty($content) ? ($sectionTypes[$sectionType]['default_content'] ?? []) : $content;
            $defaultSettings = $sectionTypes[$sectionType]['default_settings'] ?? [];
            $name = $name ?? $sectionTypes[$sectionType]['name'];
        }

        // If no order specified, add at the end
        if ($order === 0) {
            $order = ($this->sections()->max('order') ?? 0) + 1;
        }

        return $this->sections()->create([
            'section_template_id' => $template?->id,
            'section_type' => $sectionType,
            'name' => $name ?? ($template?->name ?? ucfirst(str_replace('_', ' ', $sectionType))),
            'content' => $content,
            'settings' => array_merge($defaultSettings, $settings),
            'order' => $order,
            'is_active' => true,
        ]);
    }

    /**
     * Reorder sections
     */
    public function reorderSections(array $sectionIds): void
    {
        foreach ($sectionIds as $order => $sectionId) {
            $this->sections()->where('id', $sectionId)->update(['order' => $order]);
        }
    }

    /**
     * Delete a section
     */
    public function deleteSection(int $sectionId): bool
    {
        return $this->sections()->where('id', $sectionId)->delete();
    }

    /**
     * Toggle section visibility
     */
    public function toggleSection(int $sectionId): bool
    {
        $section = $this->sections()->findOrFail($sectionId);
        $section->is_active = ! $section->is_active;

        return $section->save();
    }
}

<?php

namespace App\Traits;

use App\Models\PageSection;

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
     * Add a new section
     */
    public function addSection(string $sectionType, array $content = [], array $settings = [], ?string $name = null, int $order = 0): PageSection
    {
        $sectionTypes = PageSection::getSectionTypes();

        if (!isset($sectionTypes[$sectionType])) {
            throw new \InvalidArgumentException("Invalid section type: {$sectionType}");
        }

        // Get default content and settings
        $defaultContent = $sectionTypes[$sectionType]['default_content'] ?? [];
        $defaultSettings = $sectionTypes[$sectionType]['default_settings'] ?? [];

        // If no order specified, add at the end
        if ($order === 0) {
            $order = $this->sections()->max('order') + 1;
        }

        return $this->sections()->create([
            'section_type' => $sectionType,
            'name' => $name ?? $sectionTypes[$sectionType]['name'],
            'content' => empty($content) ? $defaultContent : $content,
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
        $section->is_active = !$section->is_active;
        return $section->save();
    }
}

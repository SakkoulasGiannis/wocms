<?php

namespace App\Services;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use Illuminate\Support\Facades\DB;

class PageImporter
{
    public function __construct(protected PageSerializer $serializer) {}

    /**
     * Import a page layout array into an existing ContentNode.
     * Updates sections to match the given layout exactly.
     *
     * @param  array<string, mixed>  $layout
     */
    public function import(ContentNode $node, array $layout): void
    {
        DB::transaction(function () use ($node, $layout) {
            $this->importSections($node, $layout['sections'] ?? []);
            $this->syncPageLayout($node);
        });
    }

    /**
     * Sync the page_layout JSON on the ContentNode from the current DB sections.
     */
    public function syncPageLayout(ContentNode $node): void
    {
        $layout = $this->serializer->serialize($node);

        $node->page_layout = $layout;
        $node->saveQuietly();
    }

    /**
     * Import sections from layout array into DB.
     * Creates new sections, updates existing ones, deletes removed ones.
     *
     * @param  array<int, array<string, mixed>>  $sectionsData
     */
    protected function importSections(ContentNode $node, array $sectionsData): void
    {
        $existingIds = PageSection::where('sectionable_type', $node->content_type)
            ->where('sectionable_id', $node->content_id)
            ->pluck('id')
            ->all();

        $importedIds = [];

        foreach ($sectionsData as $index => $sectionData) {
            $template = SectionTemplate::where('slug', $sectionData['template'] ?? '')->first()
                ?? ($sectionData['template_id'] ? SectionTemplate::find($sectionData['template_id']) : null);

            if (! $template) {
                continue;
            }

            if (! empty($sectionData['id']) && in_array($sectionData['id'], $existingIds)) {
                // Update existing
                $section = PageSection::find($sectionData['id']);
                $section->update([
                    'name' => $sectionData['name'] ?? $section->name,
                    'order' => $sectionData['order'] ?? $index + 1,
                    'is_active' => $sectionData['active'] ?? true,
                    'content' => $sectionData['content'] ?? [],
                    'settings' => $sectionData['settings'] ?? [],
                ]);
                $importedIds[] = $section->id;
            } else {
                // Create new
                $section = PageSection::create([
                    'sectionable_type' => $node->content_type,
                    'sectionable_id' => $node->content_id,
                    'section_template_id' => $template->id,
                    'section_type' => $template->slug,
                    'name' => $sectionData['name'] ?? $template->name,
                    'order' => $sectionData['order'] ?? $index + 1,
                    'is_active' => $sectionData['active'] ?? true,
                    'content' => $sectionData['content'] ?? [],
                    'settings' => $sectionData['settings'] ?? [],
                ]);
                $importedIds[] = $section->id;
            }
        }

        // Delete sections not present in the import
        $toDelete = array_diff($existingIds, $importedIds);
        if (! empty($toDelete)) {
            PageSection::whereIn('id', $toDelete)->delete();
        }
    }
}

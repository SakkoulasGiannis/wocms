<?php

namespace Modules\PageBuilder\Livewire\Admin\PageSections;

use App\Models\ContentNode;
use Livewire\Component;
use Modules\PageBuilder\Models\PageSection;

class PageSelector extends Component
{
    public $pages = [];

    public function mount(): void
    {
        $this->loadPages();
    }

    public function loadPages(): void
    {
        // Get distinct sectionable entities from page_sections
        $sectionPages = PageSection::select('sectionable_type', 'sectionable_id')
            ->whereNotNull('sectionable_type')
            ->whereNotNull('sectionable_id')
            ->groupBy('sectionable_type', 'sectionable_id')
            ->get()
            ->map(function ($row) {
                $sectionsCount = PageSection::where('sectionable_type', $row->sectionable_type)
                    ->where('sectionable_id', $row->sectionable_id)
                    ->count();

                $title = $this->getEntityTitle($row->sectionable_type, $row->sectionable_id);
                $shortType = class_basename($row->sectionable_type);

                return [
                    'sectionable_type' => $row->sectionable_type,
                    'sectionable_id' => $row->sectionable_id,
                    'short_type' => $shortType,
                    'title' => $title,
                    'sections_count' => $sectionsCount,
                    'is_home' => $shortType === 'Home',
                ];
            });

        $this->pages = $sectionPages->sortBy(function ($page) {
            return $page['is_home'] ? '0' : '1'.$page['title'];
        })->values()->toArray();
    }

    protected function getEntityTitle(string $type, int $id): string
    {
        // Try to resolve the actual model
        if (class_exists($type)) {
            $model = $type::find($id);
            if ($model) {
                return $model->title ?? $model->name ?? class_basename($type).' #'.$id;
            }
        }

        // Fallback: check ContentNode
        $node = ContentNode::where('content_type', $type)
            ->where('content_id', $id)
            ->first();

        if ($node) {
            return $node->title;
        }

        return class_basename($type).' #'.$id;
    }

    public function render(): \Illuminate\View\View
    {
        return view('pagebuilder::livewire.admin.page-sections.page-selector')
            ->layout('layouts.admin-clean');
    }
}

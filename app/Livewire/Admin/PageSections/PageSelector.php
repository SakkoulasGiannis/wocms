<?php

namespace App\Livewire\Admin\PageSections;

use App\Models\PageSection;
use App\Models\ContentNode;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PageSelector extends Component
{
    public $pages = [];

    public function mount()
    {
        $this->loadPages();
    }

    public function loadPages()
    {
        // Get all unique page_types from page_sections
        $sectionPages = PageSection::select('page_type')
            ->groupBy('page_type')
            ->get()
            ->map(function ($section) {
                $sectionsCount = PageSection::where('page_type', $section->page_type)
                    ->count();

                $title = $this->getPageTitle($section->page_type);

                return [
                    'page_type' => $section->page_type,
                    'title' => $title,
                    'sections_count' => $sectionsCount,
                    'is_home' => $section->page_type === 'home',
                ];
            });

        $this->pages = $sectionPages->sortBy(function ($page) {
            // Sort: home first, then alphabetically
            return $page['is_home'] ? '0' : '1' . $page['title'];
        })->values()->toArray();
    }

    protected function getPageTitle(string $pageType): string
    {
        // Attempt to get a friendly title from ContentNode if it exists
        $node = ContentNode::where('slug', $pageType)->first();

        if ($node) {
            return $node->title;
        }

        // Otherwise, just capitalize the page_type
        return ucfirst(str_replace('_', ' ', $pageType));
    }

    public function render()
    {
        return view('livewire.admin.page-sections.page-selector')
            ->layout('layouts.admin-clean');
    }
}

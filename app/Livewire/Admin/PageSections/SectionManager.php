<?php

namespace App\Livewire\Admin\PageSections;

use App\Models\PageSection;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class SectionManager extends Component
{
    public $pageType = 'home';
    public $sections = [];
    public $availableSectionTypes = [];

    public $showAddModal = false;
    public $editingSection = null;
    public $selectedSectionType = '';

    // UI State
    public $selectedSectionId = null;
    public $showJsonFor = []; // Array of section IDs showing JSON

    // Form fields
    public $sectionName = '';
    public $sectionContent = [];
    public $sectionSettings = [];

    public function mount(?string $pageType = 'home')
    {
        $this->pageType = $pageType;
        $this->loadSections();
        $this->availableSectionTypes = PageSection::getSectionTypes();
    }

    public function loadSections()
    {
        $this->sections = PageSection::getPageSections($this->pageType, false);
    }

    public function openAddModal()
    {
        $this->showAddModal = true;
        $this->editingSection = null;
        $this->selectedSectionType = '';
        $this->sectionName = '';
        $this->sectionContent = [];
        $this->sectionSettings = [];
    }

    public function selectSectionType($type)
    {
        $this->selectedSectionType = $type;

        // Load default content and settings
        $typeInfo = $this->availableSectionTypes[$type];
        $this->sectionContent = $typeInfo['default_content'];
        $this->sectionSettings = $typeInfo['default_settings'];
    }

    public function saveSection()
    {
        if (empty($this->selectedSectionType)) {
            return;
        }

        $maxOrder = PageSection::where('page_type', $this->pageType)->max('order') ?? 0;

        PageSection::create([
            'page_type' => $this->pageType,
            'section_type' => $this->selectedSectionType,
            'name' => $this->sectionName ?: $this->availableSectionTypes[$this->selectedSectionType]['name'],
            'order' => $maxOrder + 1,
            'is_active' => true,
            'content' => $this->sectionContent,
            'settings' => $this->sectionSettings,
        ]);

        $this->showAddModal = false;
        $this->loadSections();

        session()->flash('message', 'Section added successfully!');
    }

    public function editSection($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $this->editingSection = $section->id;
            $this->selectedSectionType = $section->section_type;
            $this->sectionName = $section->name;
            $this->sectionContent = $section->content;
            $this->sectionSettings = $section->settings;
            $this->showAddModal = true;
        }
    }

    public function updateSection()
    {
        if (!$this->editingSection) {
            return;
        }

        $section = PageSection::find($this->editingSection);

        if ($section) {
            $section->update([
                'name' => $this->sectionName,
                'content' => $this->sectionContent,
                'settings' => $this->sectionSettings,
            ]);

            $this->showAddModal = false;
            $this->loadSections();

            session()->flash('message', 'Section updated successfully!');
        }
    }

    public function deleteSection($sectionId)
    {
        PageSection::destroy($sectionId);
        $this->loadSections();

        session()->flash('message', 'Section deleted successfully!');
    }

    public function toggleActive($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $section->update(['is_active' => !$section->is_active]);
            $this->loadSections();
        }
    }

    public function moveUp($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section && $section->order > 0) {
            $previousSection = PageSection::where('page_type', $this->pageType)
                ->where('order', '<', $section->order)
                ->orderBy('order', 'desc')
                ->first();

            if ($previousSection) {
                $tempOrder = $section->order;
                $section->update(['order' => $previousSection->order]);
                $previousSection->update(['order' => $tempOrder]);

                $this->loadSections();
            }
        }
    }

    public function moveDown($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $nextSection = PageSection::where('page_type', $this->pageType)
                ->where('order', '>', $section->order)
                ->orderBy('order', 'asc')
                ->first();

            if ($nextSection) {
                $tempOrder = $section->order;
                $section->update(['order' => $nextSection->order]);
                $nextSection->update(['order' => $tempOrder]);

                $this->loadSections();
            }
        }
    }

    public function selectSection($sectionId)
    {
        $this->selectedSectionId = $sectionId;
        $this->dispatch('section-selected', sectionId: $sectionId);
    }

    public function toggleJson($sectionId)
    {
        if (in_array($sectionId, $this->showJsonFor)) {
            $this->showJsonFor = array_diff($this->showJsonFor, [$sectionId]);
        } else {
            $this->showJsonFor[] = $sectionId;
        }
    }

    public function render()
    {
        return view('livewire.admin.page-sections.section-manager')
            ->layout('layouts.app');
    }
}

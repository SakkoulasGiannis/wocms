<?php

namespace Modules\PageBuilder\Livewire\Admin\PageSections;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\PageBuilder\Models\PageSection;
use Modules\PageBuilder\Models\SectionTemplate;

class SectionManager extends Component
{
    use WithFileUploads;

    public $sectionableType;

    public $sectionableId;

    public $pageTitle = '';

    public $sections = [];

    public $availableTemplates = [];

    public $availableSectionTypes = [];

    public $showAddModal = false;

    public $editingSection = null;

    public $selectedTemplateId = null;

    public $selectedSectionType = '';

    // UI State
    public $selectedSectionId = null;

    public $showJsonFor = [];

    // Form fields
    public $sectionName = '';

    public $sectionContent = [];

    public $sectionSettings = [];

    public $sectionImageUploads = [];

    public function updatedSectionImageUploads($value, $key): void
    {
        if (! $value) {
            return;
        }

        $path = $value->store('sections', 'public');
        $url = asset('storage/'.$path);

        // Parse key: "fieldName" or "fieldName.index.subFieldName"
        $parts = explode('.', $key);
        if (count($parts) === 1) {
            $this->sectionContent[$parts[0]] = $url;
        } elseif (count($parts) === 3) {
            $this->sectionContent[$parts[0]][$parts[1]][$parts[2]] = $url;
        }

        $this->sectionImageUploads[$key] = null;
    }

    public function mount(string $sectionableType, int $sectionableId): void
    {
        // Convert URL-safe format back to FQCN (App-Models-Home → App\Models\Home)
        $this->sectionableType = str_replace('-', '\\', $sectionableType);
        $this->sectionableId = $sectionableId;
        $this->pageTitle = $this->resolveTitle();
        $this->loadSections();
        $this->availableTemplates = SectionTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->toArray();
        $this->availableSectionTypes = PageSection::getSectionTypes();
    }

    protected function resolveTitle(): string
    {
        if (class_exists($this->sectionableType)) {
            $model = $this->sectionableType::find($this->sectionableId);
            if ($model) {
                return $model->title ?? $model->name ?? class_basename($this->sectionableType).' #'.$this->sectionableId;
            }
        }

        return class_basename($this->sectionableType).' #'.$this->sectionableId;
    }

    public function loadSections(): void
    {
        $this->sections = PageSection::getForModel($this->sectionableType, $this->sectionableId, false);
    }

    public function openAddModal(): void
    {
        $this->showAddModal = true;
        $this->editingSection = null;
        $this->selectedTemplateId = null;
        $this->selectedSectionType = '';
        $this->sectionName = '';
        $this->sectionContent = [];
        $this->sectionSettings = [];
    }

    public function selectTemplate($templateId): void
    {
        $this->selectedTemplateId = $templateId;
        $template = SectionTemplate::with('fields')->find($templateId);

        if ($template) {
            $this->selectedSectionType = str_replace('-', '_', $template->slug);
            $this->sectionName = $template->name;

            // Build default content from template fields
            $defaultContent = [];
            foreach ($template->fields as $field) {
                $defaultContent[$field->name] = $field->default_value ?? '';
            }
            $this->sectionContent = $defaultContent;
            $this->sectionSettings = $template->default_settings ?? [];
        }
    }

    public function selectSectionType($type): void
    {
        $this->selectedSectionType = $type;
        $this->selectedTemplateId = null;

        $typeInfo = $this->availableSectionTypes[$type] ?? null;
        if ($typeInfo) {
            $this->sectionContent = $typeInfo['default_content'];
            $this->sectionSettings = $typeInfo['default_settings'];
        }
    }

    public function saveSection(): void
    {
        if (empty($this->selectedSectionType) && empty($this->selectedTemplateId)) {
            return;
        }

        $maxOrder = PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId)
            ->max('order') ?? 0;

        $sectionType = $this->selectedSectionType;
        if (! $sectionType && $this->selectedTemplateId) {
            $template = SectionTemplate::find($this->selectedTemplateId);
            $sectionType = $template ? str_replace('-', '_', $template->slug) : 'custom';
        }

        $name = $this->sectionName;
        if (! $name) {
            $name = $this->availableSectionTypes[$sectionType]['name']
                ?? SectionTemplate::find($this->selectedTemplateId)?->name
                ?? 'Section';
        }

        PageSection::create([
            'sectionable_type' => $this->sectionableType,
            'sectionable_id' => $this->sectionableId,
            'section_template_id' => $this->selectedTemplateId,
            'section_type' => $sectionType,
            'name' => $name,
            'order' => $maxOrder + 1,
            'is_active' => true,
            'content' => $this->sectionContent,
            'settings' => $this->sectionSettings,
        ]);

        $this->showAddModal = false;
        $this->loadSections();

        session()->flash('message', 'Section added successfully!');
    }

    public function editSection($sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $this->editingSection = $section->id;
            $this->selectedSectionType = $section->section_type;
            $this->selectedTemplateId = $section->section_template_id;
            $this->sectionName = $section->name;
            $this->sectionContent = $section->content ?? [];
            $this->sectionSettings = $section->settings ?? [];
            $this->showAddModal = true;
        }
    }

    public function updateSection(): void
    {
        if (! $this->editingSection) {
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

    public function deleteSection($sectionId): void
    {
        PageSection::destroy($sectionId);
        $this->loadSections();

        session()->flash('message', 'Section deleted successfully!');
    }

    public function toggleActive($sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $section->update(['is_active' => ! $section->is_active]);
            $this->loadSections();
        }
    }

    public function moveUp($sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section && $section->order > 0) {
            $previousSection = PageSection::where('sectionable_type', $this->sectionableType)
                ->where('sectionable_id', $this->sectionableId)
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

    public function moveDown($sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $nextSection = PageSection::where('sectionable_type', $this->sectionableType)
                ->where('sectionable_id', $this->sectionableId)
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

    /**
     * Add an empty item to a repeater field
     */
    public function addRepeaterItem(string $fieldName): void
    {
        $template = SectionTemplate::with('fields')->find($this->selectedTemplateId);
        if (! $template) {
            return;
        }

        $field = $template->fields->where('name', $fieldName)->first();
        if (! $field) {
            return;
        }

        $subFields = json_decode($field->settings ?? '{}', true)['sub_fields'] ?? [];
        $newItem = [];
        foreach ($subFields as $sf) {
            $newItem[$sf['name']] = '';
        }

        $items = $this->sectionContent[$fieldName] ?? [];
        $items[] = $newItem;
        $this->sectionContent[$fieldName] = $items;
    }

    /**
     * Remove an item from a repeater field
     */
    public function removeRepeaterItem(string $fieldName, int $index): void
    {
        $items = $this->sectionContent[$fieldName] ?? [];
        unset($items[$index]);
        $this->sectionContent[$fieldName] = array_values($items);
    }

    public function selectSection($sectionId): void
    {
        $this->selectedSectionId = $sectionId;
        $this->dispatch('section-selected', sectionId: $sectionId);
    }

    public function toggleJson($sectionId): void
    {
        if (in_array($sectionId, $this->showJsonFor)) {
            $this->showJsonFor = array_diff($this->showJsonFor, [$sectionId]);
        } else {
            $this->showJsonFor[] = $sectionId;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('pagebuilder::livewire.admin.page-sections.section-manager')
            ->layout('layouts.app');
    }
}

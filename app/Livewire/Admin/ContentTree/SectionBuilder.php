<?php

namespace App\Livewire\Admin\ContentTree;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use App\Services\PageImporter;
use App\Services\PageSerializer;
use Livewire\Component;

class SectionBuilder extends Component
{
    public $nodeId;

    public $node;

    public $sections = [];

    public $expandedSections = [];

    public $editingItems = []; // Track which items are being edited

    public $showAddModal = false;

    public $availableSectionTemplates = [];

    protected $listeners = ['node-selected' => 'loadNode'];

    public function mount($nodeId = null)
    {
        if ($nodeId) {
            $this->nodeId = $nodeId;
            $this->loadNode($nodeId);
        }
    }

    public function loadNode($nodeId)
    {
        $this->nodeId = $nodeId;
        $this->node = ContentNode::with('template')->find($nodeId);

        if ($this->node) {
            // Load the actual content model that has sections
            $contentModel = $this->node->getContentModel();

            if ($contentModel && method_exists($contentModel, 'sections')) {
                $this->sections = $contentModel->sections()
                    ->with('sectionTemplate')
                    ->orderBy('order')
                    ->get()
                    ->toArray();
            } else {
                $this->sections = [];
            }

            // Load available section templates
            $this->availableSectionTemplates = SectionTemplate::where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->toArray();
        }
    }

    public function toggleSection($sectionId)
    {
        if (in_array($sectionId, $this->expandedSections)) {
            $this->expandedSections = array_diff($this->expandedSections, [$sectionId]);
        } else {
            $this->expandedSections[] = $sectionId;
        }
    }

    public function toggleItem($itemKey)
    {
        if (in_array($itemKey, $this->editingItems)) {
            $this->editingItems = array_diff($this->editingItems, [$itemKey]);
        } else {
            $this->editingItems[] = $itemKey;
        }
    }

    public function addSection()
    {
        $this->showAddModal = true;
    }

    public function createSection($sectionTemplateId)
    {
        $contentModel = $this->node->getContentModel();

        if (! $contentModel) {
            session()->flash('error', 'Cannot add section: content model not found.');

            return;
        }

        $sectionTemplate = SectionTemplate::find($sectionTemplateId);

        if (! $sectionTemplate) {
            session()->flash('error', 'Section template not found.');

            return;
        }

        // Get the next order
        $maxOrder = PageSection::where('sectionable_type', get_class($contentModel))
            ->where('sectionable_id', $contentModel->id)
            ->max('order') ?? 0;

        // Create the section
        $section = new PageSection;
        $section->section_template_id = $sectionTemplate->id;
        $section->sectionable_type = get_class($contentModel);
        $section->sectionable_id = $contentModel->id;
        $section->section_type = $sectionTemplate->type ?? 'custom'; // Add section_type field
        $section->name = $sectionTemplate->name;
        $section->order = $maxOrder + 1;
        $section->is_active = true;

        // Initialize content with default values from template fields
        $defaultContent = [];
        foreach ($sectionTemplate->fields as $field) {
            $defaultContent[$field['name']] = $field['default_value'] ?? '';
        }
        $section->content = $defaultContent;

        $section->save();

        $this->showAddModal = false;
        $this->loadNode($this->nodeId);

        session()->flash('success', 'Section added successfully!');
    }

    public function deleteSection($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $section->delete();
            $this->loadNode($this->nodeId);
            session()->flash('success', 'Section deleted successfully!');
        }
    }

    public function toggleSectionVisibility($sectionId)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $section->is_active = ! $section->is_active;
            $section->save();
            $this->loadNode($this->nodeId);
        }
    }

    public function updateSectionOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $sectionId) {
            PageSection::where('id', $sectionId)->update(['order' => $index]);
        }

        $this->loadNode($this->nodeId);
    }

    public function updateItemContent($sectionId, $itemKey, $value)
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $content = $section->content ?? [];

            // Support nested keys like "items.0.title"
            $keys = explode('.', $itemKey);
            $current = &$content;

            foreach ($keys as $i => $key) {
                if ($i === count($keys) - 1) {
                    $current[$key] = $value;
                } else {
                    if (! isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }
            }

            $section->content = $content;
            $section->save();

            $this->loadNode($this->nodeId);
        }
    }

    public function getPageJson(): array
    {
        if (! $this->node) {
            return [];
        }

        return app(PageSerializer::class)->serialize($this->node);
    }

    public function exportJson(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $json = $this->getPageJson();
        $filename = str($this->node->title ?? 'page')->slug()->append('.json')->toString();

        return response()->streamDownload(function () use ($json) {
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function importJson(string $json): void
    {
        if (! $this->node) {
            return;
        }

        $layout = json_decode($json, true);

        if (! $layout || ! isset($layout['sections'])) {
            session()->flash('error', 'Invalid JSON format.');

            return;
        }

        app(PageImporter::class)->import($this->node, $layout);
        $this->loadNode($this->nodeId);
        session()->flash('success', 'Page imported successfully.');
    }

    public function render()
    {
        return view('livewire.admin.content-tree.section-builder');
    }
}

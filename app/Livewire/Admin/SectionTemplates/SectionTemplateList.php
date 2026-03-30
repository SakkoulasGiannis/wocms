<?php

namespace App\Livewire\Admin\SectionTemplates;

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use App\Services\AISectionGenerator;
use App\Services\SectionTemplateExporter;
use Livewire\Component;
use Livewire\WithFileUploads;

class SectionTemplateList extends Component
{
    use WithFileUploads;

    public $templates;

    public $categories;

    public $showCreateModal = false;

    public $activeTab = 'manual'; // 'manual' or 'ai'

    public $name = '';

    public $category = 'custom';

    public $description = '';

    public $html_template = '';

    // AI fields
    public $aiInput = '';

    public $aiInputType = 'description'; // 'description' or 'html'

    public $aiGenerating = false;

    public $aiError = null;

    public $aiPreview = null;

    public function mount()
    {
        $this->loadTemplates();
        $this->categories = SectionTemplate::getCategories();
    }

    public function loadTemplates()
    {
        $this->templates = SectionTemplate::with('fields')
            ->orderBy('is_system', 'desc')
            ->orderBy('category')
            ->orderBy('order')
            ->get();
    }

    public function delete($id)
    {
        try {
            $template = SectionTemplate::findOrFail($id);

            // This will throw exception if it's a system template
            $template->delete();

            $this->loadTemplates();
            session()->flash('success', "Section template '{$template->name}' deleted successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        $template = SectionTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);
        $this->loadTemplates();
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'description', 'html_template', 'aiInput', 'aiPreview', 'aiError', 'activeTab']);
        $this->activeTab = 'ai'; // Start with AI tab
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->aiError = null;
    }

    /**
     * Generate section template using AI
     */
    public function generateWithAI()
    {
        $this->validate([
            'aiInput' => 'required|string|min:10',
        ]);

        $this->aiGenerating = true;
        $this->aiError = null;
        $this->aiPreview = null;

        try {
            $generator = new AISectionGenerator;
            $result = $generator->generateSection($this->aiInput, $this->aiInputType);

            // Store preview
            $this->aiPreview = $result;

            // Auto-fill the form
            $this->name = $result['name'];
            $this->description = $result['description'] ?? '';
            $this->category = $result['category'] ?? 'custom';
            $this->html_template = $result['html_template'];

            // Switch to manual tab to show preview
            $this->activeTab = 'manual';

            session()->flash('ai_success', 'AI generated section successfully! Review and save below.');
        } catch (\Exception $e) {
            $this->aiError = $e->getMessage();
            \Log::error('AI Section Generation Error: '.$e->getMessage(), [
                'input' => $this->aiInput,
                'type' => $this->aiInputType,
            ]);
        } finally {
            $this->aiGenerating = false;
        }
    }

    public function createTemplate()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
        ]);

        $template = SectionTemplate::create([
            'name' => $this->name,
            'slug' => \Str::slug($this->name),
            'category' => $this->category,
            'description' => $this->description,
            'html_template' => $this->html_template,
            'is_system' => false,
            'is_active' => true,
        ]);

        // Create fields from AI preview or extract from HTML
        if ($this->aiPreview && isset($this->aiPreview['fields'])) {
            foreach ($this->aiPreview['fields'] as $field) {
                SectionTemplateField::create([
                    'section_template_id' => $template->id,
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'default_value' => $field['default_value'] ?? '',
                    'is_required' => $field['is_required'] ?? false,
                    'order' => $field['order'] ?? 0,
                ]);
            }
        } else {
            // Auto-detect fields from HTML template
            $generator = new AISectionGenerator;
            $fields = $generator->extractFields($this->html_template);

            foreach ($fields as $field) {
                SectionTemplateField::create([
                    'section_template_id' => $template->id,
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'default_value' => $field['default_value'] ?? '',
                    'is_required' => $field['is_required'] ?? false,
                    'order' => $field['order'] ?? 0,
                ]);
            }
        }

        $this->loadTemplates();
        $this->closeCreateModal();
        session()->flash('success', "Section template '{$template->name}' created successfully with ".count($fields ?? $this->aiPreview['fields']).' fields.');
    }

    public $importFile;

    public $showImportModal = false;

    public $importOverwrite = false;

    /**
     * Export all section templates as JSON download
     */
    public function exportAll(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exporter = new SectionTemplateExporter;
        $data = $exporter->exportAll();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'section-templates-'.date('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function openImportModal(): void
    {
        $this->reset(['importFile', 'importOverwrite']);
        $this->showImportModal = true;
    }

    public function importTemplates(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:json,txt|max:10240',
        ]);

        try {
            $json = file_get_contents($this->importFile->getRealPath());
            $data = json_decode($json, true);

            if (! is_array($data)) {
                session()->flash('error', 'Invalid JSON file format.');

                return;
            }

            $exporter = new SectionTemplateExporter;
            $stats = $exporter->import($data, $this->importOverwrite);

            $this->loadTemplates();
            $this->showImportModal = false;

            session()->flash('success', "Import complete: {$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped.");
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.section-templates.section-template-list')
            ->layout('layouts.admin-clean')
            ->title('Section Templates');
    }
}

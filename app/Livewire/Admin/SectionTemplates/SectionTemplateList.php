<?php

namespace App\Livewire\Admin\SectionTemplates;

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Livewire\Component;

class SectionTemplateList extends Component
{
    public $templates;
    public $categories;

    public $showCreateModal = false;
    public $name = '';
    public $category = 'custom';
    public $description = '';
    public $html_template = '';

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
        $template->update(['is_active' => !$template->is_active]);
        $this->loadTemplates();
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'description', 'html_template']);
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
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
            'category' => $this->category,
            'description' => $this->description,
            'html_template' => $this->html_template,
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->loadTemplates();
        $this->closeCreateModal();
        session()->flash('success', "Section template '{$template->name}' created successfully.");
    }

    public function render()
    {
        return view('livewire.admin.section-templates.section-template-list')
            ->layout('layouts.admin-clean')
            ->title('Section Templates');
    }
}

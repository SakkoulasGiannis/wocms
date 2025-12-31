<?php

namespace App\Livewire\Admin\SectionTemplates;

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Livewire\Component;
use Illuminate\Support\Str;

class SectionTemplateForm extends Component
{
    public $templateId;
    public $template;

    // Template fields
    public $name = '';
    public $slug = '';
    public $category = 'custom';
    public $description = '';
    public $html_template = '';
    public $is_active = true;
    public $order = 0;

    // Fields
    public $fields = [];
    public $showAddFieldModal = false;
    public $editingFieldIndex = null;

    // Field form
    public $fieldForm = [
        'name' => '',
        'label' => '',
        'type' => 'text',
        'default_value' => '',
        'is_required' => false,
        'options' => '',
        'order' => 0,
    ];

    public $categories;
    public $fieldTypes;

    public function mount($templateId = null)
    {
        $this->templateId = $templateId;
        $this->categories = SectionTemplate::getCategories();
        $this->fieldTypes = [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'wysiwyg' => 'WYSIWYG Editor',
            'image' => 'Image',
            'url' => 'URL',
            'email' => 'Email',
            'number' => 'Number',
            'checkbox' => 'Checkbox',
            'select' => 'Select Dropdown',
        ];

        if ($templateId) {
            $this->loadTemplate();
        }
    }

    public function loadTemplate()
    {
        $this->template = SectionTemplate::with('fields')->findOrFail($this->templateId);

        $this->name = $this->template->name;
        $this->slug = $this->template->slug;
        $this->category = $this->template->category;
        $this->description = $this->template->description ?? '';
        $this->html_template = $this->template->html_template;
        $this->is_active = $this->template->is_active;
        $this->order = $this->template->order;

        // Load fields
        $this->fields = $this->template->fields->map(function($field) {
            return [
                'id' => $field->id,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'default_value' => $field->default_value ?? '',
                'is_required' => $field->is_required,
                'options' => $field->options ?? '',
                'order' => $field->order,
            ];
        })->toArray();
    }

    public function updatedName()
    {
        if (!$this->templateId) {
            // Auto-generate slug for new templates
            $this->slug = Str::slug($this->name);
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:section_templates,slug,' . ($this->templateId ?? 'NULL'),
            'category' => 'required|string',
            'html_template' => 'required|string',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'description' => $this->description,
            'html_template' => $this->html_template,
            'is_active' => $this->is_active,
            'order' => $this->order,
        ];

        if ($this->templateId) {
            // Update
            $this->template->update($data);
            $template = $this->template;
        } else {
            // Create
            $data['is_system'] = false;
            $template = SectionTemplate::create($data);
            $this->templateId = $template->id;
            $this->template = $template;
        }

        // Sync fields
        $this->syncFields($template);

        session()->flash('success', 'Section template saved successfully.');

        if (!$this->templateId) {
            return redirect()->route('admin.section-templates.edit', $template->id);
        }
    }

    protected function syncFields($template)
    {
        // Delete fields not in the list
        $fieldIds = collect($this->fields)->pluck('id')->filter();
        $template->fields()->whereNotIn('id', $fieldIds)->delete();

        // Create/Update fields
        foreach ($this->fields as $order => $fieldData) {
            $data = [
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'type' => $fieldData['type'],
                'default_value' => $fieldData['default_value'],
                'is_required' => $fieldData['is_required'],
                'options' => $fieldData['options'],
                'order' => $order,
            ];

            if (isset($fieldData['id']) && $fieldData['id']) {
                // Update existing
                $template->fields()->where('id', $fieldData['id'])->update($data);
            } else {
                // Create new
                $template->fields()->create($data);
            }
        }
    }

    public function openAddFieldModal()
    {
        $this->reset(['fieldForm', 'editingFieldIndex']);
        $this->fieldForm['order'] = count($this->fields);
        $this->showAddFieldModal = true;
    }

    public function editField($index)
    {
        $this->editingFieldIndex = $index;
        $this->fieldForm = $this->fields[$index];
        $this->showAddFieldModal = true;
    }

    public function saveField()
    {
        $this->validate([
            'fieldForm.name' => 'required|string|max:255',
            'fieldForm.label' => 'required|string|max:255',
            'fieldForm.type' => 'required|string',
        ]);

        // Auto-generate name from label if empty
        if (empty($this->fieldForm['name'])) {
            $this->fieldForm['name'] = Str::snake($this->fieldForm['label']);
        }

        if ($this->editingFieldIndex !== null) {
            // Update existing field
            $this->fields[$this->editingFieldIndex] = $this->fieldForm;
        } else {
            // Add new field
            $this->fields[] = $this->fieldForm;
        }

        $this->closeAddFieldModal();
    }

    public function deleteField($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveFieldUp($index)
    {
        if ($index > 0) {
            $temp = $this->fields[$index];
            $this->fields[$index] = $this->fields[$index - 1];
            $this->fields[$index - 1] = $temp;
        }
    }

    public function moveFieldDown($index)
    {
        if ($index < count($this->fields) - 1) {
            $temp = $this->fields[$index];
            $this->fields[$index] = $this->fields[$index + 1];
            $this->fields[$index + 1] = $temp;
        }
    }

    public function closeAddFieldModal()
    {
        $this->showAddFieldModal = false;
        $this->reset(['fieldForm', 'editingFieldIndex']);
    }

    public function render()
    {
        return view('livewire.admin.section-templates.section-template-form')
            ->layout('layouts.admin-clean')
            ->title($this->templateId ? 'Edit Section Template' : 'Create Section Template');
    }
}

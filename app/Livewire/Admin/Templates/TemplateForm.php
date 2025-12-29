<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;
use Livewire\Component;
use Illuminate\Support\Str;

class TemplateForm extends Component
{
    public ?Template $template = null;
    public $templateId;

    // Template Fields
    public $name = '';
    public $slug = '';
    public $useSlugPrefix = false;
    public $description = '';
    public $has_physical_file = false;
    public $requires_database = true;
    public $file_path = '';
    public $render_mode = 'full_page_grapejs';
    public $html_content = '';
    public $is_active = true;
    public $is_public = true;
    public $show_in_menu = false;
    public $menu_label = '';
    public $menu_order = 0;

    // Family & Hierarchy Settings
    public $allow_children = true;
    public $allow_new_pages = true;
    public $allowed_parent_templates = [];
    public $allowed_child_templates = [];

    // Access Control
    public $use_custom_access = false;
    public $allowed_roles = [];

    // Visual
    public $icon = '';

    // Caching
    public $enable_full_page_cache = false;
    public $cache_ttl = 3600;

    // Tree Structure
    public $parent_id = null;

    // Fields Array
    public $fields = [];
    public $fieldTypes = [];
    public $availableTemplates = [];

    public function mount($templateId = null)
    {
        $this->fieldTypes = TemplateField::getFieldTypes();

        // Load all templates for parent/child selection (excluding current template)
        $this->availableTemplates = Template::where('is_active', true)
            ->when($templateId, fn($q) => $q->where('id', '!=', $templateId))
            ->orderBy('name')
            ->get();

        if ($templateId) {
            $this->template = Template::with('fields')->findOrFail($templateId);
            $this->templateId = $this->template->id;
            $this->name = $this->template->name;
            $this->slug = $this->template->slug;
            $this->useSlugPrefix = $this->template->use_slug_prefix ?? false;
            $this->description = $this->template->description;
            $this->has_physical_file = $this->template->has_physical_file;
            $this->file_path = $this->template->file_path;
            $this->render_mode = $this->template->render_mode ?? 'full_page_grapejs';
            $this->html_content = $this->template->html_content;
            $this->is_active = $this->template->is_active;
            $this->is_public = $this->template->is_public;
            $this->show_in_menu = $this->template->show_in_menu;
            $this->menu_label = $this->template->menu_label;
            $this->menu_order = $this->template->menu_order;

            // Load family & hierarchy settings
            $this->allow_children = $this->template->allow_children;
            $this->allow_new_pages = $this->template->allow_new_pages;
            $this->allowed_parent_templates = $this->template->allowed_parent_templates ?? [];
            $this->allowed_child_templates = $this->template->allowed_child_templates ?? [];

            // Load access control
            $this->use_custom_access = $this->template->use_custom_access;
            $this->allowed_roles = $this->template->allowed_roles ?? [];

            // Load visual
            $this->icon = $this->template->icon ?? '';

            // Load caching
            $this->enable_full_page_cache = $this->template->enable_full_page_cache ?? false;
            $this->cache_ttl = $this->template->cache_ttl ?? 3600;

            // Load tree structure
            $this->parent_id = $this->template->parent_id;

            // Load existing fields
            $this->fields = $this->template->fields->map(function ($field) {
                $settings = $field->settings ?? [];
                // Ensure sub_fields key exists for backwards compatibility
                if (!isset($settings['sub_fields'])) {
                    $settings['sub_fields'] = '';
                }

                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'description' => $field->description,
                    'is_required' => $field->is_required,
                    'show_in_table' => $field->show_in_table ?? true,
                    'column_position' => $field->column_position ?? 'main',
                    'is_searchable' => $field->is_searchable ?? false,
                    'is_filterable' => $field->is_filterable ?? false,
                    'is_url_identifier' => $field->is_url_identifier ?? false,
                    'default_value' => $field->default_value,
                    'validation_rules' => $field->validation_rules ?? [],
                    'settings' => $settings,
                    'insert_after_index' => null,
                ];
            })->toArray();
        } else {
            // For new templates, default parent to Home template
            $homeTemplate = Template::where('slug', 'home')->first();
            if ($homeTemplate) {
                $this->parent_id = $homeTemplate->id;
            }

            // Add default fields for new templates
            $this->addDefaultFieldsToForm();
        }
    }

    /**
     * Add default fields to the form (for display in UI)
     */
    protected function addDefaultFieldsToForm()
    {
        $this->fields = [
            [
                'id' => null,
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'description' => 'Page title',
                'is_required' => true,
                'show_in_table' => true,
                'column_position' => 'main',
                'is_searchable' => true,
                'is_filterable' => false,
                'is_url_identifier' => false,
                'default_value' => '',
                'validation_rules' => [],
                'adapts_to_render_mode' => false,
                'settings' => [
                    'sub_fields' => '',
                ],
                'insert_after_index' => null,
            ],
            [
                'id' => null,
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'description' => 'URL-friendly version of the title',
                'is_required' => true,
                'show_in_table' => true,
                'column_position' => 'main',
                'is_searchable' => false,
                'is_filterable' => false,
                'is_url_identifier' => true,
                'default_value' => '',
                'validation_rules' => [],
                'adapts_to_render_mode' => false,
                'settings' => [
                    'sub_fields' => '',
                ],
                'insert_after_index' => null,
            ],
            [
                'id' => null,
                'name' => 'body',
                'label' => 'Body',
                'type' => 'grapejs',
                'description' => 'Main content - adapts based on render mode (GrapeJS/WYSIWYG/Sections)',
                'is_required' => false,
                'show_in_table' => false,
                'column_position' => 'main',
                'is_searchable' => false,
                'is_filterable' => false,
                'is_url_identifier' => false,
                'default_value' => '',
                'validation_rules' => [],
                'adapts_to_render_mode' => true,
                'settings' => [
                    'sub_fields' => '',
                ],
                'insert_after_index' => null,
            ],
        ];
    }

    public function updatedName()
    {
        if (!$this->templateId) {
            $this->slug = Str::slug($this->name);
            if ($this->has_physical_file && empty($this->file_path)) {
                $this->file_path = 'templates/' . $this->slug . '.blade.php';
            }
        }
    }

    public function updatedHasPhysicalFile()
    {
        if ($this->has_physical_file && empty($this->file_path) && !empty($this->slug)) {
            $this->file_path = 'templates/' . $this->slug . '.blade.php';
        }
    }

    public function addField()
    {
        $this->fields[] = [
            'id' => null,
            'name' => '',
            'label' => '',
            'type' => 'text',
            'description' => '',
            'is_required' => false,
            'show_in_table' => true,
            'column_position' => 'main',
            'is_searchable' => false,
            'is_filterable' => false,
            'is_url_identifier' => false,
            'default_value' => '',
            'validation_rules' => [],
            'settings' => [
                'sub_fields' => '',
            ],
            'insert_after_index' => null,
        ];
    }

    public function updatedFields($value, $key)
    {
        // Auto-pluralize field name when it's updated
        if (preg_match('/fields\.(\d+)\.name/', $key, $matches)) {
            $index = $matches[1];
            $fieldName = $this->fields[$index]['name'];

            // Remove non-latin characters and convert to lowercase
            $fieldName = strtolower(preg_replace('/[^a-zA-Z_]/', '', $fieldName));

            // Pluralize the field name
            if (!empty($fieldName)) {
                $this->fields[$index]['name'] = Str::plural($fieldName);
            }
        }

        // Handle field repositioning when insert_after_index changes
        if (preg_match('/fields\.(\d+)\.insert_after_index/', $key, $matches)) {
            $currentIndex = (int)$matches[1];

            // Check if field still exists at this index
            if (!isset($this->fields[$currentIndex])) {
                return;
            }

            $insertAfterIndex = $this->fields[$currentIndex]['insert_after_index'];

            // Skip if empty or same position
            if ($insertAfterIndex === null || $insertAfterIndex === '' || $insertAfterIndex === 'null') {
                return;
            }

            // Convert to string for comparison
            $insertAfterIndexStr = (string)$insertAfterIndex;

            // Skip if trying to insert after itself
            if ($insertAfterIndexStr !== '-1' && (int)$insertAfterIndex == $currentIndex) {
                $this->fields[$currentIndex]['insert_after_index'] = null;
                return;
            }

            // Store the field to move
            $field = $this->fields[$currentIndex];

            // Remove from current position
            unset($this->fields[$currentIndex]);
            $this->fields = array_values($this->fields); // Re-index

            if ($insertAfterIndexStr === '-1') {
                // Insert at beginning
                array_unshift($this->fields, $field);
            } else {
                // Insert after specified index
                $targetIndex = (int)$insertAfterIndex;

                // Adjust index if current field was before the target
                if ($currentIndex < $targetIndex) {
                    $targetIndex--;
                }

                // Ensure target index is valid
                if ($targetIndex >= count($this->fields)) {
                    $this->fields[] = $field; // Add at end if target is out of bounds
                } else {
                    array_splice($this->fields, $targetIndex + 1, 0, [$field]);
                }
            }

            // Reset insert_after_index for all fields
            foreach ($this->fields as $idx => $f) {
                $this->fields[$idx]['insert_after_index'] = null;
            }

            // Force Livewire to re-render
            $this->dispatch('fieldReordered');
        }
    }

    public function removeField($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields); // Re-index
    }

    public function ensureSingleUrlIdentifier($clickedIndex)
    {
        // Get the current state of the clicked field (before wire:model updates it)
        $currentState = $this->fields[$clickedIndex]['is_url_identifier'] ?? false;

        // If it's currently false (will become true after click)
        if (!$currentState) {
            // Uncheck all other fields
            foreach ($this->fields as $index => &$field) {
                if ($index !== $clickedIndex) {
                    $field['is_url_identifier'] = false;
                }
            }
        }
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

    public function updateFieldOrder($orderedIds)
    {
        // Create a copy of current fields indexed by their current position
        $currentFields = $this->fields;

        // Reorder fields based on the new order
        $orderedFields = [];
        foreach ($orderedIds as $oldIndex) {
            if (isset($currentFields[$oldIndex])) {
                $orderedFields[] = $currentFields[$oldIndex];
            }
        }

        // Update the fields array
        $this->fields = $orderedFields;

        // Force Livewire to re-render
        $this->dispatch('fieldsReordered');
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:templates,slug,' . ($this->templateId ?? 'NULL'),
            'useSlugPrefix' => 'boolean',
            'description' => 'nullable|string',
            'has_physical_file' => 'boolean',
            'file_path' => 'nullable|string',
            'render_mode' => 'required|in:full_page_grapejs,sections,simple_content',
            'html_content' => 'nullable|string',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_label' => 'nullable|string|max:255',
            'menu_order' => 'nullable|integer',
            // Tree Structure
            'parent_id' => 'nullable|exists:templates,id',
            // Family & Hierarchy
            'allow_children' => 'boolean',
            'allow_new_pages' => 'boolean',
            'allowed_parent_templates' => 'nullable|array',
            'allowed_child_templates' => 'nullable|array',
            // Access
            'use_custom_access' => 'boolean',
            'allowed_roles' => 'nullable|array',
            // Visual
            'icon' => 'nullable|string',
            // Caching
            'enable_full_page_cache' => 'boolean',
            'cache_ttl' => 'nullable|integer|min:60',
            // Fields
            'fields.*.name' => 'required|string|regex:/^[a-z_]+$/',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.description' => 'nullable|string',
            'fields.*.is_required' => 'boolean',
            'fields.*.show_in_table' => 'boolean',
            'fields.*.is_searchable' => 'boolean',
            'fields.*.is_filterable' => 'boolean',
            'fields.*.is_url_identifier' => 'boolean',
        ]);

        // Custom validation for duplicate field names within the same template
        $fieldNames = collect($this->fields)->pluck('name')->filter();
        if ($fieldNames->count() !== $fieldNames->unique()->count()) {
            $this->addError('fields', 'Duplicate field names are not allowed within the same template.');
            return;
        }

        // Check for field name conflicts with existing fields in other templates (optional)
        foreach ($this->fields as $index => $field) {
            if (empty($field['name'])) continue;

            $existingField = \App\Models\TemplateField::where('name', $field['name'])
                ->where('template_id', $this->templateId ?? 0)
                ->when(isset($field['id']), function($query) use ($field) {
                    return $query->where('id', '!=', $field['id']);
                })
                ->first();

            if ($existingField) {
                $this->addError("fields.{$index}.name", "This field name already exists in this template.");
                return;
            }
        }

        $isNewTemplate = !$this->templateId;

        // Map camelCase to snake_case for database
        $data = $validated;
        $data['use_slug_prefix'] = $data['useSlugPrefix'] ?? false;
        unset($data['useSlugPrefix']);

        if ($this->templateId) {
            $this->template->update($data);
        } else {
            $this->template = Template::create($data);
        }

        // Update tree structure fields
        $this->updateTreeStructure();

        // Create physical file if needed
        if ($this->has_physical_file) {
            $this->template->createPhysicalFile();
        }

        // For new templates, if no fields were added, add default fields
        if ($isNewTemplate && empty($this->fields)) {
            $this->addDefaultFieldsToForm();
        }

        // Sync fields
        $this->syncFields();

        // Refresh template to load the synced fields
        $this->template->refresh();
        $this->template->load('fields');

        // Create database table and model
        $tableGenerator = new TemplateTableGenerator();
        if ($tableGenerator->createTableAndModel($this->template)) {
            session()->flash('success', 'Template and database table created successfully!');
        } else {
            session()->flash('warning', 'Template saved but table creation failed. Check logs.');
        }

        return redirect()->route('admin.templates.index');
    }

    protected function updateTreeStructure()
    {
        if (!$this->template->parent_id) {
            // Root template (like Home)
            $this->template->tree_level = 0;
            $this->template->tree_path = '/' . $this->template->id;
        } else {
            // Child template
            $parent = Template::find($this->template->parent_id);
            if ($parent) {
                $this->template->tree_level = $parent->tree_level + 1;
                $this->template->tree_path = $parent->tree_path . '/' . $this->template->id;
            }
        }

        $this->template->save();
    }


    protected function syncFields()
    {
        // Delete removed fields
        $fieldIds = collect($this->fields)->pluck('id')->filter();
        $this->template->fields()->whereNotIn('id', $fieldIds)->delete();

        // Create/Update fields
        foreach ($this->fields as $order => $fieldData) {
            // Remove UI-only fields that don't belong in database
            unset($fieldData['insert_after_index']);

            $fieldData['order'] = $order;
            $fieldData['template_id'] = $this->template->id;

            if (isset($fieldData['id']) && $fieldData['id']) {
                TemplateField::where('id', $fieldData['id'])->update($fieldData);
            } else {
                TemplateField::create($fieldData);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.templates.template-form')->layout('layouts.admin-clean');
    }
}

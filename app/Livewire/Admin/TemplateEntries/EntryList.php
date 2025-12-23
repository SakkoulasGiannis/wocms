<?php

namespace App\Livewire\Admin\TemplateEntries;

use App\Models\Template;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class EntryList extends Component
{
    use WithPagination;

    public $templateSlug;
    public $template;
    public $search = '';
    public $filters = [];

    public function mount($templateSlug)
    {
        $this->templateSlug = $templateSlug;
        $this->template = Template::with('fields')
            ->where('slug', $templateSlug)
            ->where('is_active', true)
            ->firstOrFail();

        // Initialize filters for filterable fields
        foreach ($this->template->fields->where('is_filterable', true) as $field) {
            $this->filters[$field->name] = '';
        }

        // If template doesn't require database, redirect to content tree or show info
        if (!$this->template->requires_database) {
            // This is a container template, show its children from ContentNode
            return;
        }

        // Check if dynamic model exists
        if (!$this->template->model_class || !class_exists("App\\Models\\{$this->template->model_class}")) {
            abort(500, 'Template model not found. Please save the template again to generate the model.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilters()
    {
        $this->resetPage();
    }

    public function deleteEntry($id)
    {
        $modelClass = "App\\Models\\{$this->template->model_class}";
        $entry = $modelClass::findOrFail($id);
        $entry->delete();

        session()->flash('success', $this->template->name . ' entry deleted successfully!');
    }

    public function render()
    {
        // If template doesn't require database, show ContentNode children
        if (!$this->template->requires_database) {
            // Find child templates of this template
            $childTemplateIds = \App\Models\Template::where('parent_id', $this->template->id)
                ->pluck('id')
                ->toArray();

            $children = \App\Models\ContentNode::whereIn('template_id', $childTemplateIds)
                ->with(['template', 'parent'])
                ->when($this->search, function($query) {
                    $query->where('title', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return view('livewire.admin.template-entries.entry-list-container', [
                'children' => $children,
            ])->layout('layouts.admin-clean');
        }

        $modelClass = "App\\Models\\{$this->template->model_class}";

        // If template supports children (hierarchical), load with ContentNode to show tree structure
        if ($this->template->allow_children) {
            // Get all ContentNodes for this template
            $contentNodes = \App\Models\ContentNode::where('template_id', $this->template->id)
                ->when($this->search, function($query) {
                    $query->where('title', 'like', '%' . $this->search . '%');
                })
                ->orderBy('tree_path')
                ->get();

            // Build tree structure
            $entries = $this->buildTreeStructure($contentNodes, $modelClass);

            return view('livewire.admin.template-entries.entry-list', [
                'entries' => $entries,
                'isTree' => true,
            ])->layout('layouts.admin-clean');
        }

        // Regular flat list for non-hierarchical templates
        $entries = $modelClass::query()
            ->when($this->search, function($query) {
                // Search only in searchable fields
                $searchableFields = $this->template->fields->where('is_searchable', true);
                if ($searchableFields->count() > 0) {
                    $query->where(function($q) use ($searchableFields) {
                        foreach ($searchableFields as $field) {
                            $q->orWhere($field->name, 'like', '%' . $this->search . '%');
                        }
                    });
                }
            })
            ->when($this->filters, function($query) {
                // Apply filters
                foreach ($this->filters as $fieldName => $filterValue) {
                    if (!empty($filterValue)) {
                        $query->where($fieldName, 'like', '%' . $filterValue . '%');
                    }
                }
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.template-entries.entry-list', [
            'entries' => $entries,
            'isTree' => false,
        ])->layout('layouts.admin-clean');
    }

    protected function buildTreeStructure($nodes, $modelClass)
    {
        $tree = [];
        $lookup = [];
        $childrenMap = [];

        // First pass: create lookup table and load content
        foreach ($nodes as $node) {
            // Load the actual content model
            if ($node->content_id && $node->content_type === $modelClass) {
                $node->content = $modelClass::find($node->content_id);
            }
            $lookup[$node->id] = $node;
            $childrenMap[$node->id] = [];
            $node->level = 0;
        }

        // Second pass: build tree
        foreach ($nodes as $node) {
            if ($node->parent_id && isset($lookup[$node->parent_id])) {
                $childrenMap[$node->parent_id][] = $node;
            } else {
                $tree[] = $node;
            }
        }

        // Assign children from map
        foreach ($nodes as $node) {
            $node->childNodes = $childrenMap[$node->id];
        }

        // Third pass: flatten with levels for display
        return $this->flattenTree($tree);
    }

    protected function flattenTree($nodes, $level = 0)
    {
        $result = [];
        foreach ($nodes as $node) {
            $node->level = $level;
            $result[] = $node;
            if (!empty($node->childNodes)) {
                $childResults = $this->flattenTree($node->childNodes, $level + 1);
                $result = array_merge($result, $childResults->all());
            }
        }
        return collect($result);
    }
}

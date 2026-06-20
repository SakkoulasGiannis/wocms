<?php

namespace App\Livewire\Admin\TemplateEntries;

use App\Models\Template;
use Livewire\Component;
use Livewire\WithPagination;

class EntryList extends Component
{
    use WithPagination;

    public $templateSlug;

    public $template;

    public $search = '';

    public $filters = [];

    /** Items per page. Bound to the per-page selector; persisted in the query string. */
    public int $perPage = 50;

    /** @var array<int, int> */
    public array $perPageOptions = [50, 100, 300, 500];

    protected function queryString(): array
    {
        return [
            'perPage' => ['except' => 50],
            'search' => ['except' => ''],
        ];
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /** Clamp perPage to an allowed option so the query string can't request unbounded rows. */
    protected function resolvedPerPage(): int
    {
        return in_array((int) $this->perPage, $this->perPageOptions, true)
            ? (int) $this->perPage
            : 50;
    }

    protected function resolveModelClass(): string
    {
        $mc = $this->template->model_class;

        return str_contains($mc, '\\') ? $mc : "App\\Models\\{$mc}";
    }

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
        if (! $this->template->requires_database) {
            // This is a container template, show its children from ContentNode
            return;
        }

        // Check if dynamic model exists
        if (! $this->template->model_class || ! class_exists($this->resolveModelClass())) {
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

    public function deleteEntry($id): void
    {
        $modelClass = $this->resolveModelClass();
        $entry = $modelClass::findOrFail($id);
        $entry->delete();

        session()->flash('success', $this->template->name.' entry deleted successfully!');
    }

    /**
     * Clone an entry into a new row + (if applicable) its full PageSection
     * tree. Adjusts `title`/`name` to "<old> (Copy)" and slugs to a fresh
     * unique value so the duplicate doesn't collide with the original.
     *
     * Sectionable cloning recurses through `parent_section_id` so a deeply
     * nested page-builder layout copies intact.
     */
    public function duplicateEntry(int $id): void
    {
        $modelClass = $this->resolveModelClass();

        try {
            \DB::transaction(function () use ($modelClass, $id) {
                /** @var \Illuminate\Database\Eloquent\Model $source */
                $source = $modelClass::findOrFail($id);

                // Eloquent replicate() copies fillable attributes but NOT
                // the primary key or timestamps — exactly what we want.
                $copy = $source->replicate();

                $fillable = $source->getFillable();
                $columns = \Schema::hasTable($source->getTable())
                    ? \Schema::getColumnListing($source->getTable())
                    : $fillable;

                // Tweak title / name to make the copy visually distinct
                foreach (['title', 'name', 'heading'] as $field) {
                    if (in_array($field, $columns, true) && ! empty($source->{$field})) {
                        $copy->{$field} = $source->{$field}.' (Copy)';
                        break;
                    }
                }

                // Generate a fresh, unique slug if the model uses one
                if (in_array('slug', $columns, true) && ! empty($source->slug)) {
                    $copy->slug = $this->uniqueSlug($modelClass, $source->slug);
                }

                // Don't carry a "default" flag across — only one entry can be default
                foreach (['is_default', 'default'] as $defFlag) {
                    if (in_array($defFlag, $columns, true)) {
                        $copy->{$defFlag} = false;
                    }
                }

                $copy->save();

                // Clone associated PageSections (and their children, recursively)
                // when the model is sectionable. Detected by presence of
                // page_sections rows pointing at this entry.
                if (class_exists(\Modules\PageBuilder\Models\PageSection::class)) {
                    $this->cloneSectionsTree($modelClass, $source->id, $copy->id, null, null);
                }
            });

            session()->flash('success', $this->template->name.' entry duplicated successfully!');
        } catch (\Throwable $e) {
            \Log::warning('EntryList duplicateEntry failed', ['id' => $id, 'error' => $e->getMessage()]);
            session()->flash('error', 'Could not duplicate: '.$e->getMessage());
        }
    }

    /**
     * Find a slug derivative that isn't already used by another row of the
     * same model. Tries `<slug>-copy`, then `<slug>-copy-2`, `-copy-3`…
     */
    protected function uniqueSlug(string $modelClass, string $base): string
    {
        $candidate = $base.'-copy';
        $i = 1;
        while ($modelClass::where('slug', $candidate)->exists()) {
            $candidate = $base.'-copy-'.(++$i);
        }

        return $candidate;
    }

    /**
     * Recursively clone every page_section row belonging to `$sourceEntryId`
     * over onto `$copyEntryId`, preserving the parent/child hierarchy.
     */
    protected function cloneSectionsTree(string $modelClass, int $sourceEntryId, int $copyEntryId, ?int $sourceParentId, ?int $copyParentId): void
    {
        $sections = \Modules\PageBuilder\Models\PageSection::where('sectionable_type', $modelClass)
            ->where('sectionable_id', $sourceEntryId)
            ->where('parent_section_id', $sourceParentId)
            ->orderBy('order')
            ->get();

        foreach ($sections as $section) {
            $newSection = $section->replicate();
            $newSection->sectionable_id = $copyEntryId;
            $newSection->parent_section_id = $copyParentId;
            $newSection->save();

            $this->cloneSectionsTree($modelClass, $sourceEntryId, $copyEntryId, $section->id, $newSection->id);
        }
    }

    public function setDefaultEntry(int $contentNodeId): void
    {
        // Clear default flag from all nodes belonging to this template
        \App\Models\ContentNode::where('template_id', $this->template->id)
            ->update(['is_default' => false]);

        // Mark the chosen node
        \App\Models\ContentNode::where('id', $contentNodeId)
            ->update(['is_default' => true]);

        session()->flash('success', 'Default home page updated.');
    }

    /**
     * Persist new order after drag-and-drop.
     *
     * For non-DB templates: IDs are ContentNode IDs.
     * For tree DB templates (allow_children): IDs are ContentNode IDs.
     * For flat DB templates: IDs are entry model IDs; if the model has a
     * `sort_order` column we update it directly, otherwise we update the
     * linked ContentNode's sort_order.
     *
     * @param  array<int, int>  $orderedIds  IDs in their new order
     */
    public function reorder(array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        \DB::transaction(function () use ($orderedIds) {
            // Non-DB (container) templates → ContentNode IDs
            if (! $this->template->requires_database) {
                foreach ($orderedIds as $position => $id) {
                    \App\Models\ContentNode::where('id', $id)->update(['sort_order' => $position]);
                }

                return;
            }

            // Tree DB templates → ContentNode IDs (tree-based)
            if ($this->template->allow_children) {
                foreach ($orderedIds as $position => $id) {
                    \App\Models\ContentNode::where('id', $id)
                        ->where('template_id', $this->template->id)
                        ->update(['sort_order' => $position]);
                }

                return;
            }

            // Flat DB templates → entry model IDs
            $modelClass = $this->resolveModelClass();
            $table = (new $modelClass)->getTable();

            if (\Schema::hasColumn($table, 'sort_order')) {
                foreach ($orderedIds as $position => $id) {
                    $modelClass::where('id', $id)->update(['sort_order' => $position]);
                }

                return;
            }

            // Fallback: update linked ContentNode rows
            foreach ($orderedIds as $position => $id) {
                \App\Models\ContentNode::where('content_type', $modelClass)
                    ->where('content_id', $id)
                    ->update(['sort_order' => $position]);
            }
        });

        session()->flash('success', 'Order updated.');
    }

    public function render()
    {
        $sortable = (bool) ($this->template->settings['sortable'] ?? false);

        // If template doesn't require database, show ContentNode children
        if (! $this->template->requires_database) {
            // Find child templates of this template
            $childTemplateIds = \App\Models\Template::where('parent_id', $this->template->id)
                ->pluck('id')
                ->toArray();

            $query = \App\Models\ContentNode::whereIn('template_id', $childTemplateIds)
                ->with(['template', 'parent'])
                ->when($this->search, function ($query) {
                    $query->where('title', 'like', '%'.$this->search.'%');
                });

            if ($sortable) {
                $query->orderBy('sort_order')->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $children = $query->paginate($this->resolvedPerPage());

            return view('livewire.admin.template-entries.entry-list-container', [
                'children' => $children,
                'sortable' => $sortable,
            ])->layout('layouts.admin-clean');
        }

        $modelClass = $this->resolveModelClass();

        // If template supports children (hierarchical), load with ContentNode to show tree structure
        if ($this->template->allow_children) {
            // Get all ContentNodes for this template. When sortable, order by
            // parent + sort_order so buildTreeStructure preserves sibling order.
            $contentNodes = \App\Models\ContentNode::where('template_id', $this->template->id)
                ->when($this->search, function ($query) {
                    $query->where('title', 'like', '%'.$this->search.'%');
                })
                ->when($sortable, function ($query) {
                    $query->orderByRaw('COALESCE(parent_id, 0) asc')->orderBy('sort_order');
                }, function ($query) {
                    $query->orderBy('tree_path');
                })
                ->get();

            // Build tree structure
            $entries = $this->buildTreeStructure($contentNodes, $modelClass);

            return view('livewire.admin.template-entries.entry-list', [
                'entries' => $entries,
                'isTree' => true,
                'sortable' => $sortable,
            ])->layout('layouts.admin-clean');
        }

        // Regular flat list for non-hierarchical templates
        $table = (new $modelClass)->getTable();
        $hasSortOrder = \Schema::hasColumn($table, 'sort_order');

        $entries = $modelClass::query()
            ->when($this->search, function ($query) {
                // Search only in searchable fields
                $searchableFields = $this->template->fields->where('is_searchable', true);
                if ($searchableFields->count() > 0) {
                    $query->where(function ($q) use ($searchableFields) {
                        foreach ($searchableFields as $field) {
                            $q->orWhere($field->name, 'like', '%'.$this->search.'%');
                        }
                    });
                }
            })
            ->when($this->filters, function ($query) {
                // Apply filters
                foreach ($this->filters as $fieldName => $filterValue) {
                    if (! empty($filterValue)) {
                        $query->where($fieldName, 'like', '%'.$filterValue.'%');
                    }
                }
            })
            ->when($sortable && $hasSortOrder, function ($query) {
                $query->orderBy('sort_order');
            }, function ($query) {
                $query->latest();
            })
            ->paginate($this->resolvedPerPage());

        return view('livewire.admin.template-entries.entry-list', [
            'entries' => $entries,
            'isTree' => false,
            'sortable' => $sortable,
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
            if (! empty($node->childNodes)) {
                $childResults = $this->flattenTree($node->childNodes, $level + 1);
                $result = array_merge($result, $childResults->all());
            }
        }

        return collect($result);
    }
}

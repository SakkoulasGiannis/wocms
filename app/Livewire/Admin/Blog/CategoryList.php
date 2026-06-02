<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogCategory;
use Livewire\Component;

class CategoryList extends Component
{
    public string $search = '';

    /** @var array<string, array<int, string>|string> */
    protected $queryString = ['search' => ['except' => '']];

    public function delete(int $id): void
    {
        $cat = BlogCategory::findOrFail($id);
        $name = $cat->name;
        // Children become top-level (null parent_id) automatically via FK nullOnDelete.
        $cat->delete();
        session()->flash('success', "Category '{$name}' deleted.");
    }

    /**
     * Recursively flatten a tree for display, carrying depth for indentation.
     *
     * @return array<int, array{model: BlogCategory, depth: int}>
     */
    protected function flatten(\Illuminate\Support\Collection $nodes, int $depth = 0): array
    {
        $out = [];
        foreach ($nodes as $node) {
            $out[] = ['model' => $node, 'depth' => $depth];
            if ($node->children->isNotEmpty()) {
                $out = array_merge($out, $this->flatten($node->children, $depth + 1));
            }
        }

        return $out;
    }

    public function render()
    {
        $query = BlogCategory::query()
            ->with(['children.children.children', 'blogs'])
            ->orderBy('order')
            ->orderBy('name');

        if (trim($this->search) !== '') {
            // Flat search — show matching rows ignoring hierarchy
            $matches = (clone $query)
                ->where('name', 'like', '%'.trim($this->search).'%')
                ->get()
                ->map(fn ($m) => ['model' => $m, 'depth' => 0])
                ->all();

            return view('livewire.admin.blog.category-list', [
                'rows' => $matches,
                'isSearch' => true,
            ])->layout('layouts.admin-clean');
        }

        $roots = $query->whereNull('parent_id')->get();

        return view('livewire.admin.blog.category-list', [
            'rows' => $this->flatten($roots),
            'isSearch' => false,
        ])->layout('layouts.admin-clean');
    }
}

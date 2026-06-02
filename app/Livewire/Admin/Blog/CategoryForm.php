<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryForm extends Component
{
    public ?int $categoryId = null;

    public string $name = '';

    public string $slug = '';

    public ?int $parent_id = null;

    public string $description = '';

    public int $order = 0;

    public bool $is_active = true;

    public bool $slugManuallyTouched = false;

    public function mount(?int $categoryId = null): void
    {
        if ($categoryId) {
            $c = BlogCategory::findOrFail($categoryId);
            $this->categoryId = $c->id;
            $this->name = (string) $c->name;
            $this->slug = (string) $c->slug;
            $this->parent_id = $c->parent_id;
            $this->description = (string) ($c->description ?? '');
            $this->order = (int) $c->order;
            $this->is_active = (bool) $c->is_active;
            $this->slugManuallyTouched = true; // existing slug — don't auto-overwrite
        }
    }

    public function updated(string $key, mixed $value): void
    {
        if ($key === 'slug') {
            $expected = Str::slug((string) $this->name);
            if ($value !== '' && $value !== $expected) {
                $this->slugManuallyTouched = true;
            }
        }
        if ($key === 'name' && ! $this->slugManuallyTouched) {
            $this->slug = Str::slug((string) $value);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('blog_categories', 'slug')->ignore($this->categoryId),
            ],
            'parent_id' => [
                'nullable', 'integer', 'exists:blog_categories,id',
                function (string $attr, mixed $value, \Closure $fail): void {
                    if ($this->categoryId && $value === $this->categoryId) {
                        $fail('A category cannot be its own parent.');
                    }
                    // Prevent cycles: can't pick a descendant as parent
                    if ($this->categoryId && $value && $this->isDescendant((int) $value, $this->categoryId)) {
                        $fail('You cannot choose a descendant as the parent.');
                    }
                },
            ],
            'description' => 'nullable|string|max:5000',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ], [
            'slug.unique' => 'This slug is already used by another category.',
            'slug.regex' => 'Slug may only contain lowercase letters, numbers and dashes.',
        ]);

        if ($this->categoryId) {
            BlogCategory::where('id', $this->categoryId)->update($data);
            session()->flash('success', "Category '{$data['name']}' updated.");
        } else {
            BlogCategory::create($data);
            session()->flash('success', "Category '{$data['name']}' created.");
        }

        $this->redirectRoute('admin.blog.categories.index');
    }

    protected function isDescendant(int $candidateParentId, int $movingId): bool
    {
        $node = BlogCategory::find($candidateParentId);
        while ($node && $node->parent_id) {
            if ($node->parent_id === $movingId) {
                return true;
            }
            $node = $node->parent;
        }

        return false;
    }

    /**
     * Build the parent dropdown — every category except self and self's descendants.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function getParentOptionsProperty(): array
    {
        $excludeIds = [];
        if ($this->categoryId) {
            $excludeIds[] = $this->categoryId;
            $excludeIds = array_merge($excludeIds, $this->collectDescendantIds($this->categoryId));
        }

        $opts = [];
        $build = function (\Illuminate\Support\Collection $nodes, int $depth) use (&$opts, &$build, $excludeIds): void {
            foreach ($nodes as $n) {
                if (in_array($n->id, $excludeIds, true)) {
                    continue;
                }
                $opts[] = [
                    'id' => $n->id,
                    'label' => str_repeat('— ', $depth).$n->name,
                ];
                if ($n->children->isNotEmpty()) {
                    $build($n->children, $depth + 1);
                }
            }
        };

        $roots = BlogCategory::with('children.children.children')
            ->whereNull('parent_id')
            ->orderBy('order')->orderBy('name')->get();

        $build($roots, 0);

        return $opts;
    }

    /** @return array<int, int> */
    protected function collectDescendantIds(int $id): array
    {
        $ids = [];
        $stack = BlogCategory::where('parent_id', $id)->pluck('id')->all();
        while ($stack) {
            $next = array_shift($stack);
            $ids[] = $next;
            $stack = array_merge($stack, BlogCategory::where('parent_id', $next)->pluck('id')->all());
        }

        return $ids;
    }

    public function render()
    {
        return view('livewire.admin.blog.category-form')
            ->layout('layouts.admin-clean');
    }
}

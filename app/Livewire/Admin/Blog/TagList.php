<?php

namespace App\Livewire\Admin\Blog;

use App\Models\BlogTag;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TagList extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 50;

    /** @var array<int, int> */
    public array $perPageOptions = [50, 100, 300, 500];

    public ?int $editingId = null;

    public string $editName = '';

    /** @var array<string, array<int, string>|string> */
    protected $queryString = ['search' => ['except' => ''], 'perPage' => ['except' => 50]];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function startEdit(int $id): void
    {
        $tag = BlogTag::find($id);
        if (! $tag) {
            return;
        }
        $this->editingId = $tag->id;
        $this->editName = (string) $tag->name;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editName = '';
    }

    public function saveEdit(): void
    {
        if (! $this->editingId) {
            return;
        }
        $data = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($data['editName']);
        $slug = \Illuminate\Support\Str::slug($name);

        // Slug collision check (excluding the tag being edited)
        $exists = BlogTag::where('slug', $slug)
            ->where('id', '!=', $this->editingId)
            ->exists();
        if ($exists) {
            $this->addError('editName', 'Another tag already uses this name.');

            return;
        }

        BlogTag::where('id', $this->editingId)->update(['name' => $name, 'slug' => $slug]);
        session()->flash('success', "Tag renamed to '{$name}'.");
        $this->cancelEdit();
    }

    public function delete(int $id): void
    {
        $tag = BlogTag::find($id);
        if (! $tag) {
            return;
        }
        $name = $tag->name;
        $tag->blogs()->detach();
        $tag->delete();
        session()->flash('success', "Tag '{$name}' deleted.");
    }

    /** Resolve per-page to allowed values (defensive against query-string tampering). */
    protected function resolvedPerPage(): int
    {
        return in_array((int) $this->perPage, $this->perPageOptions, true)
            ? (int) $this->perPage
            : 50;
    }

    public function render()
    {
        $query = BlogTag::query()->withCount('blogs')->orderBy('name');
        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.admin.blog.tag-list', [
            'tags' => $query->paginate($this->resolvedPerPage()),
        ])->layout('layouts.admin-clean');
    }
}

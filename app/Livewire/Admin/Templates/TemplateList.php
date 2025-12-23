<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Template;
use App\Services\TemplateTableGenerator;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $template = Template::findOrFail($id);

        // Check if template is a system template
        if ($template->is_system) {
            session()->flash('error', 'Cannot delete system template.');
            return;
        }

        // Check if template has children
        if ($template->children()->count() > 0) {
            session()->flash('error', 'Cannot delete template that has child templates.');
            return;
        }

        // Check if template's table has any entries
        if ($template->table_name && $template->model_class) {
            $modelClass = "App\\Models\\{$template->model_class}";
            if (class_exists($modelClass)) {
                $count = $modelClass::count();
                if ($count > 0) {
                    session()->flash('error', "Cannot delete template that has {$count} entries.");
                    return;
                }
            }
        }

        // Drop database table and delete model
        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->dropTableAndModel($template);

        $template->delete();
        session()->flash('success', 'Template and database table deleted successfully!');
    }

    public function toggleActive($id)
    {
        $template = Template::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        session()->flash('success', 'Template status updated!');
    }

    public function render()
    {
        $templates = Template::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter);
            })
            ->with('parent', 'children')
            ->withCount('fields')
            ->orderBy('tree_level')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.templates.template-list', [
            'templates' => $templates
        ])->layout('layouts.admin-clean');
    }
}

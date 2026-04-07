<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Template;
use App\Services\TemplateExporter;
use App\Services\TemplateTableGenerator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $importFile;

    public $showImportModal = false;

    public $importOverwrite = false;

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
        $tableGenerator = new TemplateTableGenerator;
        $tableGenerator->dropTableAndModel($template);

        $template->delete();
        session()->flash('success', 'Template and database table deleted successfully!');
    }

    public function toggleActive($id)
    {
        $template = Template::findOrFail($id);
        $template->is_active = ! $template->is_active;
        $template->save();

        session()->flash('success', 'Template status updated!');
    }

    /**
     * Export all templates as JSON download.
     */
    public function exportAll(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exporter = new TemplateExporter;
        $data = $exporter->exportAll();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'templates-'.date('Y-m-d-His').'.json';

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

            $exporter = new TemplateExporter;
            $stats = $exporter->import($data, $this->importOverwrite);

            $this->showImportModal = false;

            session()->flash('success', "Import complete: {$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped.");
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function render()
    {
        $templates = Template::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
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
            'templates' => $templates,
        ])->layout('layouts.admin-clean');
    }
}

<?php

namespace App\Livewire\Admin\Forms;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class FormList extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteForm($id)
    {
        $form = Form::findOrFail($id);
        $form->delete();

        session()->flash('success', 'Form deleted successfully!');
    }

    public function duplicateForm($id)
    {
        $form = Form::with('fields')->findOrFail($id);

        // Create a new form with same attributes
        $newForm = $form->replicate();
        $newForm->name = $form->name . ' (Copy)';
        $newForm->slug = Str::slug($newForm->name);
        $newForm->is_active = false; // Set duplicate as inactive by default
        $newForm->save();

        // Duplicate all fields
        foreach ($form->fields as $field) {
            $newField = $field->replicate();
            $newField->form_id = $newForm->id;
            $newField->save();
        }

        session()->flash('success', 'Form duplicated successfully!');

        return redirect()->route('admin.forms.edit', $newForm->id);
    }

    public function toggleActive($id)
    {
        $form = Form::findOrFail($id);
        $form->is_active = !$form->is_active;
        $form->save();

        session()->flash('success', 'Form ' . ($form->is_active ? 'activated' : 'deactivated') . ' successfully!');
    }

    public function render()
    {
        $forms = Form::query()
            ->withCount('submissions')
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.forms.form-list', [
            'forms' => $forms,
        ])->layout('layouts.admin-clean');
    }
}

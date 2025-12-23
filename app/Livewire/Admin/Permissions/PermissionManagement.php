<?php

namespace App\Livewire\Admin\Permissions;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class PermissionManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingPermissionId = null;
    public $name = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function createPermission()
    {
        $this->reset(['editingPermissionId', 'name']);
        $this->showModal = true;
    }

    public function editPermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $this->editingPermissionId = $permission->id;
        $this->name = $permission->name;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->editingPermissionId,
        ]);

        if ($this->editingPermissionId) {
            $permission = Permission::findOrFail($this->editingPermissionId);
            $permission->name = $this->name;
            $permission->save();
            session()->flash('success', 'Permission updated successfully!');
        } else {
            Permission::create(['name' => $this->name]);
            session()->flash('success', 'Permission created successfully!');
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();
        session()->flash('success', 'Permission deleted successfully!');
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['editingPermissionId', 'name']);
    }

    public function render()
    {
        $permissions = Permission::query()
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.permissions.permission-management', [
            'permissions' => $permissions,
        ])->layout('layouts.admin-clean');
    }
}

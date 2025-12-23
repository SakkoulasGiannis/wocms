<?php

namespace App\Livewire\Admin\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class RoleManagement extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function createRole()
    {
        return redirect()->route('admin.roles.create');
    }

    public function editRole($roleId)
    {
        return redirect()->route('admin.roles.edit', $roleId);
    }

    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);

        // Prevent deleting system roles
        if (in_array($role->name, ['admin', 'manager', 'user'])) {
            session()->flash('error', 'Cannot delete system roles!');
            return;
        }

        $role->delete();
        session()->flash('success', 'Role deleted successfully!');
        $this->resetPage();
    }

    public function render()
    {
        $roles = Role::query()
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.roles.role-management', [
            'roles' => $roles,
        ])->layout('layouts.admin-clean');
    }
}

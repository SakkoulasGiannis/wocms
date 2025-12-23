<?php

namespace App\Livewire\Admin\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleEdit extends Component
{
    public $roleId;
    public $role;
    public $name = '';
    public $selectedPermissions = [];

    public function mount($roleId = null)
    {
        if ($roleId) {
            $this->roleId = $roleId;
            $this->role = Role::findOrFail($roleId);
            $this->name = $this->role->name;
            $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array',
        ]);

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->name = $this->name;
            $role->save();
            session()->flash('success', 'Role updated successfully!');
        } else {
            $role = Role::create(['name' => $this->name]);
            session()->flash('success', 'Role created successfully!');
        }

        $role->syncPermissions($this->selectedPermissions);

        return redirect()->route('admin.roles');
    }

    public function delete()
    {
        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);

            if (in_array($role->name, ['admin', 'manager', 'user'])) {
                session()->flash('error', 'Cannot delete system roles!');
                return;
            }

            $role->delete();
            session()->flash('success', 'Role deleted successfully!');
            return redirect()->route('admin.roles');
        }
    }

    public function render()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode(' ', $permission->name)[0];
        });

        return view('livewire.admin.roles.role-edit', [
            'permissions' => $permissions,
        ])->layout('layouts.admin-clean');
    }
}

<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserEdit extends Component
{
    public $userId;
    public $user;

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $selectedRoles = [];
    public $selectedPermissions = [];

    public function mount($userId = null)
    {
        if ($userId) {
            $this->userId = $userId;
            $this->user = User::findOrFail($userId);
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
            $this->selectedPermissions = $this->user->permissions->pluck('name')->toArray();
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'selectedRoles' => 'array',
            'selectedPermissions' => 'array',
        ];

        if ($this->userId) {
            // Editing - password is optional
            if ($this->password) {
                $rules['password'] = 'min:8|confirmed';
            }
        } else {
            // Creating - password is required
            $rules['password'] = 'required|min:8|confirmed';
        }

        $this->validate($rules);

        if ($this->userId) {
            // Update existing user
            $user = User::findOrFail($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;

            if ($this->password) {
                $user->password = Hash::make($this->password);
            }

            $user->save();
            session()->flash('success', 'User updated successfully!');
        } else {
            // Create new user
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            session()->flash('success', 'User created successfully!');
        }

        // Sync roles
        $user->syncRoles($this->selectedRoles);

        // Sync direct permissions
        $user->syncPermissions($this->selectedPermissions);

        return redirect()->route('admin.users');
    }

    public function delete()
    {
        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot delete yourself!');
                return;
            }

            $user->delete();
            session()->flash('success', 'User deleted successfully!');
            return redirect()->route('admin.users');
        }
    }

    public function render()
    {
        $roles = Role::all();
        $permissions = Permission::all()->groupBy(function($permission) {
            // Group by first word (e.g., "view", "create", "edit", "delete")
            return explode(' ', $permission->name)[0];
        });

        return view('livewire.admin.users.user-edit', [
            'roles' => $roles,
            'permissions' => $permissions,
        ])->layout('layouts.admin-clean');
    }
}

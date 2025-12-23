<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function mount()
    {
        //
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function createUser()
    {
        return redirect()->route('admin.users.create');
    }

    public function editUser($userId)
    {
        return redirect()->route('admin.users.edit', $userId);
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete yourself!');
            return;
        }

        $user->delete();
        session()->flash('success', 'User deleted successfully!');
        $this->resetPage();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.users.user-management', [
            'users' => $users,
        ])->layout('layouts.admin-clean');
    }
}

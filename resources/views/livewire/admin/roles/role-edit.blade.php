<div class="h-screen flex flex-col bg-white">
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.roles') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $roleId ? 'Edit Role' : 'Create Role' }}</h1>
                    @if($roleId)<p class="text-sm text-gray-500 mt-1">{{ $role->name }}</p>@endif
                </div>
            </div>
            <div class="flex items-center space-x-2">
                @if($roleId && !in_array($role->name, ['admin', 'manager', 'user']))
                    <button wire:click="delete" onclick="return confirm('Delete this role?')" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">Delete Role</button>
                @endif
                <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">{{ $roleId ? 'Update' : 'Create' }} Role</button>
            </div>
        </div>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        @if(session()->has('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Role Information</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                    <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., editor, contributor">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Permissions</h2>
                <p class="text-sm text-gray-600 mb-4">Select permissions for this role.</p>
                @forelse($permissions as $group => $groupPermissions)
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">{{ ucfirst($group) }} Permissions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($groupPermissions as $permission)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer transition hover:bg-gray-50">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No permissions available.</p>
                @endforelse
                @error('selectedPermissions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>

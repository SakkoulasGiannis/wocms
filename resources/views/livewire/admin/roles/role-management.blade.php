<div class="h-screen flex flex-col bg-white">
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions</h1>
                <div class="flex space-x-4 mt-2">
                    <a href="{{ route('admin.roles') }}" class="text-sm font-medium text-blue-600 border-b-2 border-blue-600 pb-2">Roles</a>
                    <a href="{{ route('admin.permissions') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 pb-2">Permissions</a>
                </div>
            </div>
            <button wire:click="createRole" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Add Role</button>
        </div>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        @if(session()->has('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase px-6 py-3">Role</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase px-6 py-3">Permissions</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase px-6 py-3">Users</th>
                        <th class="w-32 text-right text-xs font-semibold text-gray-600 uppercase px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900">{{ ucfirst($role->name) }}</span>
                                @if(in_array($role->name, ['admin', 'manager', 'user']))
                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $role->permissions->count() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $role->users->count() }}</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editRole({{ $role->id }})" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                                @if(!in_array($role->name, ['admin', 'manager', 'user']))
                                    <button wire:click="deleteRole({{ $role->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">No roles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $roles->links() }}</div>
    </div>
</div>

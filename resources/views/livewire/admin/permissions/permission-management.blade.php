<div class="h-screen flex flex-col bg-white">
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions</h1>
                <div class="flex space-x-4 mt-2">
                    <a href="{{ route('admin.roles') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 pb-2">Roles</a>
                    <a href="{{ route('admin.permissions') }}" class="text-sm font-medium text-blue-600 border-b-2 border-blue-600 pb-2">Permissions</a>
                </div>
            </div>
            <button wire:click="createPermission" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Add Permission</button>
        </div>
    </div>
    <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search permissions..." class="w-full max-w-md pl-9 pr-4 py-2 text-sm border-gray-300 rounded-lg">
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        @if(session()->has('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase px-6 py-3">Permission Name</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase px-6 py-3">Roles Using</th>
                        <th class="w-32 text-right text-xs font-semibold text-gray-600 uppercase px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($permissions as $permission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4"><span class="text-sm font-medium text-gray-900">{{ $permission->name }}</span></td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $permission->roles->count() }} roles</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editPermission({{ $permission->id }})" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                                <button wire:click="deletePermission({{ $permission->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-12 text-center text-gray-500">No permissions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $permissions->links() }}</div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $editingPermissionId ? 'Edit' : 'Create' }} Permission</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permission Name</label>
                    <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg" placeholder="e.g., manage products">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-2">Use lowercase with spaces (e.g., "view products", "create orders")</p>
                </div>
                <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-2">
                    <button wire:click="closeModal" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">{{ $editingPermissionId ? 'Update' : 'Create' }}</button>
                </div>
            </div>
        </div>
    @endif
</div>

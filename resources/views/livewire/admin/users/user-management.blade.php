<div class="h-screen flex flex-col bg-white">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage users, roles and permissions</p>
            </div>
            <button wire:click="createUser"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add User</span>
            </button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="flex-1 max-w-md">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search users..."
                       class="w-full pl-9 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6">
        @if(session()->has('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Name</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Email</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Roles</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Created</th>
                        <th class="w-32 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4">
                                @if($user->roles->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">No roles</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="editUser({{ $user->id }})"
                                            class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="deleteUser({{ $user->id }})"
                                            onclick="return confirm('Are you sure you want to delete this user?')"
                                            class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</div>

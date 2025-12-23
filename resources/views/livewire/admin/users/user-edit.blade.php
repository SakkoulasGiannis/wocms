<div class="h-screen flex flex-col bg-white">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.users') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $userId ? 'Edit User' : 'Create User' }}
                    </h1>
                    @if($userId)
                        <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-2">
                @if($userId)
                    <button wire:click="delete"
                            onclick="return confirm('Are you sure you want to delete this user?')"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Delete User
                    </button>
                @endif
                <button wire:click="save"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    {{ $userId ? 'Update User' : 'Create User' }}
                </button>
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

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text"
                               wire:model="name"
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email"
                               wire:model="email"
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password {{ $userId ? '(leave blank to keep current)' : '' }}
                        </label>
                        <input type="password"
                               wire:model="password"
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password"
                               wire:model="password_confirmation"
                               class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Roles -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Roles</h2>
                <p class="text-sm text-gray-600 mb-4">Assign roles to this user. Roles define sets of permissions.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @forelse($roles as $role)
                        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition
                                      {{ in_array($role->name, $selectedRoles) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="checkbox"
                                   wire:model="selectedRoles"
                                   value="{{ $role->name }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1">
                            <div class="ml-3">
                                <span class="block text-sm font-medium text-gray-900">{{ ucfirst($role->name) }}</span>
                                <span class="block text-xs text-gray-500 mt-1">
                                    {{ $role->permissions->count() }} permissions
                                </span>
                            </div>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">No roles available.</p>
                    @endforelse
                </div>
                @error('selectedRoles') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Direct Permissions -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Direct Permissions</h2>
                <p class="text-sm text-gray-600 mb-4">Assign specific permissions directly to this user (in addition to role permissions).</p>

                @forelse($permissions as $group => $groupPermissions)
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">{{ ucfirst($group) }} Permissions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($groupPermissions as $permission)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer transition hover:bg-gray-50">
                                    <input type="checkbox"
                                           wire:model="selectedPermissions"
                                           value="{{ $permission->name }}"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
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

            @if($userId)
                <!-- Current Permissions Summary -->
                <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
                    <h2 class="text-lg font-semibold text-blue-900 mb-4">Effective Permissions</h2>
                    <p class="text-sm text-blue-700 mb-4">
                        This user has the following permissions (from roles + direct permissions):
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->getAllPermissions() as $permission)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

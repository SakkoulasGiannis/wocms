<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Our Staff</h1>
            <p class="mt-1 text-sm text-gray-600">Manage the agents/staff members shown on the website</p>
        </div>
        <a href="{{ route('admin.agents.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-plus mr-2"></i>
            Add Agent
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- Search -->
    <div class="mb-4">
        <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa fa-search text-gray-400"></i>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search"
                   class="w-full pl-10 pr-3 py-2 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="Search by name, role, or email...">
        </div>
    </div>

    <!-- Agents Table -->
    @if($agents->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($agents as $agent)
                <tr wire:key="agent-{{ $agent->id }}" class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        @if($agent->hasPhoto())
                            <img src="{{ $agent->getThumbUrl() }}" alt="{{ $agent->name }}"
                                 class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                <i class="fa fa-user"></i>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $agent->name }}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            Slug: <code class="bg-gray-100 px-1 rounded">{{ $agent->slug }}</code>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-600">{{ $agent->role ?: '—' }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($agent->email)
                            <div class="text-gray-700"><i class="fa fa-envelope text-gray-400 mr-1 w-4"></i>{{ $agent->email }}</div>
                        @endif
                        @if($agent->phone)
                            <div class="text-gray-700"><i class="fa fa-phone text-gray-400 mr-1 w-4"></i>{{ $agent->phone }}</div>
                        @endif
                        @if(! $agent->email && ! $agent->phone)
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-600">{{ $agent->order }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <button wire:click="toggleActive({{ $agent->id }})"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $agent->active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                            @if($agent->active)
                                <i class="fa fa-check-circle mr-1"></i>Active
                            @else
                                <i class="fa fa-times-circle mr-1"></i>Inactive
                            @endif
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.agents.edit', $agent->id) }}"
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fa fa-edit mr-1"></i>Edit
                            </a>
                            <button wire:click="delete({{ $agent->id }})"
                                    wire:confirm="Are you sure you want to delete '{{ $agent->name }}'? This cannot be undone."
                                    class="text-red-600 hover:text-red-900">
                                <i class="fa fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-12 bg-white rounded-lg shadow">
        <i class="fa fa-users text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">
            @if($search)
                No agents match your search
            @else
                No agents yet
            @endif
        </p>
        <p class="text-gray-400 text-sm mt-2">
            @if($search)
                Try a different keyword
            @else
                Add your first staff member to get started
            @endif
        </p>
        @if(! $search)
        <a href="{{ route('admin.agents.create') }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fa fa-plus mr-2"></i>Add Agent
        </a>
        @endif
    </div>
    @endif
</div>

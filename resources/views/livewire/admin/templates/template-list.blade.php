@section('title', 'Templates')
@section('page-title', 'Templates')

<div>
    <!-- Actions Bar -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex-1 max-w-lg">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search templates..."
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <a href="{{ route('admin.templates.create') }}"
           class="ml-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Template
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Templates Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($templates->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Template
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Slug
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fields
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($templates as $template)
                        <tr wire:key="template-{{ $template->id }}" class="hover:bg-gray-50">
                            <!-- Template Name -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center" style="padding-left: {{ $template->tree_level * 20 }}px;">
                                    @if($template->tree_level > 0)
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $template->name }}
                                        </div>
                                        @if($template->parent)
                                            <div class="text-xs text-gray-500">
                                                Parent: {{ $template->parent->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Slug -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 font-mono">{{ $template->slug }}</span>
                            </td>

                            <!-- Fields Count -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $template->fields_count }}
                            </td>

                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    @if($template->requires_database)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                            Database
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                            </svg>
                                            Container
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActive({{ $template->id }})"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-full text-xs font-medium transition
                                               {{ $template->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                    <span class="w-2 h-2 mr-1.5 rounded-full {{ $template->is_active ? 'bg-green-600' : 'bg-gray-400' }}"></span>
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('admin.templates.edit', $template->id) }}"
                                       class="text-blue-600 hover:text-blue-900"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if(!$template->is_system)
                                        <button wire:click="delete({{ $template->id }})"
                                                wire:confirm="Are you sure? This will delete the template and all its fields."
                                                class="text-red-600 hover:text-red-900"
                                                title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @else
                                        <button disabled
                                                class="text-gray-300 cursor-not-allowed"
                                                title="System templates cannot be deleted">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            @if($templates->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $templates->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No templates</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new template.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.templates.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Template
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

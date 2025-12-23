<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Section Templates</h1>
            <p class="mt-1 text-sm text-gray-600">Manage reusable section templates for your pages</p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-plus mr-2"></i>
            Create Custom Template
        </button>
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

    <!-- Templates by Category -->
    @php
        $groupedTemplates = $templates->groupBy('category');
    @endphp

    @foreach($groupedTemplates as $category => $categoryTemplates)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <span class="bg-gray-100 px-3 py-1 rounded-full text-sm">
                    {{ $categories[$category] ?? ucfirst($category) }}
                </span>
                <span class="ml-2 text-gray-400 text-sm">({{ $categoryTemplates->count() }})</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categoryTemplates as $template)
                    <div class="bg-white rounded-lg shadow hover:shadow-md transition border border-gray-200">
                        <!-- Template Header -->
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 flex items-center">
                                        {{ $template->name }}
                                        @if($template->is_system)
                                            <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">System</span>
                                        @endif
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ $template->description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Template Fields -->
                        <div class="p-4 bg-gray-50">
                            <div class="text-xs text-gray-600 mb-2 font-medium">Fields:</div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($template->fields as $field)
                                    <span class="inline-flex items-center px-2 py-1 bg-white border border-gray-200 rounded text-xs text-gray-700">
                                        {{ $field->label }}
                                        <span class="ml-1 text-gray-400">({{ $field->type }})</span>
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400 italic">No fields defined</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Template Actions -->
                        <div class="p-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <!-- Active Toggle -->
                                <button wire:click="toggleActive({{ $template->id }})"
                                        class="text-sm {{ $template->is_active ? 'text-green-600' : 'text-gray-400' }} hover:text-green-700">
                                    @if($template->is_active)
                                        <i class="fa fa-check-circle mr-1"></i>Active
                                    @else
                                        <i class="fa fa-times-circle mr-1"></i>Inactive
                                    @endif
                                </button>

                                <!-- Delete Button (only for non-system templates) -->
                                @if(!$template->is_system)
                                    <button wire:click="delete({{ $template->id }})"
                                            wire:confirm="Are you sure you want to delete '{{ $template->name }}'?"
                                            class="text-sm text-red-600 hover:text-red-700">
                                        <i class="fa fa-trash mr-1"></i>Delete
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Protected</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if($templates->isEmpty())
        <div class="text-center py-12">
            <i class="fa fa-cube text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No section templates found</p>
            <p class="text-gray-400 text-sm mt-2">Run the seeder or create a custom template</p>
        </div>
    @endif

    <!-- Create Template Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeCreateModal">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Create Custom Section Template</h2>
            </div>

            <div class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select wire:model="category" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- HTML Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HTML Template *</label>
                    <p class="text-xs text-gray-500 mb-2">Use &#123;&#123;variable&#125;&#125; for placeholders. Example: &#123;&#123;heading&#125;&#125;, &#123;&#123;image&#125;&#125;, etc.</p>
                    <textarea wire:model="html_template" rows="10" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"></textarea>
                    @error('html_template') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button wire:click="closeCreateModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                    Cancel
                </button>
                <button wire:click="createTemplate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    Create Template
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

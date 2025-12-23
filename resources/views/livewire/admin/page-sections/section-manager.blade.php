<div class="container mx-auto px-4 py-8">
    <!-- Back to Pages Button -->
    <div class="mb-4">
        <a href="{{ route('admin.page-sections.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Πίσω στη λίστα σελίδων
        </a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Page Sections - {{ ucfirst($pageType) }}</h1>
        <button wire:click="openAddModal" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            + Add Section
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Sections List -->
    <div class="space-y-6">
        @forelse($sections as $section)
            <div class="bg-white shadow-md rounded-lg overflow-hidden border-2 transition-all
                        {{ $selectedSectionId === $section->id ? 'border-blue-500 ring-2 ring-blue-200' : ($section->is_active ? 'border-gray-200' : 'border-red-300') }}"
                 wire:click="selectSection({{ $section->id }})"
                 style="cursor: pointer;">

                <!-- Section Header -->
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold">{{ $section->name }}</h3>
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded">
                            {{ $availableSectionTypes[$section->section_type]['name'] ?? $section->section_type }}
                        </span>
                        @if(!$section->is_active)
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Inactive</span>
                        @endif
                        @if($selectedSectionId === $section->id)
                            <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">✓ Selected for AI</span>
                        @endif
                    </div>

                    <div class="flex gap-2" onclick="event.stopPropagation()">
                        <!-- Move Up/Down -->
                        <button wire:click="moveUp({{ $section->id }})"
                                class="text-gray-600 hover:text-gray-800 p-1 hover:bg-gray-200 rounded"
                                title="Move Up">
                            ↑
                        </button>
                        <button wire:click="moveDown({{ $section->id }})"
                                class="text-gray-600 hover:text-gray-800 p-1 hover:bg-gray-200 rounded"
                                title="Move Down">
                            ↓
                        </button>

                        <!-- Toggle JSON View -->
                        <button wire:click="toggleJson({{ $section->id }})"
                                class="px-3 py-1 rounded text-xs {{ in_array($section->id, $showJsonFor) ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700' }} hover:bg-purple-200"
                                title="Toggle JSON View">
                            {{ in_array($section->id, $showJsonFor) ? 'Hide' : 'Show' }} JSON
                        </button>

                        <!-- Toggle Active -->
                        <button wire:click="toggleActive({{ $section->id }})"
                                class="px-3 py-1 rounded text-xs {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                            {{ $section->is_active ? 'Active' : 'Inactive' }}
                        </button>

                        <!-- Edit with AI -->
                        <a href="{{ route('admin.page-sections.edit', ['sectionId' => $section->id]) }}"
                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Edit
                        </a>

                        <!-- Quick Edit -->
                        <button wire:click="editSection({{ $section->id }})"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs">
                            Quick Edit
                        </button>

                        <!-- Delete -->
                        <button wire:click="deleteSection({{ $section->id }})"
                                onclick="return confirm('Are you sure?')"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Section Content -->
                <div class="p-4">
                    @if(in_array($section->id, $showJsonFor))
                        <!-- JSON View -->
                        <div class="bg-gray-900 text-gray-100 p-4 rounded font-mono text-xs overflow-x-auto">
                            <pre>{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @else
                        <!-- HTML Preview (Rendered Section) -->
                        <div class="border-2 border-dashed border-gray-300 rounded p-2">
                            <div class="text-xs text-gray-500 mb-2 font-semibold">Preview:</div>
                            <div class="bg-white">
                                @php
                                    $componentName = 'sections.' . str_replace('_', '-', $section->section_type);
                                    $renderError = null;

                                    try {
                                        $componentView = view()->make('components.' . str_replace('.', '/', $componentName), [
                                            'content' => $section->content,
                                            'settings' => $section->settings
                                        ]);
                                        echo $componentView->render();
                                    } catch (\Throwable $e) {
                                        $renderError = $e->getMessage();
                                    }
                                @endphp

                                @if($renderError)
                                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded text-sm">
                                        <strong>Error rendering section:</strong> {{ $renderError }}
                                        <div class="mt-2 text-xs">
                                            Component: {{ $componentName }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <p class="text-gray-600">No sections found. Click "Add Section" to create your first section.</p>
            </div>
        @endforelse
    </div>

    <!-- Add/Edit Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold">{{ $editingSection ? 'Edit Section' : 'Add New Section' }}</h2>
                </div>

                <div class="p-6">
                    @if(!$selectedSectionType)
                        <!-- Section Type Selection -->
                        <h3 class="text-lg font-semibold mb-4">Select Section Type</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($availableSectionTypes as $type => $info)
                                <button wire:click="selectSectionType('{{ $type }}')"
                                        class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 text-left transition">
                                    <h4 class="font-semibold mb-1">{{ $info['name'] }}</h4>
                                    <p class="text-sm text-gray-600">{{ $info['description'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <!-- Section Form -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Section Name (Optional)</label>
                                <input type="text"
                                       wire:model="sectionName"
                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                       placeholder="{{ $availableSectionTypes[$selectedSectionType]['name'] }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Content (JSON)</label>
                                <textarea wire:model="sectionContent"
                                          class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"
                                          rows="10">{{ json_encode($sectionContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                <p class="text-sm text-gray-500 mt-1">Edit the JSON content for this section</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Settings (JSON)</label>
                                <textarea wire:model="sectionSettings"
                                          class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"
                                          rows="5">{{ json_encode($sectionSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                <p class="text-sm text-gray-500 mt-1">Configure section-specific settings</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-6 border-t flex justify-end gap-2">
                    <button wire:click="$set('showAddModal', false)"
                            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">
                        Cancel
                    </button>
                    @if($selectedSectionType)
                        <button wire:click="{{ $editingSection ? 'updateSection' : 'saveSection' }}"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
                            {{ $editingSection ? 'Update' : 'Add' }} Section
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

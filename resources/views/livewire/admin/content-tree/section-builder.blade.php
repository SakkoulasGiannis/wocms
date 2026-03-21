<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $node->title ?? 'Page' }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ $node->template->name ?? 'No template' }}</p>
            </div>
            <button wire:click="addSection"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Section
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Sections List -->
    <div class="p-6 max-h-[calc(100vh-320px)] overflow-y-auto">
        @if(count($sections) > 0)
            <div class="space-y-4" id="sections-container">
                @foreach($sections as $section)
                    @php
                        $isExpanded = in_array($section['id'], $expandedSections);
                        $content = json_decode($section['content'], true) ?? [];
                        $sectionTemplate = $section['section_template'] ?? null;
                    @endphp

                    <div class="border border-gray-200 rounded-lg overflow-hidden {{ !$section['is_active'] ? 'opacity-50' : '' }}"
                         data-section-id="{{ $section['id'] }}">
                        <!-- Section Header -->
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3 flex-1">
                                    <!-- Drag Handle -->
                                    <button class="cursor-move text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                    </button>

                                    <!-- Expand/Collapse -->
                                    <button wire:click="toggleSection({{ $section['id'] }})"
                                            class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5 transform {{ $isExpanded ? 'rotate-90' : '' }} transition-transform"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>

                                    <!-- Section Info -->
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">{{ $section['name'] }}</h4>
                                        @if($sectionTemplate)
                                            <p class="text-xs text-gray-500">{{ $sectionTemplate['category'] ?? 'General' }}</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <!-- Visibility Toggle -->
                                    <button wire:click="toggleSectionVisibility({{ $section['id'] }})"
                                            class="text-gray-400 hover:text-gray-600 p-1"
                                            title="{{ $section['is_active'] ? 'Hide' : 'Show' }}">
                                        @if($section['is_active'])
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        @endif
                                    </button>

                                    <!-- Delete -->
                                    <button wire:click="deleteSection({{ $section['id'] }})"
                                            wire:confirm="Are you sure you want to delete this section?"
                                            class="text-gray-400 hover:text-red-600 p-1"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Section Content (Collapsed) -->
                        @if($isExpanded)
                            <div class="p-4 bg-white">
                                @if($sectionTemplate && isset($sectionTemplate['fields']))
                                    <div class="space-y-4">
                                        @foreach($sectionTemplate['fields'] as $field)
                                            @php
                                                $fieldName = $field['name'];
                                                $fieldValue = $content[$fieldName] ?? '';
                                                $fieldType = $field['type'] ?? 'text';
                                            @endphp

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    {{ $field['label'] ?? ucfirst($fieldName) }}
                                                </label>

                                                @if($fieldType === 'text')
                                                    <input type="text"
                                                           wire:model.blur="sections.{{ $loop->parent->index }}.content.{{ $fieldName }}"
                                                           wire:change="updateItemContent({{ $section['id'] }}, '{{ $fieldName }}', $event.target.value)"
                                                           value="{{ $fieldValue }}"
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                                                @elseif($fieldType === 'textarea')
                                                    <textarea wire:model.blur="sections.{{ $loop->parent->index }}.content.{{ $fieldName }}"
                                                              wire:change="updateItemContent({{ $section['id'] }}, '{{ $fieldName }}', $event.target.value)"
                                                              rows="3"
                                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $fieldValue }}</textarea>

                                                @elseif($fieldType === 'repeater')
                                                    <div class="space-y-2">
                                                        @php
                                                            $items = is_array($fieldValue) ? $fieldValue : [];
                                                        @endphp

                                                        @foreach($items as $itemIndex => $item)
                                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <div class="flex items-start justify-between">
                                                                    <div class="flex-1 space-y-2">
                                                                        @if(isset($field['sub_fields']))
                                                                            @foreach($field['sub_fields'] as $subField)
                                                                                @php
                                                                                    $subFieldName = $subField['name'];
                                                                                    $subFieldValue = $item[$subFieldName] ?? '';
                                                                                @endphp
                                                                                <div>
                                                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                                                        {{ $subField['label'] ?? ucfirst($subFieldName) }}
                                                                                    </label>
                                                                                    <input type="text"
                                                                                           value="{{ $subFieldValue }}"
                                                                                           wire:change="updateItemContent({{ $section['id'] }}, '{{ $fieldName }}.{{ $itemIndex }}.{{ $subFieldName }}', $event.target.value)"
                                                                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                                                                </div>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                    <button class="ml-2 text-gray-400 hover:text-red-600">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach

                                                        <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                            + Add Item
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">No fields defined for this section.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No sections yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding a section to this page.</p>
                <button wire:click="addSection"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Section
                </button>
            </div>
        @endif
    </div>

    <!-- Add Section Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
             wire:click="$set('showAddModal', false)">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden"
                 wire:click.stop>
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Section</h3>
                </div>

                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    @if(count($availableSectionTemplates) > 0)
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($availableSectionTemplates as $template)
                                <button wire:click="createSection({{ $template['id'] }})"
                                        class="text-left p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $template['name'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $template['description'] ?? '' }}</p>
                                    <span class="inline-block mt-2 px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">
                                        {{ $template['category'] ?? 'General' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No section templates available.</p>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button wire:click="$set('showAddModal', false)"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

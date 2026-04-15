@php
    $containers = ['primitive_div', 'primitive_grid', 'primitive_section'];
    $isContainer = in_array($section['section_type'], $containers);
    $indent = ($depth ?? 0) * 12;
    $children = $allSections->where('parent_section_id', $section['id'])->sortBy('order')->values();
@endphp

<div wire:key="ve-section-{{ $section['id'] }}"
     class="ve-section-item"
     data-id="{{ $section['id'] }}">

    {{-- Row --}}
    @php $isHiddenSection = isset($section['is_visible']) && !$section['is_visible']; @endphp
    <div class="group flex items-center gap-1.5 pr-2 py-2 cursor-pointer border-l-4 transition-all
                {{ $isHiddenSection ? 'opacity-50' : '' }}
                {{ $selectedSectionId === $section['id'] ? 'border-purple-500 bg-purple-50' : 'border-transparent hover:bg-gray-50 hover:border-gray-300' }}"
         style="padding-left: {{ 12 + $indent }}px"
         wire:click="selectSection({{ $section['id'] }})">

        {{-- Drag handle --}}
        <div class="ve-drag-handle cursor-grab text-gray-300 group-hover:text-gray-500 flex-shrink-0">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
            </svg>
        </div>

        {{-- Container icon --}}
        @if($isContainer)
            <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        @else
            <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        @endif

        {{-- Label --}}
        <div class="flex-1 min-w-0">
            <div class="text-xs font-medium text-gray-800 truncate leading-tight">
                {{ $section['name'] ?: 'Section' }}
            </div>
            <div class="text-[10px] text-gray-400 truncate leading-tight">{{ str_replace('primitive_', '', $section['section_type']) }}</div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-0.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
             onclick="event.stopPropagation()">
            {{-- Visibility toggle --}}
            <button wire:click.stop="toggleVisibility({{ $section['id'] }})"
                    title="{{ $isHiddenSection ? 'Show section' : 'Hide section' }}"
                    class="p-1 rounded hover:bg-gray-200 {{ $isHiddenSection ? 'text-gray-300' : 'text-blue-400' }}">
                @if($isHiddenSection)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                @else
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                @endif
            </button>
            {{-- Add child (containers only) --}}
            @if($isContainer)
                <button wire:click.stop="openAddPanelForChild({{ $section['id'] }})"
                        title="Add child section"
                        class="p-1 rounded hover:bg-gray-200 text-gray-300 hover:text-green-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
            @endif
            {{-- Duplicate --}}
            <button wire:click.stop="duplicateSection({{ $section['id'] }})"
                    title="Duplicate section"
                    class="p-1 rounded hover:bg-gray-200 text-gray-300 hover:text-gray-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>
            {{-- Delete --}}
            <button wire:click.stop="deleteSection({{ $section['id'] }})"
                    onclick="return confirm('Delete this section?')"
                    class="p-1 rounded hover:bg-red-100 text-gray-300 hover:text-red-500">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Children drop zone (always rendered for containers) --}}
    @if($isContainer)
        <div class="ve-children-list"
             id="ve-children-{{ $section['id'] }}"
             data-container="{{ $section['id'] }}"
             style="margin-left: {{ 12 + $indent }}px; min-height: 28px; border-left: 2px dashed #e5e7eb; margin-bottom: 2px;">
            @foreach($children as $child)
                @include('livewire.admin.page-sections.partials.section-tree-item', [
                    'section' => is_array($child) ? $child : $child->toArray(),
                    'allSections' => $allSections,
                    'depth' => ($depth ?? 0) + 1,
                    'selectedSectionId' => $selectedSectionId,
                ])
            @endforeach
        </div>
    @endif
</div>

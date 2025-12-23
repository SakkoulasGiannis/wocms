@php
    $hasChildren = $node->children->count() > 0;
    $isExpanded = in_array($node->id, $expandedNodes);
    $indent = $level * 24;
@endphp

<div class="tree-node">
    <!-- Node Row -->
    <div class="flex items-center py-2 px-3 hover:bg-gray-50 rounded-lg group"
         style="padding-left: {{ $indent }}px">

        <!-- Expand/Collapse Button -->
        @if($hasChildren)
            <button wire:click="toggleNode({{ $node->id }})"
                    class="mr-2 text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4 transform {{ $isExpanded ? 'rotate-90' : '' }} transition-transform"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            <span class="mr-2 w-4"></span>
        @endif

        <!-- Node Icon -->
        <div class="mr-3">
            @if($node->template)
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $node->template->menu_icon ?? 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }}"/>
                </svg>
            @else
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            @endif
        </div>

        <!-- Node Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-900 truncate">{{ $node->title }}</span>

                @if(!$node->is_published)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        Draft
                    </span>
                @endif

                @if($node->template)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $node->template->name }}
                    </span>
                @endif
            </div>

            <div class="text-xs text-gray-500 truncate mt-0.5">
                {{ $node->url_path }}
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <!-- View -->
            <a href="{{ $node->url_path }}"
               target="_blank"
               class="text-gray-400 hover:text-blue-600 transition"
               title="View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </a>

            <!-- Edit -->
            @if($node->content_type && $node->content_id && $node->template)
                <a href="{{ route('admin.template-entries.edit', [$node->template->slug, $node->content_id]) }}"
                   class="text-gray-400 hover:text-blue-600 transition"
                   title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            @endif

            <!-- Delete -->
            <button wire:click="deleteNode({{ $node->id }})"
                    wire:confirm="Are you sure you want to delete '{{ $node->title }}' and all its children?"
                    class="text-gray-400 hover:text-red-600 transition"
                    title="Delete">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Children -->
    @if($hasChildren && $isExpanded)
        <div class="mt-1">
            @foreach($node->children()->orderBy('sort_order')->orderBy('title')->get() as $childNode)
                @include('livewire.admin.content-tree.partials.tree-node', ['node' => $childNode, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

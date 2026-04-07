@php
    // Get content nodes for this template
    $templateNodes = \App\Models\ContentNode::where('template_id', $template->id)
        ->whereNull('parent_id')
        ->with('template')
        ->orderBy('sort_order')
        ->orderBy('title')
        ->get();

    $hasNodes = $templateNodes->count() > 0;
    $isExpanded = in_array('template_' . $template->id, $expandedNodes);
@endphp

<div class="template-group">
    <!-- Template Header -->
    <div class="flex items-center py-2 px-2 hover:bg-gray-50 rounded group transition-colors">
        <!-- Expand/Collapse Button -->
        @if($hasNodes)
            <button wire:click="toggleNode('template_{{ $template->id }}')"
                    class="mr-1.5 text-gray-400 hover:text-gray-600 transition shrink-0">
                <svg class="w-3.5 h-3.5 transform {{ $isExpanded ? 'rotate-90' : '' }} transition-transform"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            <span class="mr-1.5 w-3.5"></span>
        @endif

        <!-- Template Icon -->
        <div class="mr-2 shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>

        <!-- Template Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-semibold text-gray-900">{{ $template->name }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $templateNodes->count() }} {{ $templateNodes->count() === 1 ? 'page' : 'pages' }}
                </span>
            </div>
        </div>

        <!-- Template Actions -->
        <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            <!-- Add New Page -->
            <a href="/admin/{{ $template->slug }}/create"
               wire:click.stop
               class="text-gray-400 hover:text-green-600 transition p-1"
               title="Add new {{ $template->name }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </a>

            <!-- Edit Template -->
            <a href="/admin/templates/{{ $template->id }}/edit"
               wire:click.stop
               class="text-gray-400 hover:text-blue-600 transition p-1"
               title="Edit template">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Template Pages (Children) -->
    @if($hasNodes && $isExpanded)
        <div class="ml-2 mt-0.5 border-l-2 border-gray-200 pl-2">
            @foreach($templateNodes as $node)
                @include('livewire.admin.content-tree.partials.tree-node', ['node' => $node, 'level' => 0])
            @endforeach
        </div>
    @endif
</div>

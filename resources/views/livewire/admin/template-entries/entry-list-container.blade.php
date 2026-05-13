@section('page-title', $template->menu_label ?: $template->name)

<div>
    <!-- Actions Bar -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex-1 max-w-md">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search {{ Str::lower($template->menu_label ?: $template->name) }}..."
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
        </div>

        <!-- Child Template Buttons -->
        @php
            $childTemplates = \App\Models\Template::whereIn('id', $template->allowed_child_templates ?? [])
                ->where('is_active', true)
                ->get();
        @endphp

        <div class="flex items-center space-x-2">
            {{-- Template-level design button: opens visual editor scoped to the Template (not a specific entry).
                 Sections you add here become the SHARED layout for every entry of this template
                 (Phase A: just opens the editor — frontend rendering wiring lands in Phase D). --}}
            @if($template->requires_database && $template->is_active)
                <a href="{{ url('/admin/page-sections/visual/App-Models-Template/' . $template->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                   title="Design the shared layout used by every {{ Str::singular($template->name) }} entry">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                    </svg>
                    Design layout
                </a>
            @endif

            @if($childTemplates->count() > 0)
                @foreach($childTemplates as $childTemplate)
                    <a href="{{ route('admin.template-entries.create', $childTemplate->slug) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New {{ Str::singular($childTemplate->name) }}
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    @php $sortable = $sortable ?? false; @endphp

    <!-- Children Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($children->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @if($sortable)
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                        @endif
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Title
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Template
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" @if($sortable) id="entries-sortable-body" data-sortable-mode="flat" @endif>
                    @foreach($children as $child)
                        <tr class="hover:bg-gray-50"
                            @if($sortable) data-entry-id="{{ $child->id }}" data-parent-id="{{ $child->parent_id ?? 0 }}" @endif>
                            @if($sortable)
                                <td class="px-3 py-4 text-center">
                                    <button type="button"
                                            class="entry-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-700 select-none"
                                            title="Drag to reorder">
                                        <svg class="w-5 h-5 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                        </svg>
                                    </button>
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $child->title }}
                                        </div>
                                        @if(!$child->is_published)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                Draft
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $child->template->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="{{ $child->url_path }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ $child->url_path }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $child->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($child->content_type && $child->content_id)
                                    <a href="{{ route('admin.template-entries.edit', [$child->template->slug, $child->content_id]) }}"
                                       class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                @endif
                                <a href="{{ $child->url_path }}" target="_blank"
                                   class="text-green-600 hover:text-green-900 mr-3">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $children->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No child content yet</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Child templates will appear here when created.
                </p>
            </div>
        @endif
    </div>

    @if($sortable && $children->count() > 0)
        {{-- SortableJS drag-to-reorder --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            (function() {
                function initSortable() {
                    const tbody = document.getElementById('entries-sortable-body');
                    if (!tbody || tbody._sortableInit) return;
                    tbody._sortableInit = true;

                    new Sortable(tbody, {
                        handle: '.entry-drag-handle',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        chosenClass: 'bg-blue-50',
                        onEnd: function () {
                            const orderedIds = Array.from(tbody.querySelectorAll('tr[data-entry-id]'))
                                .map(tr => parseInt(tr.dataset.entryId));
                            @this.call('reorder', orderedIds);
                        },
                    });
                }
                initSortable();
                document.addEventListener('livewire:navigated', initSortable);
                if (window.Livewire) {
                    Livewire.hook('morph.updated', () => {
                        const tbody = document.getElementById('entries-sortable-body');
                        if (tbody) tbody._sortableInit = false;
                        initSortable();
                    });
                }
            })();
        </script>
    @endif
</div>

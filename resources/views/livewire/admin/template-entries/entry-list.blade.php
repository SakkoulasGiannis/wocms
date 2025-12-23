@section('page-title', $template->menu_label ?: $template->name)

<div>
    <!-- Actions Bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            @if($template->fields->where('is_searchable', true)->count() > 0)
                <div class="flex-1 max-w-md">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search {{ Str::lower($template->menu_label ?: $template->name) }}..."
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                </div>
            @else
                <div></div>
            @endif
            @if($template->allow_new_pages)
                <a href="{{ route('admin.template-entries.create', $template->slug) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New {{ Str::singular($template->name) }}
                </a>
            @else
                <span class="text-sm text-gray-500 italic">Only one {{ Str::lower($template->name) }} entry allowed</span>
            @endif
        </div>

        @php
            $filterableFields = $template->fields->where('is_filterable', true);
        @endphp
        @if($filterableFields->count() > 0)
            <div class="flex gap-3 flex-wrap">
                @foreach($filterableFields as $field)
                    <div class="flex-1 min-w-[200px] max-w-xs">
                        <label for="filter_{{ $field->name }}" class="block text-xs font-medium text-gray-700 mb-1">
                            Filter by {{ $field->label }}
                        </label>
                        <input type="text"
                               id="filter_{{ $field->name }}"
                               wire:model.live.debounce.300ms="filters.{{ $field->name }}"
                               placeholder="Filter {{ Str::lower($field->label) }}..."
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border text-sm">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Entries Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($entries->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        @foreach($template->fields->where('show_in_table', true) as $field)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $field->label }}
                            </th>
                        @endforeach
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($entries as $item)
                        @php
                            // For tree view, $item is a ContentNode with 'content' property
                            // For flat view, $item is the entry itself
                            $entry = isset($isTree) && $isTree ? $item->content : $item;
                            $level = isset($isTree) && $isTree ? ($item->level ?? 0) : 0;
                        @endphp
                        @if($entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($level > 0)
                                    <span class="text-gray-400">
                                        @for($i = 0; $i < $level; $i++)
                                            <span class="inline-block" style="width: 20px;">│</span>
                                        @endfor
                                        <span class="inline-block" style="width: 20px;">└</span>
                                    </span>
                                @endif
                                #{{ $entry->id }}
                            </td>
                            @foreach($template->fields->where('show_in_table', true) as $field)
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if(in_array($field->type, ['image', 'gallery']))
                                        @if(method_exists($entry, 'getFirstMediaUrl') && $entry->getFirstMediaUrl($field->name))
                                            <img src="{{ $entry->getFirstMediaUrl($field->name) }}"
                                                 alt="{{ $field->label }}"
                                                 class="h-10 w-10 rounded object-cover">
                                        @elseif($entry->{$field->name})
                                            <img src="{{ asset('storage/' . (is_array($entry->{$field->name}) ? $entry->{$field->name}[0] : $entry->{$field->name})) }}"
                                                 alt="{{ $field->label }}"
                                                 class="h-10 w-10 rounded object-cover">
                                        @endif
                                    @elseif(in_array($field->type, ['wysiwyg', 'textarea']))
                                        <div class="max-w-xs truncate">{{ strip_tags($entry->{$field->name}) }}</div>
                                    @elseif($field->type === 'boolean')
                                        @if($entry->{$field->name})
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Yes
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                No
                                            </span>
                                        @endif
                                    @else
                                        <div class="max-w-xs truncate">{{ $entry->{$field->name} }}</div>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $entry->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @php
                                    $viewUrl = null;
                                    $urlIdentifierField = $template->fields->where('is_url_identifier', true)->first();

                                    // Check if there's a URL identifier field
                                    if($urlIdentifierField && isset($entry->{$urlIdentifierField->name})) {
                                        $urlValue = $entry->{$urlIdentifierField->name};

                                        // Special case for Home template
                                        if($template->slug === 'home') {
                                            $viewUrl = '/';
                                        }
                                        // For templates with physical files, use the URL identifier field value
                                        elseif($template->has_physical_file) {
                                            // Check if entry is linked to a ContentNode
                                            if(class_exists('App\Models\ContentNode')) {
                                                $contentType = 'App\\Models\\' . Str::studly(Str::singular($template->slug));
                                                $node = \App\Models\ContentNode::where('content_type', $contentType)
                                                    ->where('content_id', $entry->id)
                                                    ->where('is_published', true)
                                                    ->first();
                                                if($node) {
                                                    $viewUrl = $node->url_path;
                                                }
                                            }
                                        }
                                        // For simple templates without ContentNode, construct URL from identifier
                                        elseif($urlValue) {
                                            $viewUrl = '/' . $template->slug . '/' . $urlValue;
                                        }
                                    }
                                @endphp

                                @if($viewUrl)
                                    <a href="{{ $viewUrl }}"
                                       target="_blank"
                                       class="text-green-600 hover:text-green-900 mr-3"
                                       title="View on Frontend">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                @endif

                                <a href="{{ route('admin.template-entries.edit', [$template->slug, $entry->id]) }}"
                                   class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <button wire:click="deleteEntry({{ $entry->id }})"
                                        wire:confirm="Are you sure you want to delete this {{ Str::lower(Str::singular($template->name)) }}?"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            @if(!isset($isTree) || !$isTree)
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $entries->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No {{ Str::lower($template->menu_label ?: $template->name) }}</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new {{ Str::lower(Str::singular($template->name)) }}.</p>
                @if($template->allow_new_pages)
                    <div class="mt-6">
                        <a href="{{ route('admin.template-entries.create', $template->slug) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            New {{ Str::singular($template->name) }}
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

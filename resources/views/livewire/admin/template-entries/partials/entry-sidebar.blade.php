{{-- Right Sidebar with Settings --}}
<div class="space-y-4">

    {{-- Hierarchy --}}
    @if($template->allow_children)
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                Hierarchy
            </h3>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">
                    Parent {{ Str::singular($template->name) }}
                </label>
                <select onchange="@this.set('parentNodeId', this.value)"
                        class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                    <option value="" {{ empty($this->parentNodeId) ? 'selected' : '' }}>-- Top Level --</option>
                    @foreach($availableParentNodes as $parentNode)
                        <option value="{{ $parentNode->id }}" {{ $this->parentNodeId == $parentNode->id ? 'selected' : '' }}>
                            @for($i = 0; $i < $parentNode->level; $i++)
                                &nbsp;&nbsp;
                            @endfor
                            {{ $parentNode->level > 0 ? '↳ ' : '' }}{{ $parentNode->title }}
                        </option>
                    @endforeach
                </select>
                @error('parentNodeId')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    {{-- Status --}}
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status
        </h3>
        <div>
            <select onchange="@this.set('status', this.value)"
                    value="{{ $status }}"
                    class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="disabled" {{ $status === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">
                Controls visibility on the frontend
            </p>
            @error('status')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Created At --}}
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Created Date
        </h3>
        <div>
            <input type="datetime-local"
                   value="{{ $this->createdAt ?? '' }}"
                   onchange="@this.set('createdAt', this.value)"
                   class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
            <p class="mt-1 text-xs text-gray-500">
                @if($entryId && $entry)
                    Original: {{ $entry->created_at?->format('M d, Y H:i') }}
                @else
                    Will be set on save
                @endif
            </p>
        </div>
    </div>

    {{-- Status Info (if editing) --}}
    @if($entryId && $entry)
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Info
            </h3>
            <div class="space-y-2 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span class="font-medium">ID:</span>
                    <span class="font-mono">{{ $entry->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Updated:</span>
                    <span>{{ $entry->updated_at?->diffForHumans() }}</span>
                </div>
                @if($entry->deleted_at)
                    <div class="flex justify-between text-red-600">
                        <span class="font-medium">Deleted:</span>
                        <span>{{ $entry->deleted_at?->diffForHumans() }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

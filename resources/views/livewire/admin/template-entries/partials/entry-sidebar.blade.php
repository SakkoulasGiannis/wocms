{{-- Right Sidebar with Settings --}}
<div class="space-y-4">

    {{-- AI Content Assistant --}}
    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg shadow-md p-4 border border-purple-200">
        <div x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between text-sm font-semibold text-purple-900 hover:text-purple-700 transition-colors"
            >
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    🤖 AI Assistant
                </div>
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="open"
                x-collapse
                class="mt-3 space-y-3"
            >
                <p class="text-xs text-gray-600">
                    Ask AI to improve your content. Examples: "Improve the title", "Fix grammar in Body", "Make description more engaging"
                </p>

                <div>
                    <textarea
                        id="ai-prompt-input"
                        placeholder="e.g., 'Make the title more compelling' or 'Fix grammar in all fields'"
                        rows="3"
                        class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border"
                    ></textarea>
                </div>

                <button
                    type="button"
                    id="ai-improve-btn"
                    onclick="improveContentWithAI()"
                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <svg id="ai-improve-icon-default" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg id="ai-improve-icon-loading" class="hidden animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="ai-improve-btn-text">Improve Content</span>
                </button>

                <div id="ai-improve-result" class="hidden">
                    <div class="p-3 bg-white rounded-lg border border-purple-200">
                        <p class="text-xs font-medium text-purple-900 mb-1">AI Response:</p>
                        <p id="ai-improve-message" class="text-xs text-gray-700"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

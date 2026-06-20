<div class="p-6 max-w-6xl mx-auto">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 0 0-3.09 3.091ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                </svg>
                AI Page Builder
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Tell the AI what page to build or what to change on an existing one — it will produce the JSON and compile it into the database.
            </p>
        </div>
        <a href="{{ route('admin.settings') }}#ai-prompts"
           class="text-xs text-gray-500 hover:text-gray-700 underline">
            ⚙ Edit prompts
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-6">
            <button type="button" wire:click="setTab('create')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition
                           {{ $activeTab === 'create' ? 'border-purple-600 text-purple-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                ✨ Build a new page
            </button>
            <button type="button" wire:click="setTab('edit')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition
                           {{ $activeTab === 'edit' ? 'border-purple-600 text-purple-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                ✏️ Edit an existing page
            </button>
        </nav>
    </div>

    {{-- CREATE TAB --}}
    @if ($activeTab === 'create')
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tell the AI what page you want
                </label>
                <textarea wire:model.live.debounce.300ms="createPrompt"
                          rows="6"
                          placeholder="e.g. Build a page 'Build Your Own Villa' with a hero, 3 cards for the process steps, and a call-to-action with a 'Contact us' button..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"></textarea>
                @error('createPrompt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <div class="mt-4 flex gap-2">
                    <button type="button" wire:click="runCreate" wire:loading.attr="disabled"
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="runCreate">🚀 Build it</span>
                        <span wire:loading wire:target="runCreate">⏳ Generating…</span>
                    </button>
                    <button type="button" wire:click="reset_" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-900">
                        Clear
                    </button>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                <div class="text-sm font-medium text-gray-700 mb-3">
                    Section templates the AI may use
                </div>
                <div class="text-xs text-gray-500 mb-3">
                    If you don't pick any, the AI sees ALL templates and chooses itself.
                </div>
                <div class="space-y-1 max-h-96 overflow-y-auto pr-2">
                    @foreach ($allTemplates as $tpl)
                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white cursor-pointer text-sm">
                            <input type="checkbox"
                                   wire:click="toggleTemplate('{{ $tpl->slug }}')"
                                   @checked(in_array($tpl->slug, $createTemplates))
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="flex-1">
                                <span class="text-gray-900">{{ $tpl->name }}</span>
                                <span class="text-xs text-gray-400 ml-1">{{ $tpl->slug }}</span>
                            </span>
                            <span class="text-xs text-gray-400">{{ $tpl->category }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- EDIT TAB --}}
    @if ($activeTab === 'edit')
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Pick a page
                </label>
                <select wire:model="editPageId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm">
                    <option value="">— choose —</option>
                    @foreach ($allPages as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->title }} ({{ $p->slug }}) — {{ $p->status }}
                        </option>
                    @endforeach
                </select>
                @error('editPageId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    What change do you want the AI to make?
                </label>
                <textarea wire:model.live.debounce.300ms="editPrompt"
                          rows="5"
                          placeholder="e.g. In the first paragraph change 'Building' to 'Build', and add a new call-to-action card at the end with the title 'Request a quote'..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"></textarea>
                @error('editPrompt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2">
                <button type="button" wire:click="runEdit" wire:loading.attr="disabled"
                        class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="runEdit">✨ Apply changes</span>
                    <span wire:loading wire:target="runEdit">⏳ Editing…</span>
                </button>
                <button type="button" wire:click="reset_" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-900">
                    Clear
                </button>
            </div>

            <div class="text-xs text-gray-500 pt-2 border-t border-gray-100">
                💡 The AI takes the current page JSON, applies your change, and does a smart-merge:
                section IDs and EditorJS block IDs are preserved — only what you asked changes.
            </div>
        </div>
    @endif

    {{-- RESULT PANEL --}}
    @if ($result)
        <div class="mt-6">
            @if (($result['ok'] ?? false) === true)
                <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">✅</div>
                        <div class="flex-1">
                            <div class="font-medium text-green-900">
                                Success!
                                @if (($result['created'] ?? false) === true)
                                    New page created.
                                @else
                                    Page updated.
                                @endif
                            </div>
                            <div class="text-sm text-green-700 mt-1 space-y-0.5">
                                <div><span class="text-green-600">Page ID:</span> {{ $result['page_id'] ?? '—' }}</div>
                                <div><span class="text-green-600">Slug:</span> {{ $result['slug'] ?? '—' }}</div>
                                <div><span class="text-green-600">Sections touched:</span> {{ $result['sections_touched'] ?? 0 }}</div>
                            </div>

                            @if (! empty($result['warnings']))
                                <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                                    <div class="font-medium mb-1">Warnings:</div>
                                    <ul class="list-disc list-inside space-y-0.5">
                                        @foreach ($result['warnings'] as $w)
                                            <li>{{ $w }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if (! empty($result['url']))
                                    <a href="{{ $result['url'] }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                        🌐 View page
                                    </a>
                                @endif
                                @if (! empty($result['page_id']))
                                    <a href="/admin/content-tree?focus={{ $result['page_id'] }}"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-lg text-sm transition">
                                        🌳 Edit in Content Tree
                                    </a>
                                @endif
                                <button type="button" wire:click="$toggle('showJson')"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-lg text-sm transition">
                                    {{ $showJson ? '▾' : '▸' }} View AI output
                                </button>
                            </div>

                            @if ($showJson && ! empty($result['ai_response_preview']))
                                <pre class="mt-3 p-3 bg-gray-900 text-green-300 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto"><code>{{ $result['ai_response_preview'] }}</code></pre>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">❌</div>
                        <div class="flex-1">
                            <div class="font-medium text-red-900">Failed</div>
                            <div class="text-sm text-red-700 mt-1">
                                {{ $result['error'] ?? 'Unknown error' }}
                            </div>
                            @if (! empty($result['warnings']))
                                <ul class="list-disc list-inside text-xs text-amber-700 mt-2 space-y-0.5">
                                    @foreach ($result['warnings'] as $w)
                                        <li>{{ $w }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (! empty($result['json']))
                                <details class="mt-3 text-xs">
                                    <summary class="cursor-pointer text-red-600 underline">View the JSON the AI produced</summary>
                                    <pre class="mt-2 p-3 bg-gray-900 text-red-300 rounded overflow-x-auto max-h-64 overflow-y-auto"><code>{{ $result['json'] }}</code></pre>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Loading overlay --}}
    <div wire:loading.flex wire:target="runCreate,runEdit"
         class="fixed inset-0 bg-black/40 z-50 items-center justify-center">
        <div class="bg-white rounded-xl p-6 shadow-xl flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <div class="font-medium text-gray-900">AI thinking…</div>
                <div class="text-xs text-gray-500">This can take 5–30 seconds.</div>
            </div>
        </div>
    </div>
</div>

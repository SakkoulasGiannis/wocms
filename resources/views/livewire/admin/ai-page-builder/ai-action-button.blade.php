<div>
    {{-- Trigger button --}}
    @if ($this->isSupported())
        <button type="button" wire:click="openModal"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition
                       {{ $mode === 'edit'
                          ? 'bg-purple-100 hover:bg-purple-200 text-purple-700 border border-purple-200'
                          : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white shadow-sm' }}"
                title="{{ $mode === 'edit' ? 'Ask AI to edit this' : 'Ask AI to create a new one' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                      d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 0 0-3.09 3.091ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
            </svg>
            <span>
                @if ($mode === 'edit')
                    Ask AI to edit
                @else
                    Create with AI
                @endif
            </span>
        </button>
    @else
        <button type="button" disabled
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed"
                title="AI builder is not yet available for this entity type.">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                      d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 0 0-3.09 3.091Z"/>
            </svg>
            AI (soon)
        </button>
    @endif

    {{-- Modal --}}
    @if ($open)
        @php
            // Single JS helper used by ESC, backdrop, and X button. Going through
            // Alpine + $wire.* is more reliable than wire:click when the modal
            // is rendered inside a wire:ignore.self container (e.g. visual editor
            // sidebar) — wire:click events can be swallowed by intermediate
            // morph guards.
            //
            // We deliberately do NOT lock body scroll. The fixed inset-0
            // backdrop already isolates the modal, and a leftover
            // `overflow: hidden` was breaking the EditorJS fullscreen toolbox
            // and media picker after the modal closed.
            $closeJs = '$wire.closeModal()';
        @endphp
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             x-data="{}"
             x-on:keydown.escape.window="{{ $closeJs }}"
             wire:key="ai-action-modal-{{ $mode }}-{{ $entityId ?? 'new' }}">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" x-on:click="{{ $closeJs }}"></div>

            {{-- Dialog — explicit text color so it doesn't inherit white from
                 the visual editor's dark header when embedded there. --}}
            <div class="relative bg-white text-gray-900 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                {{-- Header --}}
                <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 0 0-3.09 3.091Z"/>
                            </svg>
                            @if ($mode === 'edit')
                                Edit this with AI
                            @else
                                Create new with AI
                            @endif
                        </h2>
                        @if ($mode === 'edit' && $entityLabel)
                            <p class="text-sm text-gray-500 mt-1">
                                Editing: <span class="font-medium text-gray-700">{{ $entityLabel }}</span>
                                @if ($entityId)
                                    <span class="text-xs text-gray-400">(#{{ $entityId }})</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    <button type="button" x-on:click="{{ $closeJs }}"
                            class="text-gray-400 hover:text-gray-600 transition" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 overflow-y-auto flex-1">
                    @if (! $result)
                        @php
                            $promptPlaceholder = $mode === 'edit'
                                ? 'e.g. In the first paragraph, change X to Y and add a new call-to-action card at the end...'
                                : 'e.g. Build a page for completed villas with a hero, 3 cards for project examples, and a contact section';
                        @endphp
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            @if ($mode === 'edit')
                                Tell AI what change to make:
                            @else
                                Tell AI what you want it to build:
                            @endif
                        </label>
                        <textarea wire:model="prompt"
                                  rows="6"
                                  autofocus
                                  placeholder="{{ $promptPlaceholder }}"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"></textarea>
                        @error('prompt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                        <div class="text-xs text-gray-500 mt-3 bg-gray-50 rounded-lg p-3 border border-gray-100">
                            💡 The AI will produce JSON and compile it directly into the database,
                            @if ($mode === 'edit')
                                preserving all IDs (smart-merge — only what you asked changes).
                            @else
                                using the available section templates.
                            @endif
                            Tailwind v4 classes throughout.
                        </div>
                    @else
                        {{-- Result --}}
                        @if (($result['ok'] ?? false) === true)
                            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                                <div class="flex items-start gap-3">
                                    <div class="text-2xl">✅</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-green-900">
                                            @if (($result['created'] ?? false) === true)
                                                New item created!
                                            @else
                                                Updated successfully!
                                            @endif
                                        </div>
                                        <div class="text-sm text-green-700 mt-1">
                                            @if (! empty($result['page_id']))
                                                Page ID <strong>{{ $result['page_id'] }}</strong> ·
                                                Slug <code class="text-xs bg-white px-1 rounded">{{ $result['slug'] ?? '—' }}</code> ·
                                                {{ $result['sections_touched'] ?? 0 }} sections
                                            @else
                                                Entity ID <strong>{{ $result['entity_id'] ?? '—' }}</strong> ·
                                                {{ count($result['fields_written'] ?? []) }} fields written
                                            @endif
                                        </div>

                                        @if (! empty($result['warnings']))
                                            <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                                                <strong>Warnings:</strong>
                                                <ul class="list-disc list-inside mt-1 space-y-0.5">
                                                    @foreach ($result['warnings'] as $w)
                                                        <li>{{ $w }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @if (! empty($result['url']))
                                                {{-- url() forces an absolute root URL so /test never
                                                     gets resolved relative to /admin/page-sections/... --}}
                                                <a href="{{ url($result['url']) }}" target="_blank"
                                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-green-300 text-green-700 hover:bg-green-100 rounded-lg text-sm font-medium transition">
                                                    🌐 View live
                                                </a>
                                            @endif
                                            {{-- Smart destination button: reload current page when the
                                                 AI was triggered from inside the visual editor; redirect
                                                 to the entry edit form otherwise. --}}
                                            @php
                                                $entityId = $result['page_id'] ?? $result['entity_id'] ?? null;
                                                $editFormUrl = $entityId ? '/admin/'.($this->isPageModel() ? 'page' : $this->templateSlug).'/'.$entityId.'/edit' : '#';
                                            @endphp
                                            <button type="button"
                                                    x-on:click="
                                                        const isVisualEditor = window.location.pathname.includes('/page-sections/visual/');
                                                        if (isVisualEditor) {
                                                            window.location.reload();
                                                        } else {
                                                            window.location.href = @js($editFormUrl);
                                                        }
                                                    "
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                                <span x-text="window.location.pathname.includes('/page-sections/visual/') ? '↻ Reload to see changes' : '✏️ Open edit form'"></span>
                                            </button>
                                            @if (! empty($result['pre_revision_id']) && empty($result['was_undo']))
                                                <button type="button" wire:click="revertLast"
                                                        wire:confirm="Restore to the state before this AI change?"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 rounded-lg text-sm font-medium transition"
                                                        title="Restore to the state before this AI change">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M3 10h10a8 8 0 018 8v2M3 10l6 6M3 10l6-6"/>
                                                    </svg>
                                                    Undo
                                                </button>
                                            @endif
                                            @if (! empty($result['was_undo']))
                                                <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs">
                                                    ↶ Restored
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-red-50 border border-red-200 rounded-xl p-5">
                                <div class="flex items-start gap-3">
                                    <div class="text-2xl">❌</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-red-900">Failed</div>
                                        <div class="text-sm text-red-700 mt-1">{{ $result['error'] ?? 'Unknown error' }}</div>
                                        <button type="button" wire:click="$set('result', null)"
                                                class="mt-3 text-xs text-red-600 underline hover:text-red-800">
                                            ↻ Try again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Footer --}}
                @if (! $result)
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                        <a href="{{ route('admin.settings') }}" target="_blank"
                           class="text-xs text-gray-500 hover:text-gray-700 underline">
                            ⚙ Settings → AI Prompts
                        </a>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="{{ $closeJs }}"
                                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 rounded-lg">
                                Cancel
                            </button>
                            <button type="button" wire:click="run" wire:loading.attr="disabled"
                                    class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 inline-flex items-center gap-2">
                                <span wire:loading.remove wire:target="run">
                                    @if ($mode === 'edit')
                                        ✨ Apply changes
                                    @else
                                        🚀 Build it
                                    @endif
                                </span>
                                <span wire:loading wire:target="run" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    AI thinking…
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <script>
        // Defensive cleanup: clear any leftover body.overflow lock from prior
        // modal versions so it doesn't block the EditorJS toolbox / picker.
        if (document.body.style.overflow === 'hidden') {
            document.body.style.overflow = '';
        }
        document.addEventListener('livewire:navigated', () => { document.body.style.overflow = ''; });

        // CRITICAL: register the Livewire hook ONLY after Livewire JS is loaded.
        // This script can render before livewire.js in the inline-component case
        // (e.g. embedded inside the visual editor sidebar), and calling
        // Livewire.hook() before then throws ReferenceError that aborts EVERY
        // subsequent inline script on the page — which is what was killing
        // Alpine and Livewire-bound scroll behaviour everywhere.
        document.addEventListener('livewire:init', () => {
            if (typeof Livewire !== 'undefined' && Livewire.hook) {
                Livewire.hook('morph.removed', ({ el }) => {
                    if (el && el.matches && el.matches('.fixed.inset-0.z-\\[60\\]')) {
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
</div>

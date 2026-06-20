<div>
    {{-- Trigger --}}
    <button type="button" wire:click="openModal"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 transition"
            title="AI change history — restore a previous version">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>History</span>
        @if ($totalCount > 0)
            <span class="ml-1 inline-flex items-center justify-center text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700">
                {{ $totalCount }}
            </span>
        @endif
    </button>

    {{-- Modal --}}
    @if ($open)
        @php
            // No body.overflow lock — backdrop already isolates the modal,
            // and a leftover lock blocks the EditorJS fullscreen toolbox &
            // media picker after this modal closes.
            $closeJs = '$wire.closeModal()';
        @endphp
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             x-data="{}"
             x-on:keydown.escape.window="{{ $closeJs }}">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" x-on:click="{{ $closeJs }}"></div>

            <div class="relative bg-white text-gray-900 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                {{-- Header --}}
                <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            🕐 AI change history
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $totalCount }} snapshots — most recent first (up to 50 shown).
                            <span class="text-xs text-gray-400">Auto-cleanup after 30 days.</span>
                        </p>
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
                    @if ($result)
                        {{-- Restore result --}}
                        @if (($result['ok'] ?? false) === true)
                            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                                <div class="flex items-start gap-3">
                                    <div class="text-2xl">✅</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-green-900">Restored!</div>
                                        <div class="text-sm text-green-700 mt-1">
                                            From revision <strong>#{{ $result['restored_from'] ?? '—' }}</strong>.
                                            @if (! empty($result['sections_touched']))
                                                {{ $result['sections_touched'] }} sections.
                                            @endif
                                        </div>
                                        <div class="mt-3 flex gap-2">
                                            <button type="button" wire:click="reloadPage"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                                ↻ Reload edit form
                                            </button>
                                            <button type="button" wire:click="$set('result', null)"
                                                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm transition">
                                                Back to list
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                                <div class="font-medium text-red-900">❌ Failed</div>
                                <div class="text-sm text-red-700 mt-1">{{ $result['error'] ?? 'Unknown error' }}</div>
                                <button type="button" wire:click="$set('result', null)"
                                        class="mt-3 text-xs text-red-600 underline hover:text-red-800">
                                    Back to list
                                </button>
                            </div>
                        @endif
                    @endif

                    @if ($revisions->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            No AI snapshots yet for this item.
                            <div class="text-xs text-gray-400 mt-2">
                                Snapshots are only created when you use the AI to change this item.
                            </div>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($revisions as $rev)
                                <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:border-gray-300 transition
                                            {{ str_starts_with($rev->source, 'pre-') ? 'bg-amber-50/30' : 'bg-white' }}"
                                     wire:key="rev-{{ $rev->id }}">
                                    <div class="flex-shrink-0 mt-0.5">
                                        @if (str_starts_with($rev->source, 'pre-'))
                                            <span title="Before change — rollback target" class="text-amber-600">↶</span>
                                        @else
                                            <span title="After change — baseline" class="text-green-600">●</span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <span class="font-medium text-sm text-gray-900">{{ $rev->sourceLabel() }}</span>
                                            <span class="text-xs text-gray-500">{{ $rev->created_at->format('Y-m-d H:i:s') }}</span>
                                            <span class="text-xs text-gray-400">({{ $rev->created_at->diffForHumans() }})</span>
                                            @if ($rev->user)
                                                <span class="text-xs text-gray-500">· {{ $rev->user->name }}</span>
                                            @endif
                                        </div>
                                        @if ($rev->prompt)
                                            <div class="text-xs text-gray-600 mt-1 italic line-clamp-2">
                                                "{{ \Illuminate\Support\Str::limit($rev->prompt, 200) }}"
                                            </div>
                                        @endif
                                        <div class="text-[10px] text-gray-400 mt-1">#{{ $rev->id }}</div>
                                    </div>
                                    <button type="button"
                                            wire:click="restore({{ $rev->id }})"
                                            wire:confirm="Restore to this snapshot?\n\nThe current state will be saved as a new snapshot before being replaced."
                                            wire:loading.attr="disabled"
                                            class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-medium transition disabled:opacity-50">
                                        <span wire:loading.remove wire:target="restore({{ $rev->id }})">↻ Restore</span>
                                        <span wire:loading wire:target="restore({{ $rev->id }})">…</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex justify-between items-center text-xs text-gray-500">
                    <div>↶ = before change · ● = after change</div>
                    <button type="button" x-on:click="{{ $closeJs }}" class="text-gray-600 hover:text-gray-900">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

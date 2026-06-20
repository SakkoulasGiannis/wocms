    {{-- ===== LEFT PANEL: Sections List — RESIZABLE ===== --}}
    <div
        data-ve-sidebar
        wire:ignore.self
        x-data="{
            width: parseInt(localStorage.getItem('ve-sidebar-width') || '320', 10),
            dragging: false,
            startX: 0,
            startWidth: 0,
            startDrag(e) {
                this.dragging = true;
                this.startX = e.clientX;
                this.startWidth = this.width;
                document.body.style.userSelect = 'none';
                document.body.style.cursor = 'col-resize';
                // Disable iframe pointer events so it doesn't swallow mousemove
                const f = document.getElementById('preview-frame');
                if (f) { f.style.pointerEvents = 'none'; }
            },
            onDrag(e) {
                if (!this.dragging) return;
                const dx = e.clientX - this.startX;
                this.width = Math.max(240, Math.min(720, this.startWidth + dx));
                this.$root.style.width = this.width + 'px';
            },
            stopDrag() {
                if (!this.dragging) return;
                this.dragging = false;
                document.body.style.userSelect = '';
                document.body.style.cursor = '';
                const f = document.getElementById('preview-frame');
                if (f) { f.style.pointerEvents = ''; }
                localStorage.setItem('ve-sidebar-width', this.width);
            },
        }"
        x-init="$root.style.width = width + 'px';"
        @mousemove.window="onDrag"
        @mouseup.window="stopDrag"
        class="relative bg-white border-r border-gray-200 flex flex-col shadow-lg z-10 flex-shrink-0"
        style="height:100vh;">

        {{-- Header --}}
        <div class="px-4 py-3 bg-gray-900 text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ $backUrl }}"
                   class="text-gray-300 hover:text-white flex-shrink-0"
                   title="Back">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="text-xs text-gray-400 leading-none">Visual Editor</div>
                    <div class="text-sm font-semibold truncate leading-tight mt-0.5">{{ $pageTitle }}</div>
                </div>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                {{-- Undo / Redo (also Ctrl+Z / Ctrl+Y) --}}
                <button type="button"
                        wire:click="undo"
                        @disabled(! $this->canUndo)
                        title="Undo (Ctrl+Z)"
                        class="p-1.5 rounded text-gray-300 enabled:hover:text-white enabled:hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6h6M3 13a9 9 0 1 0 3-7.7L3 8"/>
                    </svg>
                </button>
                <button type="button"
                        wire:click="redo"
                        @disabled(! $this->canRedo)
                        title="Redo (Ctrl+Y)"
                        class="p-1.5 rounded text-gray-300 enabled:hover:text-white enabled:hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7v6h-6M21 13a9 9 0 1 1-3-7.7L21 8"/>
                    </svg>
                </button>
                <a href="{{ $previewUrl }}" target="_blank" class="p-1.5 rounded text-gray-400 hover:text-white hover:bg-white/10 transition" title="Open page">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>

                {{-- Draft Save / Discard. Nothing is written to the database until
                     "Save" is clicked; "Discard" reloads the draft from the DB and
                     throws away unsaved changes. The amber dot is the unsaved-changes
                     indicator. --}}
                <div class="flex items-center gap-1 ml-1 pl-1 border-l border-white/10">
                    @if($isDirty)
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse" title="Unsaved changes"></span>
                    @endif
                    <button type="button"
                            wire:click="discardDraft"
                            wire:confirm="Discard all unsaved changes and reload from the saved version?"
                            @disabled(! $isDirty)
                            title="Discard unsaved changes"
                            class="p-1.5 rounded text-gray-300 enabled:hover:text-white enabled:hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6h6M3 13a9 9 0 1 0 3-7.7L3 8"/>
                        </svg>
                    </button>
                    <button type="button"
                            x-on:click.prevent="
                                (async () => {
                                    /* Fold any open editor content into the draft, then
                                       persist the whole draft in one Save. */
                                    const eds = (typeof window.veCollectEditors === 'function') ? await window.veCollectEditors() : [];
                                    const patch = {};
                                    eds.forEach((e) => { const m = e.wireModel.match(/^sectionContent\.(.+)$/); if (m) { patch[m[1]] = e.json; } });
                                    $wire.saveDraft(patch);
                                })();
                            "
                            title="Save all changes"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold transition
                                   {{ $isDirty ? 'bg-purple-600 text-white hover:bg-purple-500' : 'bg-white/10 text-gray-300 hover:bg-white/20' }}">
                        <svg wire:loading.remove wire:target="saveDraft" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading wire:target="saveDraft" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span wire:loading.remove wire:target="saveDraft">Save</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </button>
                </div>

                {{-- AI: edit current page + revision history. Only for Page
                     entities (template-mode visual editing doesn't go through
                     the page builder agent yet). Wrapper sizes the embedded
                     pills so they fit the dark compact header. --}}
                @if($sectionableType === \App\Models\Page::class)
                    <div class="flex items-center gap-1 ml-1 pl-1 border-l border-white/10
                                [&_button]:!py-1 [&_button]:!px-2 [&_button]:!text-xs [&_button]:!rounded">
                        <livewire:admin.ai-page-builder.page-history-button
                            :page-id="(int) $sectionableId"
                            :key="'ve-history-'.$sectionableId" />

                        <livewire:admin.ai-page-builder.ai-action-button
                            mode="edit"
                            model-class="Page"
                            :entity-id="(int) $sectionableId"
                            :entity-label="$pageTitle"
                            :key="'ve-ai-edit-'.$sectionableId" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Sections list --}}
        @php
            $allSectionsCollection = collect($sections);
            $rootSections = $allSectionsCollection->whereNull('parent_section_id')->sortBy('order')->values();
            $hasAnyContainer = $allSectionsCollection->whereIn('section_type', ['primitive_div','primitive_grid','primitive_section'])->isNotEmpty();
        @endphp

        @if($hasAnyContainer)
            {{-- Collapse / expand all toolbar. Broadcasts a window event that
                 every section-tree-item listens for. --}}
            <div class="flex items-center gap-1 px-3 py-1.5 border-b border-gray-100 bg-gray-50">
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('ve-collapse-all'))"
                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded text-gray-600 hover:bg-gray-200">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    Collapse all
                </button>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('ve-expand-all'))"
                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded text-gray-600 hover:bg-gray-200">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    Expand all
                </button>
                <button type="button"
                        wire:click="cleanupEmptyWrappers"
                        wire:confirm="Remove all empty wrapper divs/sections (no class, no id)? Their children move up. This can't be undone except via Ctrl+Z."
                        class="ml-auto inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded text-gray-600 hover:bg-amber-100 hover:text-amber-700"
                        title="Delete redundant empty wrapper divs/sections and lift their children up">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clean empty
                </button>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto py-2" id="ve-sections-list" data-container="">
            @forelse($rootSections as $section)
                @include('livewire.admin.page-sections.partials.section-tree-item', [
                    'section' => is_array($section) ? $section : $section->toArray(),
                    'allSections' => $allSectionsCollection,
                    'depth' => 0,
                    'selectedSectionId' => $selectedSectionId,
                ])
            @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                    @if($scope === 'entry' || $scope === 'listing')
                        {{-- Template-design empty state — explain + offer starter --}}
                        <div class="mx-auto max-w-xs">
                            <svg class="w-10 h-10 mx-auto mb-3 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-700 mb-1">
                                Designing the {{ $scope === 'listing' ? 'listing page' : 'entry page' }}
                            </p>
                            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                                This page currently uses the default theme template (which is what you see in the preview).
                                Add one or more sections here to <em>replace</em> it with your own design — every entry of
                                this template will then use the layout you build.
                            </p>
                            @if($this->hasStarterPreset)
                                <button wire:click="applyStarterPreset"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-semibold shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Use default design
                                </button>
                                <p class="text-[10px] text-gray-400 mt-2">Loads sensible defaults you can edit / reorder / remove</p>
                            @endif
                        </div>
                    @else
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        No sections yet
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Add Section button --}}
        <div class="p-3 border-t border-gray-200 flex-shrink-0">
            <button wire:click="openAddPanel"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Section
            </button>
        </div>

        {{-- Drag handle — sits on the right edge, drag horizontally to resize.
             Width is persisted in localStorage so it sticks across reloads. --}}
        <div
            @mousedown.prevent="startDrag"
            class="group absolute top-0 right-0 h-full w-2 cursor-col-resize z-30 flex items-center justify-center"
            :class="dragging ? 'bg-purple-500/30' : 'bg-gray-100 hover:bg-purple-200'"
            title="Drag to resize sidebar">
            <div class="h-16 w-1 rounded-full bg-gray-300 group-hover:bg-purple-500"
                 :class="dragging ? 'bg-purple-600' : ''"></div>
        </div>
    </div>

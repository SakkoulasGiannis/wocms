{{--
    EditorJS Field Component
    Usage:
      <x-editorjs-field
          :name="$field->name"
          :value="$fieldValues[$field->name] ?? ''"
          wire-model="fieldValues.{{ $field->name }}"
          :placeholder="'Start writing...'"
      />
--}}
@props([
    'name',
    'value' => '',
    'wireModel' => null,
    'placeholder' => 'Start writing or press / for commands...',
    'minHeight' => '200px',
    'uid' => null,
    'autosave' => true,
])

@php
    $uid = $uid ?? 'ejs-' . str_replace(['.', '[', ']'], '-', $name) . '-' . Str::random(6);
@endphp

<div
    wire:ignore
    x-data="editorjsField({
        uid: '{{ $uid }}',
        wireModel: {{ $wireModel ? "'" . $wireModel . "'" : 'null' }},
        autosave: {{ $autosave ? 'true' : 'false' }},
        initialValue: {{ json_encode($value) }},
        uploadImageUrl: '{{ route('admin.editorjs.upload-image') }}',
        fetchImageUrl: '{{ route('admin.editorjs.fetch-image') }}',
        uploadFileUrl: '{{ route('admin.editorjs.upload-file') }}',
        mediaListUrl: '{{ route('admin.editorjs.media') }}',
        csrfToken: '{{ csrf_token() }}',
        placeholder: {{ json_encode($placeholder) }},
    })"
    x-init="init()"
    x-on:livewire:navigating.window="flushSave()"
    x-on:livewire:navigated.window="destroy(); $nextTick(() => init())"
    x-on:submit.window="flushSave()"
    x-on:beforeunload.window="flushSave()"
    x-on:visibilitychange.document="if (document.visibilityState === 'hidden') flushSave()"
>
    {{-- Recovery banner: floats over the editor without affecting layout — so its
         appearance/disappearance never shifts content (which would visually
         look like a scroll-reset). Pinned bottom-right of the viewport. --}}
    <div
        x-show="showRecovery"
        x-transition
        style="display:none; position:fixed; bottom:24px; right:24px; z-index:9998; max-width:420px;"
        class="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm shadow-2xl">
        <div class="flex items-center gap-2 text-amber-900">
            <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
            <span>Unsaved changes from <span x-text="recoveryAge"></span> were found.</span>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <button type="button" @click="previewRecovery()" class="rounded-md border border-amber-400 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">Preview</button>
            <button type="button" @click="recoverContent()" class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Restore</button>
            <button type="button" @click="dismissRecovery()" class="rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100">Dismiss</button>
        </div>
    </div>

    {{-- Preview modal — side-by-side: current editor content vs unsaved snapshot --}}
    <div
        x-show="showRecoveryPreview"
        x-transition.opacity
        @keydown.escape.window="showRecoveryPreview = false"
        class="fixed inset-0 z-[10000] flex items-center justify-center p-6 bg-black/50"
        style="display:none"
        @click.self="showRecoveryPreview = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-gray-50">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Unsaved changes preview</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Compare the unsaved snapshot to the currently-loaded content, then decide.</p>
                </div>
                <button type="button" @click="showRecoveryPreview = false" class="p-1.5 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-200" title="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 flex-1 min-h-0 overflow-hidden">
                <div class="flex flex-col border-r border-gray-200 min-h-0">
                    <div class="flex-shrink-0 px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500 bg-gray-50 border-b border-gray-200">Currently in editor (saved)</div>
                    <div class="flex-1 min-h-0 overflow-y-auto p-5 prose prose-sm max-w-none" x-html="previewCurrentHtml"></div>
                </div>
                <div class="flex flex-col min-h-0">
                    <div class="flex-shrink-0 px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-amber-700 bg-amber-50 border-b border-amber-200">Unsaved snapshot (<span x-text="recoveryAge"></span>)</div>
                    <div class="flex-1 min-h-0 overflow-y-auto p-5 prose prose-sm max-w-none" x-html="previewSnapshotHtml"></div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-200 bg-gray-50">
                <button type="button" @click="dismissRecovery(); showRecoveryPreview = false" class="rounded-md border border-gray-300 bg-white px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">Discard snapshot</button>
                <button type="button" @click="recoverContent(); showRecoveryPreview = false" class="rounded-md bg-amber-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Restore snapshot</button>
            </div>
        </div>
    </div>
    {{-- Toolbar: undo/redo + templates + media library — placed ABOVE the
         editor so it stays in view (the editor body can be tall). --}}
    <div class="mb-2 flex items-center gap-2">
        <div class="inline-flex items-center rounded-md border border-slate-200 bg-white overflow-hidden">
            <button
                type="button"
                @click="undo?.undo?.()"
                title="Undo (Ctrl+Z)"
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors border-r border-slate-200">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6h6M3 13a9 9 0 1 0 3-7.7L3 8"/></svg>
                Undo
            </button>
            <button
                type="button"
                @click="undo?.redo?.()"
                title="Redo (Ctrl+Y / Ctrl+Shift+Z)"
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7v6h-6M21 13a9 9 0 1 1-3-7.7L21 8"/></svg>
                Redo
            </button>
        </div>
        <button
            type="button"
            @click="if (editor && editor !== '_loading_') window.editorjsTemplates.openModal(editor)"
            title="Block templates (save / insert reusable patterns)"
            class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
            Templates
        </button>
        <button
            type="button"
            @click="pickFromMediaLibrary()"
            title="Insert image from media library"
            class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            Media library
        </button>
    </div>

    <div id="{{ $uid }}" class="editorjs-container" style="min-height: {{ $minHeight }}; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff; padding: 0.5rem 0;"></div>

    {{-- Save state indicator (saving / saved / error) --}}
    <div
        x-show="saveState !== 'idle'"
        x-transition.opacity
        :class="saveState === 'error' ? 'text-rose-700 bg-rose-50 ring-rose-200' : (saveState === 'saved' ? 'text-emerald-700 bg-emerald-50 ring-emerald-200' : 'text-slate-600 bg-slate-50 ring-slate-200')"
        class="mt-1 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 transition-colors"
        style="display:none">
        <template x-if="saveState === 'saving'">
            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="28 60" stroke-linecap="round"/></svg>
        </template>
        <template x-if="saveState === 'saved'">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </template>
        <template x-if="saveState === 'error'">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v5M12 16h.01"/></svg>
        </template>
        <span x-text="saveState === 'saving' ? 'Saving…' : (saveState === 'saved' ? 'Saved' : 'Save failed')"></span>
    </div>
</div>

@once
@push('styles')
@include('components.editorjs-field-parts._styles')
@endpush

@push('scripts')
@include('components.editorjs-field-parts._scripts')
@endpush
@endonce

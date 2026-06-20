@extends('layouts.admin-clean')

@section('title', 'New Builder')
@section('page-title', 'New Builder')

@push('styles')
<style>
    #new-builder-app { --nb-border: #e5e7eb; --nb-muted: #6b7280; }
    #new-builder-app .nb-pane { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    /* ---- tree ---- */
    #new-builder-app ul.nb-roots,
    #new-builder-app ul.nb-children { list-style: none; margin: 0; padding-left: 1rem; }
    #new-builder-app ul.nb-roots { padding-left: 0; min-height: 2rem; }
    #new-builder-app ul.nb-children { border-left: 1px dashed #d1d5db; min-height: .35rem; }
    #new-builder-app ul.nb-children.nb-collapsed { display: none; }
    #new-builder-app .nb-row {
        display: flex; align-items: center; gap: .4rem;
        padding: .25rem .45rem; border-radius: .375rem; font-size: .8125rem;
        cursor: pointer; transition: background .1s, box-shadow .1s;
    }
    #new-builder-app .nb-row:hover { background: #f3f4f6; }
    #new-builder-app .nb-row.nb-selected { background: #dbeafe; box-shadow: inset 0 0 0 1px #93c5fd; }
    #new-builder-app .nb-row.nb-hover { background: #eef2ff; box-shadow: inset 0 0 0 1px #c7d2fe; }
    #new-builder-app .nb-toggle { width: 1rem; text-align: center; color: #9ca3af; font-size: .75rem; user-select: none; }
    #new-builder-app .nb-toggle-empty { color: #d1d5db; font-size: .6rem; }
    #new-builder-app .nb-handle { cursor: grab; color: #cbd5e1; user-select: none; font-size: .8rem; }
    #new-builder-app .nb-label { font-weight: 600; color: #1f2937; }
    #new-builder-app .nb-tag-muted { font-weight: 400; color: #9ca3af; margin-left: .4rem; font-family: ui-monospace, Menlo, monospace; font-size: .72rem; }
    #new-builder-app .nb-hint-count { color: #a5b4fc; font-size: .68rem; font-family: ui-monospace, Menlo, monospace; }
    #new-builder-app .nb-preview { color: #9ca3af; font-style: italic; font-size: .72rem; }
    #new-builder-app .nb-actions { margin-left: auto; display: flex; gap: .25rem; opacity: 0; transition: opacity .1s; }
    #new-builder-app .nb-row:hover .nb-actions { opacity: 1; }
    #new-builder-app .nb-actions button {
        font-size: .6875rem; padding: .1rem .35rem; border: 1px solid #d1d5db;
        border-radius: .25rem; background: #fff; color: #374151;
    }
    #new-builder-app .nb-actions button.nb-del { color: #b91c1c; border-color: #fecaca; }

    /* ---- inspector ---- */
    #new-builder-app .nb-field { margin-bottom: .85rem; }
    #new-builder-app .nb-field label { display: block; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--nb-muted); margin-bottom: .3rem; }
    #new-builder-app .nb-field input,
    #new-builder-app .nb-field textarea { width: 100%; font-size: .8125rem; }
    #new-builder-app .nb-attr-row { display: flex; gap: .35rem; margin-bottom: .35rem; align-items: center; }
    #new-builder-app .nb-attr-row input { flex: 1; }
    #new-builder-app .nb-attr-del { padding: .25rem .5rem; border: 1px solid #fecaca; color: #b91c1c; border-radius: .25rem; background: #fff; }
    #new-builder-app .nb-add-attr { font-size: .75rem; color: #2563eb; }
    #new-builder-app .nb-hint { font-size: .8125rem; color: #9ca3af; }

    /* ---- inspector tabs (Properties / Inline CSS) ---- */
    #new-builder-app .nb-insp-tabs { display: flex; gap: .15rem; border-bottom: 1px solid var(--nb-border); margin-bottom: .85rem; }
    #new-builder-app .nb-insp-tab { padding: .4rem .7rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; border: none; border-bottom: 2px solid transparent; background: transparent; cursor: pointer; }
    #new-builder-app .nb-insp-tab:hover { color: #374151; }
    #new-builder-app .nb-insp-tab.nb-insp-tab-active { color: #2563eb; border-bottom-color: #2563eb; }
    #new-builder-app .nb-insp-body[data-insp-active="props"] .nb-panel-css { display: none; }
    #new-builder-app .nb-insp-body[data-insp-active="css"] .nb-panel-props { display: none; }
    #new-builder-app .nb-css-area { font-family: ui-monospace, Menlo, monospace; font-size: .78rem; line-height: 1.5; }
    #new-builder-app .nb-css-area + .nb-hint code { background: #f3f4f6; padding: 0 .2rem; border-radius: .2rem; }

    /* ---- class picker chips ---- */
    #new-builder-app .nb-chip-picker { border: 1px solid var(--nb-border); border-radius: .5rem; padding: .5rem; background: #fafafa; max-height: 180px; overflow: auto; }
    #new-builder-app .nb-chip-group { margin-bottom: .5rem; }
    #new-builder-app .nb-chip-group:last-child { margin-bottom: 0; }
    #new-builder-app .nb-chip-group-label { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; margin-bottom: .25rem; }
    #new-builder-app .nb-chips { display: flex; flex-wrap: wrap; gap: .25rem; }
    #new-builder-app .nb-chip {
        font-family: ui-monospace, Menlo, monospace; font-size: .68rem;
        padding: .15rem .4rem; border: 1px solid #d1d5db; border-radius: 9999px;
        background: #fff; color: #4b5563; cursor: pointer; transition: all .1s;
    }
    #new-builder-app .nb-chip:hover { border-color: #93c5fd; color: #2563eb; }
    #new-builder-app .nb-chip-on { background: #2563eb; border-color: #2563eb; color: #fff; }

    /* ---- preview frame ---- */
    #new-builder-app .nb-preview-stage { background: #f3f4f6; display: flex; justify-content: center; }
    #new-builder-app iframe[data-pane="preview"] { box-shadow: 0 1px 4px rgba(0,0,0,.08); background: #fff; transition: width .15s; max-width: 100%; }
    #new-builder-app .nb-width-btn { padding: .25rem .55rem; font-size: .7rem; border: 1px solid #d1d5db; background: #fff; color: #6b7280; border-radius: .35rem; }
    #new-builder-app .nb-width-btn.nb-width-on { background: #1f2937; color: #fff; border-color: #1f2937; }

    /* ---- sortable ---- */
    #new-builder-app .sortable-ghost { opacity: .4; }
    #new-builder-app .sortable-chosen > .nb-row { background: #e0e7ff; }

    /* ---- panes / tabs ---- */
    #new-builder-app textarea.nb-pane { width: 100%; height: 100%; resize: none; }
    #new-builder-app .nb-tab { padding: .5rem .9rem; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 2px solid transparent; background: transparent; cursor: pointer; }
    #new-builder-app .nb-tab:hover { color: #374151; }
    #new-builder-app .nb-tab.nb-tab-active { color: #2563eb; border-bottom-color: #2563eb; background: #fff; }
    #new-builder-app .nb-toolbtn:disabled { opacity: .4; cursor: not-allowed; }
    #new-builder-app .nb-col-head { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--nb-muted); }
</style>
@endpush

@section('content')
<div id="new-builder-app" class="px-4 sm:px-0" data-seed-html-wrap>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-gray-500">
            Bidirectional HTML &harr; JSON visual builder. Edit any pane &mdash; the others stay in sync.
        </p>
        <div data-toolbar class="flex items-center gap-2">
            <div class="flex items-center gap-1 mr-1">
                <button data-tool="undo" type="button" disabled title="Undo (Ctrl/Cmd+Z)"
                        class="nb-toolbtn inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    &#8630; Undo
                </button>
                <button data-tool="redo" type="button" disabled title="Redo (Ctrl/Cmd+Shift+Z)"
                        class="nb-toolbtn inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Redo &#8631;
                </button>
            </div>
            <button data-tool="add-root" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">
                + Root node
            </button>
            <button data-tool="format-json" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                Re-parse JSON
            </button>
            <button data-tool="clear" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-700 bg-white border border-red-200 rounded hover:bg-red-50">
                Clear
            </button>
        </div>
    </div>

    <div data-error class="hidden mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4" style="height: calc(100vh - 230px); min-height: 480px;">
        {{-- LEFT: Tree / HTML / JSON (tabbed) --}}
        <div class="lg:col-span-3 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div data-nbtabs class="flex border-b border-gray-200 bg-gray-50">
                <button type="button" data-nbtab="tree" class="nb-tab nb-tab-active">Tree</button>
                <button type="button" data-nbtab="html" class="nb-tab">HTML</button>
                <button type="button" data-nbtab="json" class="nb-tab">JSON</button>
            </div>
            <div class="flex-1 relative overflow-hidden">
                <div data-pane="tree" data-nbpanel="tree" class="nb-pane nb-panel absolute inset-0 overflow-auto p-2"></div>
                <textarea data-pane="html" data-nbpanel="html" spellcheck="false"
                          class="nb-pane nb-panel hidden absolute inset-0 p-3 text-xs text-gray-800 focus:outline-none border-0"></textarea>
                <textarea data-pane="json" data-nbpanel="json" spellcheck="false"
                          class="nb-pane nb-panel hidden absolute inset-0 p-3 text-xs text-gray-800 focus:outline-none border-0"></textarea>
            </div>
        </div>

        {{-- MIDDLE: live preview (widest) --}}
        <div class="lg:col-span-6 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div data-preview-toolbar class="px-3 py-2 border-b border-gray-200 bg-gray-50 flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="nb-col-head">Preview</span>
                    <div class="flex items-center gap-1">
                        <button type="button" data-width="mobile" class="nb-width-btn" title="Mobile 375px">Mobile</button>
                        <button type="button" data-width="tablet" class="nb-width-btn" title="Tablet 768px">Tablet</button>
                        <button type="button" data-width="full" class="nb-width-btn nb-width-on" title="Full width">Desktop</button>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select data-framework class="text-xs rounded border-gray-300 py-1 pr-7 focus:border-blue-500 focus:ring-blue-500">
                        <option value="tailwind">Tailwind CSS</option>
                        <option value="bootstrap">Bootstrap 5</option>
                        <option value="bulma">Bulma</option>
                        <option value="none">No framework</option>
                    </select>
                    <button type="button" data-tool="open-tab" title="Open preview in new tab"
                            class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                        &#8599; Open tab
                    </button>
                </div>
            </div>
            <div class="nb-preview-stage flex-1 overflow-auto p-3">
                <iframe data-pane="preview" title="Preview" class="w-full h-full border-0"></iframe>
            </div>
        </div>

        {{-- RIGHT: Inspector --}}
        <div class="lg:col-span-3 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="px-3 py-2.5 border-b border-gray-200 bg-gray-50">
                <span class="nb-col-head">Inspector</span>
            </div>
            <div data-inspector class="flex-1 overflow-auto p-3"></div>
        </div>
    </div>

    {{-- Seed HTML matching the JSON contract example so the page loads non-empty. --}}
    <textarea data-seed-html class="hidden">&lt;div data-somthing='abc' class='someclass' id='something'&gt;
    &lt;h1 class='sometailwindclass'&gt;something&lt;/h1&gt;
    &lt;div&gt;
        &lt;h2&gt;something&lt;/h2&gt;
        &lt;span&gt;something&lt;/span&gt;
        &lt;div class='someclass' id='something'&gt;ec&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</textarea>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
@php
    $nbModules = ['builder-core', 'utils', 'state', 'history', 'tree', 'inspector', 'preview', 'dnd', 'hover-sync', 'main'];
@endphp
@foreach ($nbModules as $nbModule)
    @php $nbPath = public_path("js/new-builder/{$nbModule}.js"); @endphp
    <script src="{{ asset("js/new-builder/{$nbModule}.js") }}?v={{ file_exists($nbPath) ? filemtime($nbPath) : 1 }}"></script>
@endforeach
<script>
    (function () {
        var app = document.getElementById('new-builder-app');
        if (!app) { return; }
        app.querySelectorAll('[data-nbtab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-nbtab');
                app.querySelectorAll('[data-nbtab]').forEach(function (b) {
                    b.classList.toggle('nb-tab-active', b.getAttribute('data-nbtab') === tab);
                });
                app.querySelectorAll('[data-nbpanel]').forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute('data-nbpanel') !== tab);
                });
            });
        });
    })();
</script>
@endpush

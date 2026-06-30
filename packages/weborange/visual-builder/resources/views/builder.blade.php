@extends(config('visual-builder.layout'))

@php
    $vbAs = config('visual-builder.as', 'visual-builder.');
    $vbModules = ['builder-core', 'utils', 'state', 'history', 'tree', 'inspector', 'preview', 'dnd', 'hover-sync', 'palette', 'elements', 'media', 'icons', 'codemodal', 'tokens', 'ai', 'save', 'main'];
@endphp

@section('title', config('visual-builder.title'))
@section('page-title', config('visual-builder.title'))

@push('styles')
{{-- Tabler Icons webfont — used ONLY to browse icons in the picker (admin). --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
{{-- CodeMirror — line-numbered editor for the per-node HTML modal. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
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
        font-size: .8rem; line-height: 1; padding: .2rem; min-width: 1.55rem; height: 1.55rem;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #d1d5db; border-radius: .25rem; background: #fff; color: #374151;
    }
    #new-builder-app .nb-actions button:hover { background: #f3f4f6; color: #111827; border-color: #9ca3af; }
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
    #new-builder-app .nb-insp-body .nb-panel { display: none; }
    #new-builder-app .nb-insp-body[data-insp-active="props"] .nb-panel-props,
    #new-builder-app .nb-insp-body[data-insp-active="css"] .nb-panel-css,
    #new-builder-app .nb-insp-body[data-insp-active="loop"] .nb-panel-loop { display: block; }
    #new-builder-app .nb-loop-filter { display: flex; gap: .35rem; }
    #new-builder-app .nb-loop-filter input { flex: 1; }
    #new-builder-app .nb-panel-loop select, #new-builder-app .nb-panel-loop input { width: 100%; font-size: .8125rem; }
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

    /* Applied-classes removable chips */
    #new-builder-app .nb-cls-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-bottom: .4rem; }
    #new-builder-app .nb-cls-chip {
        display: inline-flex; align-items: center; gap: .25rem;
        font-family: ui-monospace, Menlo, monospace; font-size: .68rem;
        padding: .12rem .3rem .12rem .45rem; border-radius: 9999px;
        background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;
    }
    #new-builder-app .nb-cls-x { border: 0; background: none; color: #6366f1; cursor: pointer; font-size: .9rem; line-height: 1; padding: 0 .1rem; }
    #new-builder-app .nb-cls-x:hover { color: #dc2626; }

    /* Smart segmented style controls */
    #new-builder-app .nb-controls { border: 1px solid var(--nb-border); border-radius: .5rem; padding: .5rem; background: #fafafa; display: flex; flex-direction: column; gap: .4rem; max-height: 320px; overflow: auto; }
    #new-builder-app .nb-ctl-row { display: flex; align-items: center; gap: .5rem; }
    #new-builder-app .nb-ctl-label { flex: 0 0 5.2rem; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #9ca3af; }
    #new-builder-app .nb-ctl-opts { display: inline-flex; flex-wrap: wrap; gap: 0; border: 1px solid #d1d5db; border-radius: .4rem; overflow: hidden; }
    #new-builder-app .nb-ctl-btn {
        font-size: .68rem; font-weight: 600; padding: .2rem .5rem; min-width: 2rem;
        background: #fff; color: #4b5563; cursor: pointer; border: 0; border-right: 1px solid #e5e7eb; transition: all .1s;
    }
    #new-builder-app .nb-ctl-opts .nb-ctl-btn:last-child { border-right: 0; }
    #new-builder-app .nb-ctl-btn:hover { background: #eff6ff; color: #2563eb; }
    #new-builder-app .nb-ctl-on { background: #2563eb; color: #fff; }
    #new-builder-app .nb-ctl-on:hover { background: #1d4ed8; color: #fff; }

    /* Content mini-WYSIWYG */
    #new-builder-app .nb-rich-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem; padding: .3rem; border: 1px solid var(--nb-border); border-bottom: 0; border-radius: .4rem .4rem 0 0; background: #f9fafb; }
    #new-builder-app .nb-rich-btn { width: 1.6rem; height: 1.6rem; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; border: 1px solid #d1d5db; border-radius: .3rem; background: #fff; color: #374151; cursor: pointer; }
    #new-builder-app .nb-rich-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }
    #new-builder-app .nb-rich-clear:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }
    #new-builder-app .nb-rich-sep { width: 1px; height: 1.1rem; background: #e5e7eb; margin: 0 .15rem; }
    #new-builder-app .nb-rich-color { width: 1.1rem; height: 1.1rem; border-radius: 9999px; border: 1px solid rgba(0,0,0,.15); cursor: pointer; padding: 0; }
    #new-builder-app .nb-rich-color:hover { transform: scale(1.15); }
    #new-builder-app .nb-rich {
        min-height: 3rem; max-height: 12rem; overflow: auto; padding: .5rem .6rem;
        border: 1px solid var(--nb-border); border-radius: 0 0 .4rem .4rem; background: #fff;
        font-size: .85rem; line-height: 1.5; color: #111827; outline: none;
    }
    #new-builder-app .nb-rich:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
    #new-builder-app .nb-rich:empty::before { content: 'Type text… select to format'; color: #9ca3af; }

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
    #new-builder-app .nb-css-scope { color: #6b7280; background: #fff; border: 1px solid #e5e7eb; cursor: pointer; }
    #new-builder-app .nb-css-scope:hover { color: #374151; }
    #new-builder-app .nb-css-scope.nb-css-scope-active { color: #2563eb; background: #eff6ff; border-color: #bfdbfe; }
    #new-builder-app .nb-toolbtn:disabled { opacity: .4; cursor: not-allowed; }
    #new-builder-app .nb-col-head { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--nb-muted); }

    /* ---- component palette ---- */
    #new-builder-app .nb-pal-group { display: flex; align-items: baseline; gap: .5rem; }
    #new-builder-app .nb-pal-group-label { flex: 0 0 5rem; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6366f1; padding-top: .15rem; }
    #new-builder-app .nb-pal-buttons { display: flex; flex-wrap: wrap; gap: .3rem; }
    #new-builder-app .nb-pal-btn { font-size: .72rem; font-weight: 600; padding: .25rem .6rem; border: 1px solid #c7d2fe; border-radius: 9999px; background: #fff; color: #4338ca; cursor: pointer; transition: all .1s; }
    #new-builder-app .nb-pal-btn:hover { background: #4f46e5; border-color: #4f46e5; color: #fff; }

    /* ---- token chips ---- */
    #new-builder-app .nb-token-chip { font-family: ui-monospace, Menlo, monospace; font-size: .7rem; font-weight: 600; padding: .2rem .5rem; border: 1px solid #f0abfc; border-radius: 9999px; background: #fff; color: #a21caf; cursor: pointer; transition: all .1s; }
    #new-builder-app .nb-token-chip:hover { background: #c026d3; border-color: #c026d3; color: #fff; }

    /* ---- image picker (inspector) ---- */
    #new-builder-app .nb-img-src-row { display: flex; gap: .35rem; }
    #new-builder-app .nb-img-src-row input { flex: 1; }
    #new-builder-app .nb-pick-btn { flex: 0 0 auto; font-size: .72rem; font-weight: 600; padding: .25rem .55rem; border: 1px solid #6366f1; border-radius: .35rem; background: #eef2ff; color: #4338ca; cursor: pointer; white-space: nowrap; }
    #new-builder-app .nb-pick-btn:hover { background: #4f46e5; color: #fff; }

    /* ---- media picker modal ---- */
    #new-builder-app .nb-media-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; padding: 2rem; }
    #new-builder-app .nb-media-overlay.hidden { display: none; }
    #new-builder-app .nb-media-modal { background: #fff; border-radius: .75rem; width: min(900px, 100%); max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,.3); }
    #new-builder-app .nb-media-head { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border-bottom: 1px solid var(--nb-border); }
    #new-builder-app .nb-media-head strong { font-size: .9rem; }
    #new-builder-app .nb-media-search { flex: 1; font-size: .8125rem; border: 1px solid #d1d5db; border-radius: .4rem; padding: .35rem .6rem; }
    #new-builder-app .nb-media-close { font-size: 1.1rem; color: #6b7280; background: none; border: none; cursor: pointer; line-height: 1; }
    #new-builder-app .nb-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .5rem; padding: 1rem; overflow: auto; }
    #new-builder-app .nb-media-item { border: 1px solid var(--nb-border); border-radius: .5rem; overflow: hidden; cursor: pointer; background: #f9fafb; aspect-ratio: 1; padding: 0; }
    #new-builder-app .nb-media-item:hover { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79,70,229,.25); }
    #new-builder-app .nb-media-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    #new-builder-app .nb-media-status { padding: 0 1rem 1rem; font-size: .8125rem; color: #6b7280; }
    #new-builder-app .nb-media-status:empty { display: none; }

    /* ---- media upload ---- */
    #new-builder-app .nb-media-upload { font-size: .8125rem; font-weight: 600; padding: .35rem .75rem; border: 1px solid #4f46e5; border-radius: .4rem; background: #eef2ff; color: #4338ca; cursor: pointer; white-space: nowrap; }
    #new-builder-app .nb-media-upload:hover { background: #4f46e5; color: #fff; }

    /* ---- element picker (＋child / ＋sib) ---- */
    #new-builder-app .nb-el-modal { background: #fff; border-radius: .75rem; width: min(620px, 100%); max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,.3); }
    #new-builder-app .nb-el-mode { font-size: .72rem; color: #9ca3af; margin-right: auto; }
    #new-builder-app .nb-el-list { padding: 1rem; overflow: auto; display: flex; flex-direction: column; gap: .75rem; }

    /* ---- make/remove repeater buttons ---- */
    #new-builder-app .nb-make-loop { display: block; width: 100%; margin-bottom: .85rem; font-size: .75rem; font-weight: 600; padding: .4rem; border: 1px dashed #7c3aed; border-radius: .4rem; background: #faf5ff; color: #6d28d9; cursor: pointer; }
    #new-builder-app .nb-make-loop:hover { background: #7c3aed; color: #fff; border-style: solid; }
    #new-builder-app .nb-unmake-loop { margin-top: .75rem; font-size: .72rem; font-weight: 600; padding: .3rem .6rem; border: 1px solid #fecaca; border-radius: .35rem; background: #fff; color: #b91c1c; cursor: pointer; }
    #new-builder-app .nb-unmake-loop:hover { background: #fef2f2; }

    /* ---- code modal (CodeMirror) ---- */
    #new-builder-app .nb-code-modal { background: #fff; border-radius: .75rem; width: min(960px, 100%); height: min(78vh, 680px); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,.3); }
    #new-builder-app .nb-code-host { flex: 1; min-height: 0; overflow: hidden; position: relative; }
    #new-builder-app .nb-code-host .CodeMirror { height: 100%; font-size: .8rem; font-family: ui-monospace, Menlo, monospace; }
    #new-builder-app .nb-code-textarea { width: 100%; height: 100%; border: 0; resize: none; padding: .75rem; font-family: ui-monospace, Menlo, monospace; font-size: .8rem; }
    #new-builder-app .nb-code-foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .65rem 1rem; border-top: 1px solid var(--nb-border); }
    #new-builder-app .nb-code-actions { display: flex; gap: .5rem; }
    #new-builder-app .nb-code-btn { font-size: .8rem; font-weight: 600; padding: .35rem .9rem; border: 1px solid #d1d5db; border-radius: .4rem; background: #fff; color: #374151; cursor: pointer; }
    #new-builder-app .nb-code-apply { background: #2563eb; border-color: #2563eb; color: #fff; }
    #new-builder-app .nb-code-apply:hover { background: #1d4ed8; }

    /* ---- icon picker ---- */
    #new-builder-app .nb-icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(54px, 1fr)); gap: .35rem; padding: 1rem; overflow: auto; }
    #new-builder-app .nb-icon-item { display: flex; align-items: center; justify-content: center; aspect-ratio: 1; border: 1px solid var(--nb-border); border-radius: .5rem; background: #fff; color: #374151; cursor: pointer; font-size: 1.4rem; }
    #new-builder-app .nb-icon-item:hover { border-color: #0d9488; color: #0d9488; background: #f0fdfa; }
    #new-builder-app .nb-icon-item .ti { line-height: 1; }

    /* ---- save result ---- */
    #new-builder-app .nb-save-result:empty { display: none; }
    #new-builder-app .nb-save-result.nb-save-ok { color: #065f46; }
    #new-builder-app .nb-save-result.nb-save-err { color: #b91c1c; }
    #new-builder-app .nb-save-result a { color: inherit; }
</style>
@endpush

@section(config('visual-builder.content_section', 'content'))
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
            <button data-ai-open type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white rounded hover:opacity-90"
                    style="background:linear-gradient(90deg,#7c3aed,#db2777)">
                &#10024; AI
            </button>
            <button data-palette-open type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                &#43; Blocks
            </button>
            <button data-tokens-open type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-fuchsia-600 rounded hover:bg-fuchsia-700">
                {&nbsp;} Tokens
            </button>
            <button data-icons-open type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-teal-600 rounded hover:bg-teal-700">
                &#9733; Icon
            </button>
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
            <a data-view-page href="#" target="_blank" rel="noopener"
               class="hidden items-center px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                &#8599; View page
            </a>
            <button data-save-open type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-l hover:bg-emerald-700">
                &#128190; Save
            </button>
            <button data-save-options type="button"
                    title="Save options — Replace page content, sections mode, save as template…"
                    class="inline-flex items-center px-2 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-r border-l border-emerald-500 hover:bg-emerald-700">
                &#9662;
            </button>
        </div>
    </div>

    <div data-error class="hidden mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

    {{-- AI generate panel --}}
    <div data-ai-config class="hidden" data-ai-url="{{ route($vbAs.'ai') }}" data-csrf="{{ csrf_token() }}"></div>
    <div data-ai-panel class="hidden mb-3 rounded-lg border border-violet-200 bg-violet-50/70 px-4 py-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-violet-800">&#10024; Generate with AI</span>
            <button data-ai-cancel type="button" class="text-xs text-gray-500 hover:text-gray-700">Close</button>
        </div>
        @if(!empty($vbStyleTemplates))
            <div class="mb-2">
                <label class="block text-xs font-medium text-violet-800 mb-1">Use a style template (optional) — the AI copies its look</label>
                <select data-ai-template class="w-full text-sm rounded border-gray-300 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">— none (free style) —</option>
                    @foreach($vbStyleTemplates as $vbT)
                        <option value="{{ $vbT['id'] }}">{{ $vbT['label'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <textarea data-ai-prompt rows="3"
                  class="w-full text-sm rounded border-gray-300 focus:border-violet-500 focus:ring-violet-500"
                  placeholder="Describe the section you want… e.g. “a hero with a heading, subtext and two buttons on a dark gradient” or “a 3-column features grid with icons”."></textarea>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-1.5 text-xs text-violet-800">
                <input data-ai-mode type="checkbox" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                Replace everything (otherwise append to the canvas)
            </label>
            <button data-ai-fixseo type="button"
                    class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded hover:bg-emerald-100 disabled:opacity-50"
                    title="Fix the heading hierarchy (one h1, correct h2/h3 order) of the whole page for SEO — review the result before saving.">
                <span data-ai-fixseo-label>&#9874; Fix SEO structure</span>
            </button>
            <button data-ai-generate type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold text-white rounded disabled:opacity-50"
                    style="background:linear-gradient(90deg,#7c3aed,#db2777)">
                <span data-ai-label>&#10024; Generate</span>
            </button>
        </div>
        <p data-ai-result class="mt-2 text-xs text-violet-700/80">Tip: be specific about layout, colours, and content. The AI returns a Tailwind HTML section you can then edit visually.</p>
    </div>

    {{-- Component palette --}}
    <script type="application/json" data-vb-forms>@json($vbForms ?? [])</script>
    <script type="application/json" data-vb-sliders>@json($vbSliders ?? [])</script>
    <script type="application/json" data-vb-nodes>@json($vbNodes ?? [])</script>
    <script type="application/json" data-vb-site-css>@json($vbSiteCss ?? '')</script>
    <div data-palette-panel class="hidden mb-3 rounded-lg border border-indigo-200 bg-indigo-50/60 px-4 py-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-indigo-800">Insert block</span>
            <button data-palette-cancel type="button" class="text-xs text-gray-500 hover:text-gray-700">Close</button>
        </div>
        <div data-palette-list class="flex flex-col gap-2"></div>
        <p class="mt-2 text-xs text-indigo-700/80">Inserts into the selected node (or at the page root if nothing is selected). Tip: select a container to nest inside it, or click empty space in the tree / the ⊘ button to deselect and add at the root.</p>
    </div>

    {{-- Token picker panel --}}
    <div data-tokens-config class="hidden" data-tokens-url="{{ route($vbAs.'tokens') }}"></div>
    <div data-tokens-panel class="hidden mb-3 rounded-lg border border-fuchsia-200 bg-fuchsia-50/60 px-4 py-3">
        <div class="flex flex-wrap items-center gap-3 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-fuchsia-800">Dynamic tokens</span>
            <select data-tokens-source class="text-sm rounded border-gray-300 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                <option value="">— source —</option>
                @foreach ($vbSources as $src)
                    <option value="{{ $src['slug'] }}">{{ $src['name'] }}</option>
                @endforeach
            </select>
            <button data-tokens-cancel type="button" class="ml-auto text-xs text-gray-500 hover:text-gray-700">Close</button>
        </div>
        <div data-tokens-list class="flex flex-wrap gap-1.5"></div>
        <p data-tokens-status class="mt-2 text-xs text-fuchsia-700/80">Pick a source to see its fields.</p>
    </div>

    {{-- Save panel --}}
    <script type="application/json" data-preview-css>@json(config('visual-builder.preview_css', []))</script>
    <div data-save-config class="hidden"
         data-save-url="{{ route($vbAs.'save') }}"
         data-sections-url="{{ route($vbAs.'sections') }}"
         data-sample-url="{{ route($vbAs.'sample') }}"
         data-slider-url="{{ route($vbAs.'render-slider') }}"
         data-entries-url="{{ route($vbAs.'entries') }}"
         data-preselect-target="{{ $vbPreselectTarget ?? '' }}"
         data-csrf="{{ csrf_token() }}"></div>
    @if (config('visual-builder.media_url'))
        <div data-media-config class="hidden"
             data-media-url="{{ config('visual-builder.media_url') }}"
             data-upload-url="{{ config('visual-builder.upload_url') }}"
             data-csrf="{{ csrf_token() }}"></div>
    @endif
    <div data-save-panel class="hidden mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-1">Target</label>
                <select data-save-page class="w-full text-sm rounded border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                    @forelse ($vbTargets as $t)
                        <option value="{{ $t['id'] }}" data-mode="{{ $t['mode'] ?? '' }}" data-url="{{ $t['url'] ?? '' }}">{{ $t['label'] }}</option>
                    @empty
                        <option value="">No targets available</option>
                    @endforelse
                </select>
            </div>
            <div class="min-w-[200px]">
                <label class="block text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-1">Section</label>
                <div class="flex gap-1.5">
                    <select data-save-section class="flex-1 text-sm rounded border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">➕ New section</option>
                    </select>
                    <button data-save-load type="button" title="Load this section into the builder"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-white border border-emerald-300 rounded hover:bg-emerald-100 disabled:opacity-40"
                            disabled>Load</button>
                </div>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-1">Section name</label>
                <input data-save-name type="text" value="Builder section"
                       class="w-full text-sm rounded border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div class="flex flex-col gap-1.5 pb-1">
                <label class="inline-flex items-center gap-1.5 text-xs text-emerald-800">
                    <input data-save-convert type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    Switch to sections mode if needed
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-red-700"
                       title="Soft-deletes this page's existing sections (recoverable) and saves the builder output as its content. Use to finish a migration.">
                    <input data-save-replace type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    Replace page content (finish migration)
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-violet-800"
                       title="Mark this page as a reusable style template — it will appear in the AI panel's 'Use template' picker so new pages can be generated in its style.">
                    <input data-save-template type="checkbox" @checked($vbIsTemplate ?? false) class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    Save as style template (reusable by AI)
                </label>
            </div>
            <div class="flex items-center gap-2 pb-1">
                <button data-save-submit type="button"
                        class="inline-flex items-center px-4 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded hover:bg-emerald-700 disabled:opacity-50">
                    Save section
                </button>
                <button data-save-cancel type="button"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-emerald-200">
            <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800">
                <input data-save-loop type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                Save as dynamic loop (repeat this design once per item)
            </label>
            <div data-save-loop-config class="hidden mt-2 flex flex-wrap items-end gap-3">
                <div class="min-w-[170px]">
                    <label class="block text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-800 mb-1">Source</label>
                    <select data-loop-source class="w-full text-sm rounded border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— pick a collection —</option>
                        @foreach ($vbSources as $src)
                            <option value="{{ $src['slug'] }}">{{ $src['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-20">
                    <label class="block text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-800 mb-1">Columns</label>
                    <select data-loop-columns class="w-full text-sm rounded border-gray-300">
                        <option>1</option><option>2</option><option selected>3</option><option>4</option>
                    </select>
                </div>
                <div class="w-20">
                    <label class="block text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-800 mb-1">Limit</label>
                    <input data-loop-limit type="number" value="12" min="1" class="w-full text-sm rounded border-gray-300">
                </div>
                <div class="w-28">
                    <label class="block text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-800 mb-1">Order</label>
                    <select data-loop-order class="w-full text-sm rounded border-gray-300">
                        <option value="created_at|desc">Newest</option>
                        <option value="created_at|asc">Oldest</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-800 mb-1">Heading (optional)</label>
                    <input data-loop-heading type="text" placeholder="e.g. Latest posts" class="w-full text-sm rounded border-gray-300">
                </div>
            </div>
        </div>
        <p class="mt-2 text-xs text-emerald-700">Appends the current builder output as a section to the chosen target. Tip: in loop mode, build ONE card and use {tokens} for per-item values.</p>
        <div data-save-result class="mt-2 text-sm"></div>
    </div>

    {{-- Full-width builder: hide the host admin sidebar + trim chrome so the
         3 panes (and especially the preview) get the whole screen. Scoped to this
         page only — the style tag is loaded just by the builder view. --}}
    <style>
        body .flex.h-screen > aside { display: none !important; }
        body .flex.h-screen > div > main { padding: .5rem !important; }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3" style="height: calc(100vh - 150px); min-height: 480px;">
        {{-- LEFT: Tree / HTML / JSON (tabbed) --}}
        <div class="lg:col-span-2 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div data-nbtabs class="flex items-center border-b border-gray-200 bg-gray-50">
                <button type="button" data-nbtab="tree" class="nb-tab nb-tab-active">Tree</button>
                <button type="button" data-nbtab="html" class="nb-tab">HTML</button>
                <button type="button" data-nbtab="json" class="nb-tab">JSON</button>
                <button type="button" data-nbtab="css" class="nb-tab" title="Global CSS — loads in the preview and on the live page">CSS</button>
                <div class="ml-auto flex items-center gap-0.5 pr-1.5">
                    <button type="button" data-tree-deselect title="Deselect — next block inserts at the page root"
                            class="px-1.5 py-1 text-gray-400 hover:text-blue-600 leading-none text-sm">&#8856;</button>
                    <button type="button" data-tree-expand-all title="Expand all"
                            class="px-1.5 py-1 text-gray-400 hover:text-blue-600 leading-none text-sm">&#9662;</button>
                    <button type="button" data-tree-collapse-all title="Collapse all"
                            class="px-1.5 py-1 text-gray-400 hover:text-blue-600 leading-none text-sm">&#9652;</button>
                </div>
            </div>
            <div class="flex-1 relative overflow-hidden">
                <div data-pane="tree" data-nbpanel="tree" class="nb-pane nb-panel absolute inset-0 overflow-auto p-2"></div>
                <textarea data-pane="html" data-nbpanel="html" spellcheck="false"
                          class="nb-pane nb-panel hidden absolute inset-0 p-3 text-xs text-gray-800 focus:outline-none border-0"></textarea>
                <textarea data-pane="json" data-nbpanel="json" spellcheck="false"
                          class="nb-pane nb-panel hidden absolute inset-0 p-3 text-xs text-gray-800 focus:outline-none border-0"></textarea>
                <div data-nbpanel="css" class="nb-pane nb-panel hidden absolute inset-0 flex flex-col">
                    <div class="flex items-center gap-1 px-2 py-1.5 border-b border-gray-200 bg-gray-50 text-xs">
                        <span class="text-gray-500 mr-1">Apply to:</span>
                        <button type="button" data-css-scope-btn="page"
                                class="nb-css-scope nb-css-scope-active px-2 py-1 rounded font-medium">This page</button>
                        <button type="button" data-css-scope-btn="site"
                                class="nb-css-scope px-2 py-1 rounded font-medium">All pages</button>
                    </div>
                    <div class="flex-1 relative overflow-hidden">
                        <textarea data-global-css data-css-scope="page" spellcheck="false"
                                  placeholder="/* This page only — saved into this page's content; shows in the preview and on the published page. */&#10;.my-class { color: #4f46e5; }&#10;@media (max-width: 640px) { h1 { font-size: 1.5rem; } }"
                                  class="absolute inset-0 p-3 font-mono text-xs text-gray-800 focus:outline-none border-0 resize-none"></textarea>
                        <textarea data-site-css data-css-scope="site" spellcheck="false"
                                  placeholder="/* All pages (site-wide) — saved to Settings → Integrations; applies to every front-end page. */&#10;.spacing { margin-bottom: 20px; }"
                                  class="hidden absolute inset-0 p-3 font-mono text-xs text-gray-800 focus:outline-none border-0 resize-none"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- MIDDLE: live preview (widest) --}}
        <div class="lg:col-span-8 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
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
        <div class="lg:col-span-2 flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="px-3 py-2.5 border-b border-gray-200 bg-gray-50">
                <span class="nb-col-head">Inspector</span>
            </div>
            <div data-inspector class="flex-1 overflow-auto p-3"></div>
        </div>
    </div>

    {{-- Seed HTML: a migrated target's content when ?target is set, else the example. --}}
    @if (!empty($vbSeedHtml ?? null))
        <textarea data-seed-html class="hidden">{{ $vbSeedHtml }}</textarea>
    @else
        <textarea data-seed-html class="hidden">&lt;div data-somthing='abc' class='someclass' id='something'&gt;
    &lt;h1 class='sometailwindclass'&gt;something&lt;/h1&gt;
    &lt;div&gt;
        &lt;h2&gt;something&lt;/h2&gt;
        &lt;span&gt;something&lt;/span&gt;
        &lt;div class='someclass' id='something'&gt;ec&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</textarea>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closetag.min.js"></script>
<script src="{{ route($vbAs.'asset', ['file' => 'Sortable.min.js']) }}?v={{ $vbAssetVersion }}"></script>
@foreach ($vbModules as $vbModule)
    <script src="{{ route($vbAs.'asset', ['file' => "new-builder/{$vbModule}.js"]) }}?v={{ $vbAssetVersion }}"></script>
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

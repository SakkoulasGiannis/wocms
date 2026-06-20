<style>
.editorjs-container .ce-block__content,
.editorjs-container .ce-toolbar__content {
    max-width: calc(100% - 2rem) !important;
    margin: 0 0 0 2rem !important;
}
/* RawTool: the vendored CSS forces min-height:200px which makes a 1-line
   HTML snippet render as a huge box. Auto-size to content instead. */
.ce-rawtool__textarea {
    min-height: 2.75rem !important;
    height: auto;
    field-sizing: content; /* Chrome 123+: grow/shrink to fit content */
    max-height: 60vh;
    overflow-y: auto;
    line-height: 1.5;
}
/* LiveHtml block — renders pasted markup styled & editable in place. */
.ce-livehtml { position: relative; border: 1px dashed #c7d2fe; border-radius: 8px; margin: 4px 0; }
.ce-livehtml__bar { display: flex; justify-content: flex-end; gap: 6px; padding: 4px 6px; border-bottom: 1px dashed #e0e7ff; background: #f5f7ff; border-radius: 8px 8px 0 0; }
.ce-livehtml__btn { font-size: 11px; font-weight: 600; color: #4f46e5; background: #fff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 2px 8px; cursor: pointer; }
.ce-livehtml__btn:hover { background: #eef2ff; }
.ce-livehtml__content { outline: none; }
.ce-livehtml__content:focus { outline: none; }
.ce-livehtml__content img { cursor: pointer !important; }
.ce-livehtml__content img:hover { outline: 2px solid #6366f1 !important; outline-offset: 2px; }
.ce-livehtml__source { display:block; width: 100%; box-sizing: border-box; height: 55vh; min-height: 18rem; max-height: 85vh; resize: vertical; overflow: auto; white-space: pre-wrap; word-break: break-word; tab-size: 2; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; line-height: 1.55; padding: 12px; border: 0; border-radius: 0 0 8px 8px; background: #0f172a; color: #e2e8f0; }
/* Style mode — click any element to edit its classes */
.ce-livehtml__btn--active { background: #4f46e5 !important; color: #fff !important; border-color: #4f46e5 !important; }
.ce-livehtml--style .ce-livehtml__content * { cursor: pointer; }
.ce-livehtml--style .ce-livehtml__content *:hover { outline: 1px dashed #818cf8 !important; outline-offset: 1px; }
.ce-livehtml-style-pop { position: fixed; z-index: 100001; width: 280px; background: #fff; border: 1px solid #c7d2fe; border-radius: 10px; box-shadow: 0 12px 40px rgba(15,23,42,.25); font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; overflow: hidden; }
.ce-livehtml-style-pop__head { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: #f5f7ff; border-bottom: 1px solid #e0e7ff; font-size: 12px; color: #334155; }
.ce-livehtml-style-pop__head b { color: #4f46e5; font-family: ui-monospace, monospace; }
.ce-livehtml-style-pop__x { border: 0; background: transparent; color: #94a3b8; cursor: pointer; font-size: 13px; padding: 2px 6px; border-radius: 4px; }
.ce-livehtml-style-pop__x:hover { background: #e2e8f0; color: #475569; }
.ce-livehtml-style-pop__input { display:block; width: 100%; box-sizing: border-box; min-height: 72px; resize: vertical; border: 0; outline: none; padding: 10px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; line-height: 1.5; color: #0f172a; }
.ce-livehtml-style-pop__imgbtn { display: block; width: 100%; box-sizing: border-box; border: 0; border-top: 1px solid #e0e7ff; background: #eef2ff; color: #4f46e5; font-size: 12px; font-weight: 600; padding: 8px; cursor: pointer; }
.ce-livehtml-style-pop__imgbtn:hover { background: #e0e7ff; }
.editorjs-container .codex-editor__redactor {
    padding-bottom: 100px;
    padding-right: 4rem;
}
/* Fullscreen mode — body-level class so cascade wins for all existing AND future blocks */
body.editorjs-fullscreen-mode { overflow: hidden; }

/* ── Visual page editor wysiwyg: dedicated scrollable container ───────
   Use a real CSS class instead of Alpine :style. Inline-style swaps via
   Alpine's cssText replacement were race-conditioning with EditorJS's own
   sizing, so the container ended up without overflow rules in some cases.
   These !important rules deterministically win the cascade. */
#ejs-fullscreen-scrollable.is-fullscreen {
    position: fixed !important;
    top: 56px !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    overflow-y: scroll !important;
    overflow-x: hidden !important;
    background: #ffffff !important;
    z-index: 1000 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}
#ejs-fullscreen-scrollable.is-fullscreen > * {
    width: 100% !important;
    min-height: calc(100vh + 200px) !important;
    padding: 15px !important;
    box-sizing: border-box !important;
}
#ejs-fullscreen-scrollable.is-collapsed {
    position: fixed !important;
    left: 0 !important;
    right: 0 !important;
    top: 100vh !important;
    width: 100% !important;
    height: 100vh !important;
    overflow: hidden !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

body.editorjs-fullscreen-mode .editorjs-container,
body.editorjs-fullscreen-mode .editorjs-container .codex-editor,
body.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor,
html body.editorjs-fullscreen-mode .editorjs-container .codex-editor--narrow .codex-editor__redactor {
    max-width: 100% !important;
    width: 100% !important;
}
/* Allow the editor to grow naturally inside the fullscreen scrollable wrapper.
   The wrapper (absolute top-14 bottom-0 overflow-y-auto) handles the scroll. */
body.editorjs-fullscreen-mode .editorjs-container {
    overflow: visible !important;
}
body.editorjs-fullscreen-mode .editorjs-container .codex-editor,
body.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor {
    overflow: visible !important;
    max-height: none !important;
}
body.editorjs-fullscreen-mode .editorjs-container .ce-block__content,
body.editorjs-fullscreen-mode .editorjs-container .ce-toolbar__content,
html body.editorjs-fullscreen-mode .editorjs-container .codex-editor--narrow .ce-block__content,
html body.editorjs-fullscreen-mode .editorjs-container .codex-editor--narrow .ce-toolbar__content {
    max-width: 100% !important;
    margin: 0 !important;
    width: 100% !important;
}
body.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor {
    padding-right: 5rem !important;
}
/* Keep legacy wrapper-class support (for any callers that apply it on the wrapper) */
.editorjs-fullscreen-mode .editorjs-container,
.editorjs-fullscreen-mode .editorjs-container .codex-editor,
.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor {
    max-width: 100% !important;
    width: 100% !important;
}
.editorjs-fullscreen-mode .editorjs-container .ce-block__content,
.editorjs-fullscreen-mode .editorjs-container .ce-toolbar__content {
    max-width: 100% !important;
    margin: 0 !important;
}
.editorjs-container .codex-editor {
    font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
}
.editorjs-container .ce-paragraph {
    line-height: 1.6;
}
.editorjs-container .cdx-block {
    padding: 0.25rem 0;
}
.editorjs-container .ce-toolbar__plus,
.editorjs-container .ce-toolbar__settings-btn {
    color: #6b7280;
}
.editorjs-container .ce-toolbar__plus:hover,
.editorjs-container .ce-toolbar__settings-btn:hover {
    color: #111827;
    background: #f3f4f6;
}
/* Drag handle hint: settings button doubles as drag handle for reordering blocks */
.editorjs-container .ce-toolbar__settings-btn {
    cursor: grab;
}
.editorjs-container .ce-toolbar__settings-btn:active {
    cursor: grabbing;
}
.editorjs-container .ce-toolbar__settings-btn::before {
    content: '⋮⋮';
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: #94a3b8;
    letter-spacing: -2px;
    opacity: 0;
    transition: opacity .15s ease;
    pointer-events: none;
}
.editorjs-container .ce-toolbar__settings-btn:hover::before,
.editorjs-container .ce-block:hover .ce-toolbar__settings-btn::before {
    opacity: 1;
}
/* Slight visual cue when block is being dragged */
.editorjs-container .ce-block.ce-block--drag-over {
    background: linear-gradient(to bottom, transparent, rgba(21, 99, 223, 0.05));
    border-top: 2px solid #1563df;
}

/* Nested editor handling — when the user is hovering a nested codex-editor (inside
   a Container or similar), hide the OUTER editor's plus/settings toolbar so they
   don't overlap and prevent each other from being clicked. */
.editorjs-container .codex-editor:has(.codex-editor:hover) > .ce-toolbar .ce-toolbar__plus,
.editorjs-container .codex-editor:has(.codex-editor:hover) > .ce-toolbar .ce-toolbar__settings-btn {
    opacity: 0 !important;
    pointer-events: none !important;
    transition: opacity .15s ease;
}
/* Same for the reorder arrows — outer arrows hide when hovering inner blocks */
.editorjs-container .codex-editor:has(.codex-editor:hover) > .codex-editor__redactor > .ce-block > .ej-reorder-bar {
    display: none !important;
}

/* ── Block settings / tunes popover — must sit ABOVE nested editor content ──
   The Container tool's per-breakpoint width panel (Mobile/Tablet/Desktop) was
   getting covered by nested column content and clipped off the right edge,
   so it couldn't be clicked. Fixes:
   - high z-index so nested editors never paint over it
   - capped height + vertical scroll (no more clipped tall panels)
   - no horizontal scrollbar; keep the panel within a sane width
   - never clipped by an ancestor's overflow                               */
.editorjs-container .ce-popover,
.editorjs-container .ce-popover__container,
.editorjs-container .ce-settings,
.editorjs-container .ce-inline-toolbar,
.editorjs-container .ce-conversion-toolbar,
.editorjs-container .ce-toolbar__actions {
    z-index: 1200 !important;
}
/* Give the popover a DEFINITE comfortable width (not just max-width — a bare
   max-width lets EditorJS size it narrow, then the 280px width panel overflows
   and a horizontal scrollbar appears). Forbid horizontal scrolling on the
   popover AND every inner wrapper so the Container width panel is never
   clipped. Selectors are unscoped because EditorJS may render the popover
   outside .editorjs-container — the ce- prefix is specific enough. */
.ce-popover,
.ce-popover__container {
    width: 320px !important;
    max-width: calc(100vw - 24px) !important;
    max-height: 72vh !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    box-sizing: border-box !important;
}
.ce-popover__items,
.ce-popover-item-html,
.ce-popover__custom-content,
.ce-settings,
.ce-settings__default-zone,
.ce-settings__plugin-zone {
    overflow-x: hidden !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
/* Nested editors must not create stacking contexts that trap the popover
   above, nor paint over it. Keep their content below the toolbars. */
.editorjs-container .ctr-tool-wrap .codex-editor {
    z-index: 0;
}
/* The settings panel content (renderSettings output) and its controls fill
   the popover width — never force a min-width that overflows. */
.ce-popover [data-ctr-settings] {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
.ce-popover [data-ctr-settings] select,
.ce-popover [data-ctr-settings] input {
    max-width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}
.editorjs-container .cdx-settings-button:hover,
.editorjs-container .cdx-settings-button--active {
    background: #dbeafe;
    color: #1d4ed8;
}
/* Heading visual styles inside the editor — match what users expect on the frontend.
   Use !important + multiple selectors to win over Tailwind preflight which resets
   `h1-h6 { font-size: inherit; font-weight: inherit; }` in the admin layout's app.css. */
.editorjs-container h1,
.editorjs-container h1.ce-header,
.editorjs-container .ce-block h1            { font-size: 2.25rem  !important; font-weight: 800 !important; line-height: 1.2  !important; margin: 0.4em 0 !important; color: #0f172a !important; }
.editorjs-container h2,
.editorjs-container h2.ce-header,
.editorjs-container .ce-block h2            { font-size: 1.75rem  !important; font-weight: 700 !important; line-height: 1.25 !important; margin: 0.4em 0 !important; color: #0f172a !important; }
.editorjs-container h3,
.editorjs-container h3.ce-header,
.editorjs-container .ce-block h3            { font-size: 1.4rem   !important; font-weight: 700 !important; line-height: 1.3  !important; margin: 0.4em 0 !important; color: #1f2937 !important; }
.editorjs-container h4,
.editorjs-container h4.ce-header,
.editorjs-container .ce-block h4            { font-size: 1.2rem   !important; font-weight: 600 !important; line-height: 1.35 !important; margin: 0.4em 0 !important; color: #1f2937 !important; }
.editorjs-container h5,
.editorjs-container h5.ce-header,
.editorjs-container .ce-block h5            { font-size: 1.05rem  !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.4em 0 !important; color: #374151 !important; }
.editorjs-container h6,
.editorjs-container h6.ce-header,
.editorjs-container .ce-block h6            { font-size: 0.95rem  !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.4em 0 !important; color: #4b5563 !important; text-transform: uppercase !important; letter-spacing: 0.04em !important; }
/* Prevent the minHeight ghost paragraph from showing a second placeholder */
.editorjs-container .codex-editor--empty .ce-block:not(:first-child) [data-placeholder]::before,
.editorjs-container .codex-editor--empty .ce-block:not(:first-child) [data-placeholder]:empty::before {
    display: none !important;
}

/* Container tool — free it from the 650px parent cap so live width preview works */
.editorjs-container .ce-block__content:has(.ctr-tool-wrap),
.editorjs-container .ce-toolbar__content:has(+ .ce-block .ctr-tool-wrap) {
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
}
.editorjs-container .ctr-tool-wrap {
    margin: 0 auto;
    transition: max-width .18s ease;
}

/* ── Tailwind Class Picker modal ─────────────────────────────── */
.tw-picker-overlay{position:fixed;inset:0;background:rgba(17,24,39,0.55);z-index:10001;display:flex;align-items:center;justify-content:center;padding:24px;animation:twPickerFade .15s ease-out}
@keyframes twPickerFade{from{opacity:0}to{opacity:1}}
.tw-picker-modal{background:#fff;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,0.3);width:100%;max-width:720px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif}
.tw-picker-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:linear-gradient(to bottom,#fafbfc,#f3f4f6)}
.tw-picker-title{font-size:15px;font-weight:700;color:#111827;display:flex;align-items:center;gap:8px}
.tw-picker-title-icon{width:26px;height:26px;border-radius:6px;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;font-weight:700;font-family:monospace;font-size:12px;display:inline-flex;align-items:center;justify-content:center}
.tw-picker-close{background:none;border:none;color:#6b7280;cursor:pointer;padding:4px;border-radius:4px;display:flex}
.tw-picker-close:hover{background:#e5e7eb;color:#111827}
.tw-picker-search-wrap{padding:12px 20px;border-bottom:1px solid #f3f4f6;background:#fff;position:relative}
.tw-picker-search{width:100%;padding:10px 12px 10px 36px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#f9fafb;transition:all .15s}
.tw-picker-search:focus{outline:none;border-color:#6366f1;background:#fff;box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
.tw-picker-search-icon{position:absolute;left:32px;top:50%;transform:translateY(-50%);color:#9ca3af;width:16px;height:16px;pointer-events:none}
.tw-picker-tabs{display:flex;gap:2px;padding:0 20px;border-bottom:1px solid #e5e7eb;background:#fff;overflow-x:auto;scrollbar-width:none}
.tw-picker-tabs::-webkit-scrollbar{display:none}
.tw-picker-tab{padding:8px 12px;border:none;background:none;font-size:12px;color:#6b7280;cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;font-weight:500;transition:all .12s}
.tw-picker-tab:hover{color:#111827}
.tw-picker-tab.active{color:#6366f1;border-bottom-color:#6366f1}
.tw-picker-body{flex:1;overflow-y:auto;padding:16px 20px;background:#fafbfc}
.tw-picker-selected{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px;margin-bottom:14px;min-height:52px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start}
.tw-picker-selected-empty{color:#9ca3af;font-size:12px;font-style:italic;align-self:center;padding:8px 4px}
.tw-picker-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 4px 4px 10px;background:linear-gradient(135deg,#eef2ff,#e0e7ff);border:1px solid #c7d2fe;color:#4338ca;border-radius:16px;font-size:12px;font-family:ui-monospace,monospace;font-weight:600;animation:twChipIn .12s ease-out}
.tw-picker-chip.tw-picker-chip-custom{background:linear-gradient(135deg,#fef3c7,#fde68a);border-color:#fbbf24;color:#92400e}
@keyframes twChipIn{from{opacity:0;transform:scale(0.85)}to{opacity:1;transform:scale(1)}}
.tw-picker-chip-x{width:18px;height:18px;border-radius:50%;background:#fff;color:#6b7280;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;border:none;margin-left:4px;font-size:11px;line-height:1;transition:all .12s;padding:0}
.tw-picker-chip-x:hover{background:#ef4444;color:#fff}
.tw-picker-custom-wrap{display:flex;gap:6px;margin-bottom:14px}
.tw-picker-custom-input{flex:1;padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:12px;font-family:ui-monospace,monospace;background:#fff}
.tw-picker-custom-input:focus{outline:none;border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.1)}
.tw-picker-custom-btn{padding:8px 14px;border:none;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .12s}
.tw-picker-custom-btn:hover{background:linear-gradient(135deg,#d97706,#b45309)}
.tw-picker-group{margin-bottom:18px}
.tw-picker-group-title{font-size:10px;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;font-weight:700;margin-bottom:6px;padding-left:2px}
.tw-picker-classes{display:flex;flex-wrap:wrap;gap:4px}
.tw-picker-pill{padding:4px 9px;background:#fff;border:1px solid #e5e7eb;color:#374151;border-radius:6px;font-size:11px;font-family:ui-monospace,monospace;cursor:pointer;transition:all .12s;font-weight:500}
.tw-picker-pill:hover{background:#eef2ff;border-color:#6366f1;color:#4338ca;transform:translateY(-1px)}
.tw-picker-pill.active{background:linear-gradient(135deg,#6366f1,#8b5cf6);border-color:transparent;color:#fff}
.tw-picker-footer{display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid #e5e7eb;background:#fff}
.tw-picker-preview{font-family:ui-monospace,monospace;font-size:11px;color:#6b7280;max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tw-picker-actions{display:flex;gap:8px}
.tw-picker-btn{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .12s}
.tw-picker-btn-cancel{background:#f3f4f6;color:#374151}
.tw-picker-btn-cancel:hover{background:#e5e7eb}
.tw-picker-btn-apply{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
.tw-picker-btn-apply:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 12px rgba(99,102,241,0.3)}
.tw-picker-btn-clear{background:transparent;color:#dc2626;font-size:12px}
.tw-picker-btn-clear:hover{background:#fef2f2}
.tw-picker-empty{text-align:center;color:#9ca3af;padding:40px 20px;font-size:13px}
</style>

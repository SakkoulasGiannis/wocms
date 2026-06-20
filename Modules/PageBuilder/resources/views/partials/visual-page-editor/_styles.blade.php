<style>
.ve-ghost { opacity: 0.4; background: #ede9fe !important; border-radius: 6px; }
.ve-dragging { opacity: 0.9; }
.ve-children-list { transition: background 0.15s; }
.ve-children-list.sortable-over { background: #f5f3ff; border-color: #a78bfa !important; }

/* RawTool: vendored CSS forces min-height:200px -> a 1-line HTML snippet
   renders as a huge box. Auto-size to content instead. */
.ce-rawtool__textarea {
    min-height: 2.75rem !important;
    height: auto;
    field-sizing: content; /* Chrome 123+: grow/shrink to fit content */
    max-height: 60vh;
    overflow-y: auto;
    line-height: 1.5;
}

/* Editor heading visual styles — duplicated here so they always load on the
   visual editor page (regardless of whether x-editorjs-field's stack push fired).
   !important wins over Tailwind preflight (h1-h6 -> font-size: inherit) in app.css. */
.editorjs-container h1:not(.ce-livehtml__content *), .editorjs-container h1.ce-header, .editorjs-container .ce-block h1:not(.ce-livehtml__content *) { font-size: 2.25rem !important; font-weight: 800 !important; line-height: 1.2  !important; margin: 0.4em 0 !important; color: #0f172a !important; }
.editorjs-container h2:not(.ce-livehtml__content *), .editorjs-container h2.ce-header, .editorjs-container .ce-block h2:not(.ce-livehtml__content *) { font-size: 1.75rem !important; font-weight: 700 !important; line-height: 1.25 !important; margin: 0.4em 0 !important; color: #0f172a !important; }
.editorjs-container h3:not(.ce-livehtml__content *), .editorjs-container h3.ce-header, .editorjs-container .ce-block h3:not(.ce-livehtml__content *) { font-size: 1.4rem  !important; font-weight: 700 !important; line-height: 1.3  !important; margin: 0.4em 0 !important; color: #1f2937 !important; }
.editorjs-container h4:not(.ce-livehtml__content *), .editorjs-container h4.ce-header, .editorjs-container .ce-block h4:not(.ce-livehtml__content *) { font-size: 1.2rem  !important; font-weight: 600 !important; line-height: 1.35 !important; margin: 0.4em 0 !important; color: #1f2937 !important; }
.editorjs-container h5:not(.ce-livehtml__content *), .editorjs-container h5.ce-header, .editorjs-container .ce-block h5:not(.ce-livehtml__content *) { font-size: 1.05rem !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.4em 0 !important; color: #374151 !important; }
.editorjs-container h6:not(.ce-livehtml__content *), .editorjs-container h6.ce-header, .editorjs-container .ce-block h6:not(.ce-livehtml__content *) { font-size: 0.95rem !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.4em 0 !important; color: #4b5563 !important; text-transform: uppercase !important; letter-spacing: 0.04em !important; }
/* Inside the Live HTML block, the editor's heading defaults above are scoped out
   (:not(.ce-livehtml__content *)) so headings/text use ONLY their own pasted
   Tailwind classes — the Style class editor can now actually change colors/sizes. */

/* ── Container/Columns settings popover ONLY ──────────────────────────────
   Scoped with :has([data-ctr-settings]) so it targets the popover that holds
   the Mobile/Tablet/Desktop width panel and NOTHING else — the EditorJS
   toolbox / block-add menu (also .ce-popover) is untouched. Gives the panel
   enough width and kills the horizontal scrollbar. */
.ce-popover:has([data-ctr-settings]),
.ce-popover__container:has([data-ctr-settings]) {
    width: 320px !important;
    max-width: calc(100vw - 24px) !important;
    max-height: 78vh !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    box-sizing: border-box !important;
    /* Sit above the column blocks / editor toolbars — the popover's top
       (Mobile width) was painting behind the columns row. */
    z-index: 2147483000 !important;
}
.ce-popover:has([data-ctr-settings]) [data-ctr-settings] {
    width: 100% !important; min-width: 0 !important; max-width: 100% !important;
    box-sizing: border-box !important;
}
.ce-popover:has([data-ctr-settings]) [data-ctr-settings] select,
.ce-popover:has([data-ctr-settings]) [data-ctr-settings] input {
    max-width: 100% !important; min-width: 0 !important; box-sizing: border-box !important;
}
</style>

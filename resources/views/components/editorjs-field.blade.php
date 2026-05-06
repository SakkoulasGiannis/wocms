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
])

@php
    $uid = $uid ?? 'ejs-' . str_replace(['.', '[', ']'], '-', $name) . '-' . Str::random(6);
@endphp

<div
    wire:ignore
    x-data="editorjsField({
        uid: '{{ $uid }}',
        wireModel: {{ $wireModel ? "'" . $wireModel . "'" : 'null' }},
        initialValue: {{ json_encode($value) }},
        uploadImageUrl: '{{ route('admin.editorjs.upload-image') }}',
        fetchImageUrl: '{{ route('admin.editorjs.fetch-image') }}',
        uploadFileUrl: '{{ route('admin.editorjs.upload-file') }}',
        csrfToken: '{{ csrf_token() }}',
        placeholder: {{ json_encode($placeholder) }},
    })"
    x-init="init()"
    x-on:livewire:navigated.window="destroy()"
>
    <div id="{{ $uid }}" class="editorjs-container" style="min-height: {{ $minHeight }}; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff; padding: 0.5rem 0;"></div>
</div>

@once
@push('styles')
<style>
.editorjs-container .ce-block__content,
.editorjs-container .ce-toolbar__content {
    max-width: calc(100% - 2rem) !important;
    margin: 0 0 0 2rem !important;
}
.editorjs-container .codex-editor__redactor {
    padding-bottom: 100px;
    padding-right: 4rem;
}
/* Fullscreen mode — body-level class so cascade wins for all existing AND future blocks */
body.editorjs-fullscreen-mode { overflow: hidden; }
body.editorjs-fullscreen-mode .editorjs-container,
body.editorjs-fullscreen-mode .editorjs-container .codex-editor,
body.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor,
html body.editorjs-fullscreen-mode .editorjs-container .codex-editor--narrow .codex-editor__redactor {
    max-width: 100% !important;
    width: 100% !important;
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
@endpush

@push('scripts')
{{-- EditorJS Core --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.30.8/dist/editorjs.umd.min.js"></script>

{{-- Block Tools --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.7/dist/header.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/nested-list@1.4.2/dist/nested-list.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.7.6/dist/quote.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2.9.3/dist/code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1.4.2/dist/delimiter.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@2.10.1/dist/image.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@2.7.6/dist/embed.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@2.4.3/dist/table.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/link@2.6.2/dist/link.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/raw@2.5.0/dist/raw.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@1.6.0/dist/checklist.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/warning@1.4.0/dist/warning.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/attaches@1.3.2/dist/attaches.umd.js"></script>

{{-- Inline Tools --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@1.4.0/dist/marker.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.5.0/dist/inline-code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@1.2.1/dist/underline.umd.js"></script>

{{-- Undo/Redo (Ctrl+Z / Ctrl+Shift+Z). Re-enabled with v2.0.28 — wrapped in try/catch
     at the call site so any bug with our custom tools degrades gracefully instead of
     breaking the whole editor. --}}
<script src="https://cdn.jsdelivr.net/npm/editorjs-undo@2.0.28/dist/bundle.js"></script>

<script>
/* ─── Tailwind Class Picker: shared modal for block class tune ─── */
window.TAILWIND_CLASS_CATALOG = window.TAILWIND_CLASS_CATALOG || {
    'Typography': {
        'Font size':     ['text-xs','text-sm','text-base','text-lg','text-xl','text-2xl','text-3xl','text-4xl','text-5xl','text-6xl','text-7xl'],
        'Font weight':   ['font-thin','font-light','font-normal','font-medium','font-semibold','font-bold','font-extrabold','font-black'],
        'Italic / style':['italic','not-italic','underline','line-through','no-underline','uppercase','lowercase','capitalize','normal-case','tracking-tighter','tracking-tight','tracking-normal','tracking-wide','tracking-wider'],
        'Line height':   ['leading-none','leading-tight','leading-snug','leading-normal','leading-relaxed','leading-loose'],
        'Alignment':     ['text-left','text-center','text-right','text-justify'],
    },
    'Colors': {
        'Text':   ['text-white','text-black','text-slate-500','text-slate-700','text-slate-900','text-gray-500','text-gray-700','text-gray-900','text-red-500','text-red-600','text-orange-500','text-amber-500','text-yellow-500','text-green-500','text-green-600','text-teal-500','text-blue-500','text-blue-600','text-indigo-500','text-purple-500','text-purple-600','text-pink-500'],
        'Background': ['bg-white','bg-black','bg-slate-50','bg-slate-100','bg-slate-200','bg-slate-800','bg-slate-900','bg-gray-50','bg-gray-100','bg-red-50','bg-red-100','bg-orange-50','bg-yellow-50','bg-green-50','bg-green-100','bg-blue-50','bg-blue-100','bg-indigo-50','bg-purple-50','bg-pink-50','bg-gradient-to-r','bg-gradient-to-br','from-blue-500','to-purple-500','from-indigo-500','to-pink-500'],
    },
    'Spacing': {
        'Margin':        ['m-0','m-1','m-2','m-3','m-4','m-6','m-8','mx-auto','my-auto','mt-0','mt-2','mt-4','mt-6','mt-8','mt-10','mt-12','mb-0','mb-2','mb-4','mb-6','mb-8','mb-10','mb-12'],
        'Padding':       ['p-0','p-1','p-2','p-3','p-4','p-6','p-8','p-10','p-12','px-2','px-4','px-6','px-8','py-2','py-4','py-6','py-8','py-10','py-12','py-16','py-20','py-24'],
    },
    'Layout': {
        'Display':       ['block','inline','inline-block','flex','inline-flex','grid','inline-grid','hidden'],
        'Flex':          ['flex-row','flex-col','flex-wrap','flex-nowrap','items-start','items-center','items-end','items-stretch','justify-start','justify-center','justify-end','justify-between','justify-around','gap-1','gap-2','gap-3','gap-4','gap-6','gap-8'],
        'Grid':          ['grid-cols-1','grid-cols-2','grid-cols-3','grid-cols-4','grid-cols-5','grid-cols-6','grid-cols-12'],
        'Width':         ['w-auto','w-full','w-1/2','w-1/3','w-2/3','w-1/4','w-3/4','w-screen','max-w-xs','max-w-sm','max-w-md','max-w-lg','max-w-xl','max-w-2xl','max-w-3xl','max-w-4xl','max-w-5xl','max-w-6xl','max-w-7xl','max-w-full'],
        'Position':      ['relative','absolute','fixed','sticky','static','top-0','bottom-0','left-0','right-0','z-0','z-10','z-20','z-50'],
    },
    'Borders & Effects': {
        'Border':        ['border','border-0','border-2','border-4','border-solid','border-dashed','border-dotted','border-gray-200','border-gray-300','border-slate-300','border-blue-500','border-indigo-500','border-purple-500'],
        'Rounded':       ['rounded-none','rounded-sm','rounded','rounded-md','rounded-lg','rounded-xl','rounded-2xl','rounded-3xl','rounded-full'],
        'Shadow':        ['shadow-none','shadow-sm','shadow','shadow-md','shadow-lg','shadow-xl','shadow-2xl','shadow-inner'],
        'Opacity':       ['opacity-0','opacity-25','opacity-50','opacity-75','opacity-100'],
    },
    'Responsive': {
        'Breakpoint prefixes (add to any class)': ['sm:','md:','lg:','xl:','2xl:'],
        'Mobile hide/show': ['hidden','sm:hidden','md:hidden','lg:hidden','sm:block','md:block','lg:block','sm:flex','md:flex','lg:flex'],
    },
    'Effects': {
        'Hover / transitions': ['hover:underline','hover:opacity-75','hover:shadow-lg','hover:scale-105','transition','transition-all','transition-colors','duration-150','duration-300','duration-500','ease-in','ease-out','ease-in-out'],
    },
};

window.openTailwindClassPicker = function(options) {
    options = options || {};
    const currentClasses = (options.current || '').trim().split(/\s+/).filter(Boolean);
    const onApply = typeof options.onApply === 'function' ? options.onApply : () => {};
    const title = options.title || 'Block classes';

    // Destroy any previous picker
    document.querySelectorAll('.tw-picker-overlay').forEach(el => el.remove());

    const selected = new Set(currentClasses);
    const categories = Object.keys(window.TAILWIND_CLASS_CATALOG);
    let activeCategory = categories[0];
    let searchQuery = '';

    const overlay = document.createElement('div');
    overlay.className = 'tw-picker-overlay';
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });

    const modal = document.createElement('div');
    modal.className = 'tw-picker-modal';
    modal.addEventListener('click', (e) => e.stopPropagation());
    overlay.appendChild(modal);

    // Header
    const header = document.createElement('div');
    header.className = 'tw-picker-header';
    header.innerHTML = `
        <div class="tw-picker-title">
            <span class="tw-picker-title-icon">.tw</span>
            <span>${title}</span>
        </div>
    `;
    const closeBtn = document.createElement('button');
    closeBtn.className = 'tw-picker-close';
    closeBtn.title = 'Close';
    closeBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
    closeBtn.addEventListener('click', close);
    header.appendChild(closeBtn);
    modal.appendChild(header);

    // Search
    const searchWrap = document.createElement('div');
    searchWrap.className = 'tw-picker-search-wrap';
    searchWrap.innerHTML = `
        <svg class="tw-picker-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
    `;
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search classes (e.g. text-, bg-, p-4, rounded...)';
    searchInput.className = 'tw-picker-search';
    searchInput.addEventListener('input', (e) => { searchQuery = e.target.value.toLowerCase(); renderBody(); });
    searchWrap.appendChild(searchInput);
    modal.appendChild(searchWrap);

    // Tabs
    const tabs = document.createElement('div');
    tabs.className = 'tw-picker-tabs';
    categories.forEach(cat => {
        const t = document.createElement('button');
        t.type = 'button';
        t.className = 'tw-picker-tab' + (cat === activeCategory ? ' active' : '');
        t.textContent = cat;
        t.addEventListener('click', () => { activeCategory = cat; searchQuery = ''; searchInput.value = ''; renderTabs(); renderBody(); });
        tabs.appendChild(t);
    });
    modal.appendChild(tabs);

    const renderTabs = () => {
        Array.from(tabs.children).forEach((t, i) => t.classList.toggle('active', categories[i] === activeCategory));
    };

    // Body
    const body = document.createElement('div');
    body.className = 'tw-picker-body';
    modal.appendChild(body);

    // Footer
    const footer = document.createElement('div');
    footer.className = 'tw-picker-footer';
    const preview = document.createElement('div');
    preview.className = 'tw-picker-preview';
    const actions = document.createElement('div');
    actions.className = 'tw-picker-actions';
    const clearBtn = document.createElement('button');
    clearBtn.className = 'tw-picker-btn tw-picker-btn-clear';
    clearBtn.textContent = 'Clear all';
    clearBtn.addEventListener('click', () => { selected.clear(); renderBody(); updatePreview(); });
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'tw-picker-btn tw-picker-btn-cancel';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', close);
    const applyBtn = document.createElement('button');
    applyBtn.className = 'tw-picker-btn tw-picker-btn-apply';
    applyBtn.textContent = 'Apply';
    applyBtn.addEventListener('click', () => {
        const result = Array.from(selected).join(' ').trim();
        onApply(result);
        close();
    });
    actions.appendChild(clearBtn);
    actions.appendChild(cancelBtn);
    actions.appendChild(applyBtn);
    footer.appendChild(preview);
    footer.appendChild(actions);
    modal.appendChild(footer);

    const updatePreview = () => {
        const str = Array.from(selected).join(' ');
        preview.textContent = str || '(no classes)';
        preview.title = str;
    };

    const toggleClass = (cls) => {
        if (selected.has(cls)) selected.delete(cls);
        else selected.add(cls);
        renderBody();
        updatePreview();
    };

    const renderSelectedRow = () => {
        const sel = document.createElement('div');
        sel.className = 'tw-picker-selected';
        if (selected.size === 0) {
            const e = document.createElement('div');
            e.className = 'tw-picker-selected-empty';
            e.textContent = 'No classes applied. Click below to add, or type a custom class.';
            sel.appendChild(e);
        } else {
            // Recognise known vs custom classes
            const known = new Set();
            Object.values(window.TAILWIND_CLASS_CATALOG).forEach(group =>
                Object.values(group).forEach(list => list.forEach(c => known.add(c))));
            Array.from(selected).forEach(cls => {
                const chip = document.createElement('span');
                chip.className = 'tw-picker-chip' + (known.has(cls) ? '' : ' tw-picker-chip-custom');
                chip.textContent = cls;
                const x = document.createElement('button');
                x.type = 'button';
                x.className = 'tw-picker-chip-x';
                x.innerHTML = '×';
                x.title = 'Remove';
                x.addEventListener('click', () => toggleClass(cls));
                chip.appendChild(x);
                sel.appendChild(chip);
            });
        }
        return sel;
    };

    const renderCustomInput = () => {
        const wrap = document.createElement('div');
        wrap.className = 'tw-picker-custom-wrap';
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.className = 'tw-picker-custom-input';
        inp.placeholder = 'Add custom class(es)… e.g. my-brand-heading bg-[#1563DF]';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tw-picker-custom-btn';
        btn.textContent = '+ Add';
        const addCustom = () => {
            const val = inp.value.trim();
            if (!val) return;
            val.split(/\s+/).forEach(c => { if (c) selected.add(c); });
            inp.value = '';
            renderBody();
            updatePreview();
        };
        btn.addEventListener('click', addCustom);
        inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addCustom(); } });
        wrap.appendChild(inp);
        wrap.appendChild(btn);
        return wrap;
    };

    const renderBody = () => {
        body.innerHTML = '';
        body.appendChild(renderSelectedRow());
        body.appendChild(renderCustomInput());

        // If searching → across ALL categories; else only active tab
        const catalog = window.TAILWIND_CLASS_CATALOG;
        let shown = 0;
        const renderGroup = (groupName, classes) => {
            const filtered = searchQuery ? classes.filter(c => c.toLowerCase().includes(searchQuery)) : classes;
            if (filtered.length === 0) return;
            shown += filtered.length;
            const g = document.createElement('div');
            g.className = 'tw-picker-group';
            const t = document.createElement('div');
            t.className = 'tw-picker-group-title';
            t.textContent = groupName;
            g.appendChild(t);
            const wrap = document.createElement('div');
            wrap.className = 'tw-picker-classes';
            filtered.forEach(cls => {
                const pill = document.createElement('button');
                pill.type = 'button';
                pill.className = 'tw-picker-pill' + (selected.has(cls) ? ' active' : '');
                pill.textContent = cls;
                pill.addEventListener('click', () => toggleClass(cls));
                wrap.appendChild(pill);
            });
            g.appendChild(wrap);
            body.appendChild(g);
        };

        if (searchQuery) {
            Object.entries(catalog).forEach(([catName, groups]) => {
                Object.entries(groups).forEach(([gName, list]) => renderGroup(`${catName} · ${gName}`, list));
            });
        } else {
            const active = catalog[activeCategory] || {};
            Object.entries(active).forEach(([gName, list]) => renderGroup(gName, list));
        }

        if (shown === 0) {
            const empty = document.createElement('div');
            empty.className = 'tw-picker-empty';
            empty.textContent = 'No classes match. Tip: use the custom input above to add any class.';
            body.appendChild(empty);
        }
    };

    function close() {
        overlay.remove();
        document.removeEventListener('keydown', onKey);
    }
    const onKey = (e) => { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', onKey);

    document.body.appendChild(overlay);
    renderBody();
    updatePreview();
    setTimeout(() => searchInput.focus(), 50);
};

/* ─── Space block: vertical spacer with configurable height (rem/px/custom) ─── */
window.SpaceTool = class SpaceTool {
    static get toolbox() {
        return { title: 'Space', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 7l7-4 7 4M5 17l7 4 7-4"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }
    static get PRESETS() {
        return [
            { key: 'xs', label: 'XS',  value: '0.5rem' },
            { key: 'sm', label: 'SM',  value: '1rem'   },
            { key: 'md', label: 'MD',  value: '2rem'   },
            { key: 'lg', label: 'LG',  value: '4rem'   },
            { key: 'xl', label: 'XL',  value: '6rem'   },
        ];
    }
    constructor({ data, api }) {
        this.api = api;
        const d = (data && typeof data === 'object') ? data : {};
        this.data = { height: d.height || '2rem' };
    }
    render() {
        this.wrap = document.createElement('div');
        this.wrap.style.cssText = 'position:relative;border:1px dashed #cbd5e1;border-radius:6px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;transition:height .15s ease';
        this.label = document.createElement('span');
        this.wrap.appendChild(this.label);
        this.applyHeight();
        return this.wrap;
    }
    applyHeight() {
        if (!this.wrap) return;
        this.wrap.style.height = this.data.height || '2rem';
        if (this.label) this.label.textContent = `↕ Space · ${this.data.height || '2rem'}`;
    }
    renderSettings() {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:6px;width:240px';
        const lbl = document.createElement('div');
        lbl.textContent = 'Quick presets';
        lbl.style.cssText = 'font-size:11px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:0.04em';
        wrap.appendChild(lbl);
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:2px';
        SpaceTool.PRESETS.forEach(p => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = p.label; b.title = p.value;
            b.style.cssText = `flex:1;padding:6px 4px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:${this.data.height === p.value ? 'linear-gradient(135deg,#10b981,#059669)' : '#fff'};color:${this.data.height === p.value ? '#fff' : '#374151'};font-size:11px;font-weight:600`;
            b.addEventListener('click', () => {
                this.data.height = p.value;
                this.applyHeight();
                Array.from(row.children).forEach(c => {
                    const isActive = c.title === this.data.height;
                    c.style.background = isActive ? 'linear-gradient(135deg,#10b981,#059669)' : '#fff';
                    c.style.color = isActive ? '#fff' : '#374151';
                });
                if (custom) custom.value = this.data.height;
            });
            row.appendChild(b);
        });
        wrap.appendChild(row);

        const cl = document.createElement('div');
        cl.textContent = 'Custom (rem / px / vh / %)';
        cl.style.cssText = 'font-size:11px;font-weight:600;color:#374151;margin-top:4px;text-transform:uppercase;letter-spacing:0.04em';
        wrap.appendChild(cl);
        const custom = document.createElement('input');
        custom.type = 'text'; custom.placeholder = '3rem'; custom.value = this.data.height || '';
        custom.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:ui-monospace,monospace';
        custom.addEventListener('input', (e) => {
            const v = e.target.value.trim();
            if (v) {
                this.data.height = v;
                this.applyHeight();
            }
        });
        wrap.appendChild(custom);
        return wrap;
    }
    save() { return { height: this.data.height || '2rem' }; }
    static get sanitize() { return { height: false }; }
};

/* ─── Container block: wraps content with responsive max-width + custom classes ─── */
window.ContainerTool = class ContainerTool {
    static get toolbox() {
        return { title: 'Container', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h12M6 14h8"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }
    /**
     * Tell EditorJS this tool handles its own line breaks. Without this, pressing
     * Enter inside the nested sub-editor bubbles up and the outer editor creates a
     * new paragraph block AFTER the container — kicking the user out of typing.
     */
    static get enableLineBreaks() { return true; }

    // Preset max-widths (map to Tailwind max-w-* + CSS values for live preview)
    static get WIDTHS() {
        return {
            'full':   { label: 'Full width',    class: 'max-w-full',  css: '100%' },
            '8xl':    { label: '8xl (88rem)',   class: 'max-w-8xl',   css: '88rem' },
            '7xl':    { label: '7xl (80rem)',   class: 'max-w-7xl',   css: '80rem' },
            '6xl':    { label: '6xl (72rem)',   class: 'max-w-6xl',   css: '72rem' },
            '5xl':    { label: '5xl (64rem)',   class: 'max-w-5xl',   css: '64rem' },
            '4xl':    { label: '4xl (56rem)',   class: 'max-w-4xl',   css: '56rem' },
            '3xl':    { label: '3xl (48rem)',   class: 'max-w-3xl',   css: '48rem' },
            '2xl':    { label: '2xl (42rem)',   class: 'max-w-2xl',   css: '42rem' },
            'xl':     { label: 'xl (36rem)',    class: 'max-w-xl',    css: '36rem' },
            'prose':  { label: 'Prose (65ch)',  class: 'max-w-prose', css: '65ch' },
        };
    }

    constructor({ data, api, config }) {
        this.api = api;
        const d = data && typeof data === 'object' ? data : {};
        this.data = {
            desktop: d.desktop || '7xl',
            tablet: d.tablet || 'full',
            mobile: d.mobile || 'full',
            wrapperClass: d.wrapperClass || '',
            innerClass: d.innerClass || '',
            content: d.content || { blocks: [] },
        };
        this.subEditor = null;
    }

    renderSettings() {
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:280px';

        const makeSelect = (label, key) => {
            const lab = document.createElement('label');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            lab.textContent = label;
            const sel = document.createElement('select');
            sel.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px';
            Object.entries(ContainerTool.WIDTHS).forEach(([k, v]) => {
                const opt = document.createElement('option');
                opt.value = k; opt.textContent = v.label;
                if (this.data[key] === k) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.addEventListener('change', (e) => { this.data[key] = e.target.value; this.updateLabel(); this.applyVisualWidth(); });
            const wrap = document.createElement('div');
            wrap.appendChild(lab); wrap.appendChild(sel);
            return wrap;
        };

        wrapper.appendChild(makeSelect('📱 Mobile', 'mobile'));
        wrapper.appendChild(makeSelect('📱 Tablet', 'tablet'));
        wrapper.appendChild(makeSelect('🖥️ Desktop', 'desktop'));

        const makeInput = (label, key, placeholder) => {
            const lab = document.createElement('label');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            lab.textContent = label;
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;gap:4px';
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.placeholder = placeholder;
            inp.value = this.data[key] || '';
            inp.style.cssText = 'flex:1;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:monospace';
            inp.addEventListener('input', (e) => { this.data[key] = e.target.value; this.applyLiveClasses(); });
            const pick = document.createElement('button');
            pick.type = 'button';
            pick.textContent = '.tw';
            pick.title = 'Open Tailwind class picker';
            pick.style.cssText = 'padding:6px 10px;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;font-size:11px;font-family:monospace;font-weight:700;cursor:pointer;color:#4f46e5';
            pick.addEventListener('click', () => {
                if (typeof window.openTailwindClassPicker === 'function') {
                    window.openTailwindClassPicker({
                        current: this.data[key] || '',
                        title: label,
                        onApply: (classes) => { this.data[key] = (classes || '').trim(); inp.value = this.data[key]; this.applyLiveClasses(); },
                    });
                }
            });
            row.appendChild(inp);
            row.appendChild(pick);
            const wrap = document.createElement('div');
            wrap.appendChild(lab); wrap.appendChild(row);
            return wrap;
        };

        wrapper.appendChild(makeInput('Wrapper classes (outer)', 'wrapperClass', 'py-12 bg-slate-50'));
        wrapper.appendChild(makeInput('Inner classes (content)', 'innerClass', 'mx-auto px-4 sm:px-6 lg:px-8'));

        return wrapper;
    }

    applyLiveClasses() {
        if (this.wrap) {
            this.wrap.className = ('ctr-tool-wrap ' + (this.data.wrapperClass || '')).trim();
        }
        if (this.innerEl) {
            this.innerEl.className = (this.data.innerClass || '').trim();
        }
    }

    updateLabel() {
        if (this.labelEl) {
            this.labelEl.textContent = `Container · M:${this.data.mobile} T:${this.data.tablet} D:${this.data.desktop}`;
        }
    }

    /**
     * Unlocks the parent `.ce-block__content` (which has EditorJS's 650px cap)
     * and applies the selected desktop width to our own wrap with !important
     * so no other CSS can override it.
     */
    applyVisualWidth() {
        if (!this.wrap) return;
        const w = ContainerTool.WIDTHS[this.data.desktop] || ContainerTool.WIDTHS['full'];

        // Free the parent block-content from the 650px cap
        const parent = this.wrap.closest('.ce-block__content');
        if (parent) {
            parent.style.setProperty('max-width', 'none', 'important');
            parent.style.setProperty('width', '100%', 'important');
            parent.style.setProperty('margin', '0', 'important');
        }

        // Apply selected width to the wrap itself
        this.wrap.style.setProperty('max-width', w.css, 'important');
        this.wrap.style.setProperty('width', '100%', 'important');
        this.wrap.style.setProperty('margin-left', 'auto', 'important');
        this.wrap.style.setProperty('margin-right', 'auto', 'important');

        // Show the resolved pixel width in the label for clarity
        if (this.labelEl) {
            const rect = this.wrap.getBoundingClientRect();
            const px = Math.round(rect.width);
            this.labelEl.textContent = `Container · M:${this.data.mobile} T:${this.data.tablet} D:${this.data.desktop} · ${px}px`;
        }
    }

    render() {
        this.wrap = document.createElement('div');
        this.wrap.className = ('ctr-tool-wrap ' + (this.data.wrapperClass || '')).trim();
        this.wrap.style.cssText = 'position:relative;padding:18px 10px 10px;border:1px dashed #d1d5db;border-radius:8px;background:transparent;box-sizing:border-box;transition:max-width .18s ease';

        this.labelEl = document.createElement('div');
        this.labelEl.style.cssText = 'position:absolute;top:-9px;left:10px;background:#fff;padding:0 6px;font-size:10px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;border-radius:2px';
        this.updateLabel();
        this.wrap.appendChild(this.labelEl);

        // Inner element — receives the user's innerClass live
        this.innerEl = document.createElement('div');
        this.innerEl.className = (this.data.innerClass || '').trim();
        this.wrap.appendChild(this.innerEl);

        const holder = document.createElement('div');
        holder.id = `ej-container-${Math.random().toString(36).slice(2, 9)}`;
        this.innerEl.appendChild(holder);

        // Apply the stored width once mounted + re-apply on window resize
        setTimeout(() => this.applyVisualWidth(), 30);
        setTimeout(() => this.applyVisualWidth(), 200);
        this._onResize = () => this.applyVisualWidth();
        window.addEventListener('resize', this._onResize);

        try {
            const subTools = {
                header: { class: Header, inlineToolbar: true, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                list: { class: NestedList, inlineToolbar: true },
                quote: { class: Quote, inlineToolbar: true },
                marker: Marker,
                inlineCode: InlineCode,
                underline: Underline,
                ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
                ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),
                ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
                ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),
                ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),
                ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),
                ...(window.SpaceTool   ? { space:   { class: window.SpaceTool   } } : {}),
            };
            if (window.__editorImageTool) {
                subTools.image = {
                    ...window.__editorImageTool,
                    tunes: window.ImageSizeTune ? ['imageSize'] : [],
                };
            }

            this.subEditor = new EditorJS({
                holder: holder,
                placeholder: 'Container content...',
                data: this.data.content || { blocks: [] },
                minHeight: 80,
                tools: subTools,
                tunes: [
                    ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                    ...(window.BlockClassesTune ? ['blockClasses'] : []),
                ],
                onChange: async () => {
                    try { this.data.content = await this.subEditor.save(); } catch (e) {}
                },
                onReady: () => {
                    // Hook the floating multi-block alignment toolbar onto this nested editor
                    if (typeof window.initMultiBlockAlignmentBar === 'function') {
                        window.initMultiBlockAlignmentBar(holder);
                    }
                },
            });
        } catch (e) {
            console.warn('Container sub-editor init failed:', e);
        }

        return this.wrap;
    }

    async save() {
        if (this.subEditor && typeof this.subEditor.save === 'function') {
            try { this.data.content = await this.subEditor.save(); } catch (e) {}
        }
        return { ...this.data };
    }

    destroy() {
        try { this.subEditor?.destroy?.(); } catch (e) {}
        this.subEditor = null;
        if (this._onResize) { window.removeEventListener('resize', this._onResize); this._onResize = null; }
    }

    static get sanitize() {
        return { desktop: false, tablet: false, mobile: false, wrapperClass: false, innerClass: false, content: false };
    }
};

/* ─── Block Tune: Custom CSS Classes (Tailwind) on the block element itself ─── */
window.BlockClassesTune = class BlockClassesTune {
    static get isTune() { return true; }

    constructor({ api, data, block }) {
        this.api = api;
        this.block = block;
        this.data = data && typeof data === 'object' ? data : {};
    }

    render() {
        const el = document.createElement('div');
        el.classList.add('ce-settings__button');
        el.title = 'Add Tailwind / CSS classes to this block';
        el.style.cssText = 'display:flex;align-items:center;gap:6px;padding:4px 8px;border-radius:4px;cursor:pointer;font-size:12px';
        el.innerHTML = '<span style="font-family:monospace;font-weight:700">.tw</span><span style="font-size:11px">Classes</span>';
        el.addEventListener('click', () => this.openEditor());
        return el;
    }

    openEditor() {
        const current = this.data.classes || '';
        if (typeof window.openTailwindClassPicker === 'function') {
            window.openTailwindClassPicker({
                current,
                title: 'Tailwind / CSS classes for this block',
                onApply: (classes) => {
                    this.data.classes = (classes || '').trim();
                    this.applyToBlock();
                },
            });
        } else {
            const input = prompt('Tailwind / CSS classes for this block:\n(applied to the block element itself — h1, p, img, etc.)', current);
            if (input === null) return;
            this.data.classes = input.trim();
            this.applyToBlock();
        }
    }

    applyToBlock() {
        try {
            const blockIndex = this.api.blocks.getCurrentBlockIndex?.() ?? -1;
            let blockEl = null;
            if (this.block && this.block.holder) {
                blockEl = this.block.holder;
            } else if (blockIndex >= 0) {
                const nodes = document.querySelectorAll('.ce-block');
                blockEl = nodes[blockIndex];
            }
            if (!blockEl) return;
            // Find the primary content element (cover EditorJS's ce-paragraph div + headings + lists/etc.)
            const primary = blockEl.querySelector(
                '.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, img, pre, figure'
            );
            if (!primary) return;

            // ADDITIVE: remove previously-applied user classes, then add the new set.
            // Never wipe className — that would kill EditorJS internals like .ce-header.
            const prev = (primary.dataset.btcClasses || '').split(/\s+/).filter(Boolean);
            prev.forEach(c => primary.classList.remove(c));

            const next = (this.data.classes || '').trim().split(/\s+/).filter(Boolean);
            next.forEach(c => primary.classList.add(c));
            primary.dataset.btcClasses = next.join(' ');
        } catch (e) {}
    }

    save() {
        return { classes: this.data.classes || '' };
    }

    wrap(blockContent) {
        // Called by EditorJS when rendering; we use it to also reflect styles live
        setTimeout(() => this.applyToBlock(), 50);
        return blockContent;
    }
};

/* ─── Helpers used by TextAlignmentTune (selector + bulk save patch) ─── */
window.findBlockPrimary = window.findBlockPrimary || function(blockEl) {
    if (!blockEl) return null;
    // EditorJS renders paragraphs as <div class="ce-paragraph">, headers as <h2 class="ce-header">,
    // lists as <ul>/<ol>, quotes as <blockquote>, etc. Cover them all.
    return blockEl.querySelector(
        '.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, figure, pre, [contenteditable="true"]'
    );
};
window.applyAlignmentToBlockElement = window.applyAlignmentToBlockElement || function(blockEl, alignment) {
    if (!blockEl) return;
    const primary = window.findBlockPrimary(blockEl);
    if (primary) primary.style.textAlign = alignment || '';
    if (alignment) {
        blockEl.dataset.textAlignment = alignment;
    } else {
        delete blockEl.dataset.textAlignment;
    }
};
/**
 * Walks an EditorJS save() output and injects tunes.textAlignment based on
 * dataset.textAlignment that the tune wrote on each .ce-block (used for bulk
 * multi-block alignment where only ONE tune instance is interacted with).
 */
window.patchAlignmentTunes = window.patchAlignmentTunes || function(outputData, containerEl) {
    if (!outputData || !Array.isArray(outputData.blocks) || !containerEl) return;
    outputData.blocks.forEach((block) => {
        if (!block.id) return;
        const el = containerEl.querySelector(`.ce-block[data-id="${block.id}"]`);
        if (!el) return;
        const al = el.dataset.textAlignment;
        if (al && al !== '') {
            block.tunes = block.tunes || {};
            block.tunes.textAlignment = { alignment: al };
        }
    });
};

/* ─── Block Tune: Text Alignment (Left / Center / Right / Justify) ─── */
window.TextAlignmentTune = class TextAlignmentTune {
    static get isTune() { return true; }

    static get OPTIONS() {
        return [
            { key: 'left',    label: 'Left',    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h15"/></svg>' },
            { key: 'center',  label: 'Center',  icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M6 12h12M4 18h16"/></svg>' },
            { key: 'right',   label: 'Right',   icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M9 12h12M6 18h15"/></svg>' },
            { key: 'justify', label: 'Justify', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>' },
        ];
    }

    constructor({ api, data, block }) {
        this.api = api;
        this.block = block;
        this.data = (data && typeof data === 'object') ? data : {};
        this.buttons = [];
        this.countLabel = null;
    }

    /** Find currently-selected blocks (multi-select). Always includes current block. */
    getTargetBlocks() {
        const selected = Array.from(document.querySelectorAll('.ce-block--selected'));
        const own = (this.block && this.block.holder) ? this.block.holder : null;
        if (selected.length > 0) {
            // ensure own block is in the list (sometimes it's not marked selected)
            if (own && !selected.includes(own)) selected.push(own);
            return selected;
        }
        return own ? [own] : [];
    }

    render() {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;gap:2px;padding:4px 6px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;align-items:center';

        const lbl = document.createElement('span');
        lbl.textContent = 'Align';
        lbl.style.cssText = 'font-size:11px;color:#6b7280;align-self:center;margin-right:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em';
        wrap.appendChild(lbl);

        TextAlignmentTune.OPTIONS.forEach(opt => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = opt.label + ' align';
            btn.dataset.align = opt.key;
            btn.style.cssText = 'flex:1;min-width:32px;display:inline-flex;align-items:center;justify-content:center;padding:5px 6px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:#fff;color:#374151;transition:all .12s';
            btn.innerHTML = opt.icon;
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const next = (this.data.alignment === opt.key) ? null : opt.key;
                const targets = this.getTargetBlocks();
                targets.forEach(blockEl => window.applyAlignmentToBlockElement(blockEl, next));
                // Update our own data when our block is in the targets
                const own = (this.block && this.block.holder) ? this.block.holder : null;
                if (own && targets.includes(own)) {
                    this.data.alignment = next;
                    this.refreshActive();
                }
                this.dispatchChange();
                if (targets.length > 1) {
                    this.flashCount(`Applied to ${targets.length} blocks`);
                }
            });
            this.buttons.push(btn);
            wrap.appendChild(btn);
        });

        // Tiny status line that briefly shows how many blocks were affected
        this.countLabel = document.createElement('div');
        this.countLabel.style.cssText = 'font-size:10px;color:#10b981;width:100%;padding:2px 0 0;text-align:right;opacity:0;transition:opacity .25s ease';
        wrap.appendChild(this.countLabel);

        setTimeout(() => { this.refreshActive(); this.applyToBlock(); }, 30);
        return wrap;
    }

    flashCount(msg) {
        if (!this.countLabel) return;
        this.countLabel.textContent = '✓ ' + msg;
        this.countLabel.style.opacity = '1';
        clearTimeout(this._flashTimer);
        this._flashTimer = setTimeout(() => { this.countLabel.style.opacity = '0'; }, 1800);
    }

    /** Triggers EditorJS to re-save (so onChange fires with patched tune data). */
    dispatchChange() {
        try { this.block?.dispatchChange?.(); } catch (e) {}
    }

    refreshActive() {
        this.buttons.forEach(b => {
            const isActive = b.dataset.align === this.data.alignment;
            b.style.background = isActive ? 'linear-gradient(135deg,#6366f1,#8b5cf6)' : '#fff';
            b.style.color = isActive ? '#fff' : '#374151';
            b.style.borderColor = isActive ? 'transparent' : '#e5e7eb';
        });
    }

    applyToBlock() {
        try {
            let blockEl = (this.block && this.block.holder) ? this.block.holder : null;
            if (!blockEl) {
                const idx = this.api.blocks.getCurrentBlockIndex?.() ?? -1;
                if (idx >= 0) blockEl = document.querySelectorAll('.ce-block')[idx];
            }
            window.applyAlignmentToBlockElement(blockEl, this.data.alignment);
        } catch (e) {}
    }

    save() {
        return { alignment: this.data.alignment || null };
    }

    wrap(blockContent) {
        setTimeout(() => this.applyToBlock(), 50);
        return blockContent;
    }
};

/* ─── Block Tune: Image Size (resize images: 25 / 50 / 75 / 100% + custom) ─── */
window.ImageSizeTune = class ImageSizeTune {
    static get isTune() { return true; }

    static get OPTIONS() {
        return [
            { key: '25',  label: '25%',  width: '25%' },
            { key: '50',  label: '50%',  width: '50%' },
            { key: '75',  label: '75%',  width: '75%' },
            { key: '100', label: '100%', width: '100%' },
        ];
    }

    constructor({ api, data, block }) {
        this.api = api;
        this.block = block;
        this.data = (data && typeof data === 'object') ? data : {};
        this.buttons = [];
    }

    render() {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;gap:2px;padding:4px 6px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;align-items:center';

        const lbl = document.createElement('span');
        lbl.textContent = 'Size';
        lbl.style.cssText = 'font-size:11px;color:#6b7280;align-self:center;margin-right:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em';
        wrap.appendChild(lbl);

        ImageSizeTune.OPTIONS.forEach(opt => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = `Resize image to ${opt.label}`;
            btn.dataset.size = opt.key;
            btn.style.cssText = 'flex:1;min-width:42px;padding:5px 8px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:#fff;color:#374151;font-size:11px;font-weight:600;transition:all .12s';
            btn.textContent = opt.label;
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const next = (this.data.size === opt.key) ? null : opt.key;
                this.data.size = next;
                this.applyToBlock();
                this.refreshActive();
            });
            this.buttons.push(btn);
            wrap.appendChild(btn);
        });

        // Custom size input
        const customWrap = document.createElement('div');
        customWrap.style.cssText = 'display:flex;align-items:center;gap:4px;width:100%;margin-top:4px';
        const customLbl = document.createElement('span');
        customLbl.textContent = 'Custom:';
        customLbl.style.cssText = 'font-size:11px;color:#6b7280';
        const customInput = document.createElement('input');
        customInput.type = 'text';
        customInput.placeholder = '420px or 60%';
        customInput.value = (this.data.custom || '');
        customInput.style.cssText = 'flex:1;padding:4px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:11px;font-family:ui-monospace,monospace';
        customInput.addEventListener('input', (e) => {
            this.data.custom = e.target.value.trim();
            this.data.size = this.data.custom ? 'custom' : null;
            this.applyToBlock();
            this.refreshActive();
        });
        customWrap.appendChild(customLbl);
        customWrap.appendChild(customInput);
        wrap.appendChild(customWrap);

        setTimeout(() => { this.refreshActive(); this.applyToBlock(); }, 30);
        return wrap;
    }

    refreshActive() {
        this.buttons.forEach(b => {
            const isActive = b.dataset.size === this.data.size;
            b.style.background = isActive ? 'linear-gradient(135deg,#10b981,#059669)' : '#fff';
            b.style.color = isActive ? '#fff' : '#374151';
            b.style.borderColor = isActive ? 'transparent' : '#e5e7eb';
        });
    }

    applyToBlock() {
        try {
            let blockEl = (this.block && this.block.holder) ? this.block.holder : null;
            if (!blockEl) {
                const idx = this.api.blocks.getCurrentBlockIndex?.() ?? -1;
                if (idx >= 0) blockEl = document.querySelectorAll('.ce-block')[idx];
            }
            if (!blockEl) return;
            const img = blockEl.querySelector('img');
            if (!img) return;

            let widthValue = '';
            if (this.data.size === 'custom' && this.data.custom) {
                widthValue = this.data.custom;
            } else if (this.data.size) {
                const opt = ImageSizeTune.OPTIONS.find(o => o.key === this.data.size);
                if (opt) widthValue = opt.width;
            }

            if (widthValue) {
                img.style.setProperty('width', widthValue, 'important');
                img.style.setProperty('max-width', widthValue, 'important');
                img.style.setProperty('height', 'auto', 'important');
                // Preserve any existing alignment by NOT touching margin here
                img.dataset.imgSize = this.data.size + (this.data.size === 'custom' ? ':' + this.data.custom : '');
            } else {
                img.style.removeProperty('width');
                img.style.removeProperty('max-width');
                img.style.removeProperty('height');
                delete img.dataset.imgSize;
            }
        } catch (e) {}
    }

    save() {
        return {
            size: this.data.size || null,
            custom: this.data.custom || null,
        };
    }

    wrap(blockContent) {
        setTimeout(() => this.applyToBlock(), 50);
        return blockContent;
    }
};

console.log('[mb-align] script LOADED — version 2026-05-06 with keyboard shortcuts');

/* ─── Keyboard shortcuts: Ctrl/Cmd + Shift + L/E/R/J for align L/C/R/J ──────
   Works in any EditorJS instance (outer + nested). Targets all blocks the
   selection covers — both .ce-block--selected (EditorJS native multi-select)
   AND the text-range fallback. Persists via dataset.textAlignment + the
   patchAlignmentTunes save hook. */
if (!window._mbAlignKeyboardInited) {
    window._mbAlignKeyboardInited = true;
    document.addEventListener('keydown', function(e) {
        if (!(e.ctrlKey || e.metaKey) || !e.shiftKey) return;
        const k = e.key.toLowerCase();
        const map = { l: 'left', e: 'center', r: 'right', j: 'justify' };
        const alignment = map[k];
        if (!alignment) return;

        // Only act if focus is inside an EditorJS instance
        const active = document.activeElement;
        const inEditor = (active && active.closest && active.closest('.codex-editor')) ||
                         (window.getSelection()?.anchorNode?.parentElement?.closest?.('.codex-editor'));
        if (!inEditor) return;

        e.preventDefault();
        e.stopPropagation();

        // Resolve target blocks: try block-level selection → text range → current block
        let blocks = [];
        const root = inEditor.parentElement || inEditor;
        const flagged = Array.from(root.querySelectorAll('.ce-block.ce-block--selected'));
        if (flagged.length >= 1) {
            blocks = flagged;
        } else {
            const sel = window.getSelection();
            if (sel && sel.rangeCount && !sel.isCollapsed) {
                const range = sel.getRangeAt(0);
                const sEl = (range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement);
                const eEl = (range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement);
                const sBlock = sEl?.closest('.ce-block');
                const eBlock = eEl?.closest('.ce-block');
                if (sBlock) blocks.push(sBlock);
                if (eBlock && eBlock !== sBlock) {
                    let cur = sBlock?.nextElementSibling;
                    while (cur && cur !== eBlock) {
                        if (cur.classList?.contains('ce-block')) blocks.push(cur);
                        cur = cur.nextElementSibling;
                    }
                    blocks.push(eBlock);
                }
            }
            // Last resort: just the current block (the one with focus)
            if (blocks.length === 0) {
                const cur = active?.closest?.('.ce-block') || window.getSelection()?.anchorNode?.parentElement?.closest?.('.ce-block');
                if (cur) blocks.push(cur);
            }
        }

        if (blocks.length === 0) {
            console.warn('[mb-align] keyboard: no blocks resolved');
            return;
        }

        blocks.forEach(b => {
            if (typeof window.applyAlignmentToBlockElement === 'function') {
                window.applyAlignmentToBlockElement(b, alignment);
            }
        });
        // Try to dispatch change so EditorJS re-saves with the alignment tune.
        try {
            const editor = root._editorjsInstance || (inEditor.closest('[id]'))?._editorjsInstance;
            if (editor && editor.blocks) {
                blocks.forEach(b => {
                    const idx = Array.from(root.querySelectorAll('.ce-block')).indexOf(b);
                    if (idx >= 0) editor.blocks.getBlockByIndex(idx)?.dispatchChange?.();
                });
            }
        } catch (_) {}

        console.log('[mb-align] keyboard:', alignment, '→', blocks.length, 'block(s)');
    }, true); // capture phase so we beat browser defaults
}

/* ─── Floating alignment toolbar (GLOBAL, document-level) ────────────────────
   Bulletproof variant: bar element is built lazily on first need + re-attached
   if it's ever removed from the DOM (Livewire morphdom can wipe body children).
   In fullscreen mode the bar is appended to the fullscreen wrapper so it sits
   on top of the position:fixed editor; otherwise to <body>. */
(function setupGlobalAlignmentBar() {
    let bar = null;
    let lastBlocks = [];
    let lastEditor = null;

    function buildBar() {
        const el = document.createElement('div');
        el.id = 'mb-align-bar';
        el.style.cssText = 'position:absolute;display:none;z-index:2147483647;background:#111827;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.35);padding:4px;gap:2px;align-items:center;user-select:none';
        el.setAttribute('role', 'toolbar');
        el.addEventListener('mousedown', (e) => e.preventDefault());

        const opts = [
            { key: 'left',    title: 'Align left',    icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h15"/></svg>' },
            { key: 'center',  title: 'Center',        icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M6 12h12M4 18h16"/></svg>' },
            { key: 'right',   title: 'Align right',   icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M9 12h12M6 18h15"/></svg>' },
            { key: 'justify', title: 'Justify',       icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>' },
        ];
        opts.forEach(o => {
            const b = document.createElement('button');
            b.type = 'button'; b.title = o.title; b.dataset.align = o.key;
            b.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:34px;height:30px;border:none;border-radius:5px;cursor:pointer;background:transparent;color:#fff;transition:background .12s';
            b.innerHTML = o.icon;
            b.addEventListener('mouseenter', () => b.style.background = 'rgba(99,102,241,0.5)');
            b.addEventListener('mouseleave', () => b.style.background = 'transparent');
            b.addEventListener('click', (ev) => {
                ev.preventDefault(); ev.stopPropagation();
                applyToCurrentSelection(o.key);
            });
            el.appendChild(b);
        });
        const clear = document.createElement('button');
        clear.type = 'button'; clear.title = 'Clear alignment';
        clear.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:34px;height:30px;border:none;border-radius:5px;cursor:pointer;background:transparent;color:#fca5a5;font-size:18px;font-weight:700;margin-left:2px';
        clear.innerHTML = '×';
        clear.addEventListener('mouseenter', () => clear.style.background = 'rgba(239,68,68,0.4)');
        clear.addEventListener('mouseleave', () => clear.style.background = 'transparent');
        clear.addEventListener('click', (ev) => { ev.preventDefault(); ev.stopPropagation(); applyToCurrentSelection(null); });
        el.appendChild(clear);
        return el;
    }

    function ensureBar() {
        // Decide where to append: fullscreen wrapper if active, else <body>
        const fullscreenHost = document.querySelector('.editorjs-fullscreen-mode') ||
                               document.querySelector('[class*="editorjs-fullscreen"]');
        const targetParent = fullscreenHost || document.body;
        if (!targetParent) return null;

        // If bar exists but is detached or in wrong parent, re-create
        if (bar && (!document.contains(bar) || bar.parentElement !== targetParent)) {
            try { bar.remove(); } catch (e) {}
            bar = null;
        }
        if (!bar) {
            bar = buildBar();
            targetParent.appendChild(bar);
            console.log('[mb-align] bar attached to', targetParent === document.body ? '<body>' : '.editorjs-fullscreen-mode');
        }
        return bar;
    }

    function findBlocksForSelection() {
        const sel = window.getSelection();
        // Try DOM Range first
        if (sel && sel.rangeCount && !sel.isCollapsed) {
            const range = sel.getRangeAt(0);
            const sEl = (range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement);
            const eEl = (range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement);
            const sBlock = sEl?.closest?.('.ce-block');
            const eBlock = eEl?.closest?.('.ce-block');
            if (sBlock) {
                // Find the OWNING editor root for this block
                const editorRoot = sBlock.closest('.codex-editor');
                if (!editorRoot) return { blocks: [], root: null };
                const blocks = [sBlock];
                if (eBlock && eBlock !== sBlock) {
                    let cur = sBlock.nextElementSibling;
                    while (cur && cur !== eBlock) {
                        if (cur.classList?.contains('ce-block')) blocks.push(cur);
                        cur = cur.nextElementSibling;
                    }
                    blocks.push(eBlock);
                }
                return { blocks, root: editorRoot };
            }
        }
        // Try EditorJS native block selection (.ce-block--selected)
        const allEditors = document.querySelectorAll('.codex-editor');
        for (const ed of allEditors) {
            const flagged = Array.from(ed.querySelectorAll('.ce-block.ce-block--selected'));
            if (flagged.length >= 1) {
                return { blocks: flagged, root: ed };
            }
        }
        return { blocks: [], root: null };
    }

    function showBarForBlocks(blocks) {
        const el = ensureBar();
        if (!el || !blocks.length) { hideBar(); return; }
        let minTop = Infinity, minLeft = Infinity, maxRight = -Infinity;
        blocks.forEach(b => {
            const r = b.getBoundingClientRect();
            if (r.top < minTop) minTop = r.top;
            if (r.left < minLeft) minLeft = r.left;
            if (r.right > maxRight) maxRight = r.right;
        });
        if (!isFinite(minTop)) { hideBar(); return; }
        // In fullscreen, the bar's offsetParent is the fullscreen wrapper; use clientRect-relative coords
        const inFullscreen = el.parentElement && el.parentElement.classList?.contains('editorjs-fullscreen-mode');
        el.style.visibility = 'hidden';
        el.style.display = 'flex';
        const barWidth = el.offsetWidth || 180;
        let top, left;
        if (inFullscreen) {
            const parentRect = el.parentElement.getBoundingClientRect();
            top  = (minTop  - parentRect.top) - 44;
            left = ((minLeft + maxRight) / 2 - parentRect.left) - (barWidth / 2);
        } else {
            top  = window.scrollY + minTop - 44;
            left = window.scrollX + (minLeft + maxRight) / 2 - (barWidth / 2);
        }
        el.style.top  = Math.max(8, top) + 'px';
        el.style.left = Math.max(8, left) + 'px';
        el.style.visibility = 'visible';
    }
    function hideBar() { if (bar) bar.style.display = 'none'; }

    function applyToCurrentSelection(alignment) {
        // Use the LAST captured selection state (clicking the bar may have collapsed the range)
        const blocks = lastBlocks.length ? lastBlocks : findBlocksForSelection().blocks;
        const root = lastEditor || findBlocksForSelection().root;
        if (!blocks.length) return;
        blocks.forEach(b => {
            if (typeof window.applyAlignmentToBlockElement === 'function') {
                window.applyAlignmentToBlockElement(b, alignment);
            }
        });
        // Trigger save patch via Block.dispatchChange()
        try {
            const editorRootEl = root?.parentElement || root;
            const editor = editorRootEl?._editorjsInstance;
            if (editor && editor.blocks && root) {
                const allBlocks = Array.from(root.querySelectorAll('.ce-block'));
                blocks.forEach(b => {
                    const idx = allBlocks.indexOf(b);
                    if (idx >= 0) editor.blocks.getBlockByIndex(idx)?.dispatchChange?.();
                });
            }
        } catch (e) {}
        hideBar();
    }

    // ─ React to selection changes ─
    let checkTimer = null;
    function check(reason) {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(() => {
            const { blocks, root } = findBlocksForSelection();
            console.log('[mb-align] check from', reason, '→', blocks.length, 'block(s)');
            if (blocks.length >= 1) {
                lastBlocks = blocks;
                lastEditor = root;
                showBarForBlocks(blocks);
            } else {
                hideBar();
                lastBlocks = [];
                lastEditor = null;
            }
        }, 120);
    }

    document.addEventListener('selectionchange', () => check('selectionchange'));
    document.addEventListener('mouseup', () => check('mouseup'));
    document.addEventListener('keyup', () => check('keyup'));

    // Watch the DOM for .ce-block--selected class additions — EditorJS often
    // adds the class slightly AFTER mouseup, missing the initial check.
    try {
        const mo = new MutationObserver((muts) => {
            for (const m of muts) {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    const t = m.target;
                    if (t.classList?.contains('ce-block')) {
                        check('mutation');
                        return;
                    }
                }
            }
        });
        const startObserver = () => mo.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'] });
        if (document.body) startObserver();
        else document.addEventListener('DOMContentLoaded', startObserver);
    } catch (e) {}

    window.addEventListener('scroll', () => {
        if (lastBlocks.length) showBarForBlocks(lastBlocks);
    }, true);
    document.addEventListener('mousedown', (e) => {
        if (bar && !bar.contains(e.target)) {
            // Don't hide immediately — selection might happen on this very click
            setTimeout(() => {
                const { blocks } = findBlocksForSelection();
                if (!blocks.length) hideBar();
            }, 100);
        }
    });
})();

// Backward compat — old initMultiBlockAlignmentBar(rootContainer) calls become no-ops
// (the global setup above handles all editor instances automatically).
window.initMultiBlockAlignmentBar = function() { /* now global, no-op */ };

/* ─── Inline Alignment tool — multi-block text-align via the inline toolbar ───
   Lets the user select text across one or many blocks (click-drag) and apply
   left/center/right/justify alignment from the inline toolbar (alongside
   bold/italic/marker/etc.). Reuses the same dataset.textAlignment + save-patch
   approach as TextAlignmentTune so the alignment persists in saved JSON. */
window.InlineAlignmentTool = class InlineAlignmentTool {
    static get isInline() { return true; }
    static get title() { return 'Alignment'; }
    static get sanitize() { return {}; }

    constructor({ api }) {
        this.api = api;
        this.OPTIONS = [
            { key: 'left',    label: 'Left',    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h15"/></svg>' },
            { key: 'center',  label: 'Center',  icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M6 12h12M4 18h16"/></svg>' },
            { key: 'right',   label: 'Right',   icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M9 12h12M6 18h15"/></svg>' },
            { key: 'justify', label: 'Justify', icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>' },
        ];
    }

    render() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('ce-inline-tool');
        btn.title = 'Text alignment';
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h12M3 18h15"/></svg>';
        return btn;
    }

    renderActions() {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;gap:2px;padding:4px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.08)';
        this.OPTIONS.forEach(opt => {
            const b = document.createElement('button');
            b.type = 'button';
            b.title = opt.label + ' align';
            b.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:#fff;color:#374151;transition:all .12s';
            b.innerHTML = opt.icon;
            b.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                this.applyAlignmentToSelection(opt.key);
                this.hideActions();
            });
            wrap.appendChild(b);
        });
        // Clear button
        const clear = document.createElement('button');
        clear.type = 'button';
        clear.title = 'Clear alignment';
        clear.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid #fecaca;border-radius:4px;cursor:pointer;background:#fff;color:#dc2626;transition:all .12s;font-weight:700;font-size:14px';
        clear.innerHTML = '×';
        clear.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            this.applyAlignmentToSelection(null);
            this.hideActions();
        });
        wrap.appendChild(clear);

        wrap.style.display = 'none';
        this.actionsEl = wrap;
        return wrap;
    }

    showActions() { if (this.actionsEl) this.actionsEl.style.display = 'flex'; }
    hideActions() { if (this.actionsEl) this.actionsEl.style.display = 'none'; }

    surround(range) {
        // Capture range BEFORE the popup eats focus / the selection is lost.
        this.capturedRange = range;
        if (this.actionsEl && this.actionsEl.style.display === 'flex') {
            this.hideActions();
        } else {
            this.showActions();
        }
    }

    applyAlignmentToSelection(alignment) {
        // Find all .ce-block elements that contain the captured range. If no range,
        // apply to the block where the cursor was.
        let range = this.capturedRange;
        if (!range) {
            const sel = window.getSelection();
            if (sel && sel.rangeCount) range = sel.getRangeAt(0);
        }

        const blocks = new Set();
        if (range) {
            // Walk the range's start container up to find .ce-block, then iterate forward.
            const startBlock = (range.startContainer.nodeType === 1
                ? range.startContainer
                : range.startContainer.parentElement)?.closest('.ce-block');
            const endBlock = (range.endContainer.nodeType === 1
                ? range.endContainer
                : range.endContainer.parentElement)?.closest('.ce-block');
            if (startBlock) blocks.add(startBlock);
            if (endBlock) blocks.add(endBlock);
            // If the range spans multiple blocks, walk siblings from start to end.
            if (startBlock && endBlock && startBlock !== endBlock) {
                let cur = startBlock.nextElementSibling;
                while (cur && cur !== endBlock) {
                    if (cur.classList.contains('ce-block')) blocks.add(cur);
                    cur = cur.nextElementSibling;
                }
            }
        }

        // Fallback: if no blocks resolved, apply to the currently focused block.
        if (blocks.size === 0) {
            const idx = this.api.blocks.getCurrentBlockIndex?.() ?? -1;
            if (idx >= 0) {
                const node = document.querySelectorAll('.ce-block')[idx];
                if (node) blocks.add(node);
            }
        }

        blocks.forEach(blockEl => {
            if (typeof window.applyAlignmentToBlockElement === 'function') {
                window.applyAlignmentToBlockElement(blockEl, alignment);
            }
        });

        // Clear captured range so next click starts fresh.
        this.capturedRange = null;
    }

    checkState() { return false; }
};

/* ─── Inline Text Color tool ─── */
window.ColorTool = class ColorTool {
    static get isInline() { return true; }
    static get title() { return 'Text Color'; }
    static get sanitize() { return { span: { style: true, class: true } }; }

    constructor({ api }) {
        this.api = api;
        this.tag = 'SPAN';
        this.palette = [
            { name: 'Default', value: '' },
            { name: 'Brand', value: 'var(--color-brand, #1563DF)' },
            { name: 'Brand Dark', value: 'var(--color-brand-dark, #0d47a1)' },
            { name: 'Red', value: '#dc2626' },
            { name: 'Orange', value: '#ea580c' },
            { name: 'Amber', value: '#d97706' },
            { name: 'Green', value: '#16a34a' },
            { name: 'Teal', value: '#0d9488' },
            { name: 'Blue', value: '#2563eb' },
            { name: 'Purple', value: '#9333ea' },
            { name: 'Pink', value: '#db2777' },
            { name: 'Gray', value: '#6b7280' },
            { name: 'Black', value: '#111827' },
        ];
    }

    render() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('ce-inline-tool');
        btn.title = 'Text Color';
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16M6 16L12 4l6 12M8 12h8"/></svg>';
        this.button = btn;
        return btn;
    }

    renderActions() {
        const wrap = document.createElement('div');
        wrap.classList.add('ce-color-palette');
        wrap.style.cssText = 'display:none;padding:6px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.08);display:flex;flex-wrap:wrap;gap:4px;max-width:240px';
        this.palette.forEach(c => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = c.name;
            btn.style.cssText = `width:24px;height:24px;border-radius:4px;cursor:pointer;border:1px solid #e5e7eb;${c.value ? `background:${c.value}` : 'background:linear-gradient(45deg,#fff 48%,#ef4444 48%,#ef4444 52%,#fff 52%)'}`;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.applyColor(c.value);
                this.hidePalette();
            });
            wrap.appendChild(btn);
        });
        wrap.style.display = 'none';
        this.paletteEl = wrap;
        return wrap;
    }

    showPalette() { if (this.paletteEl) this.paletteEl.style.display = 'flex'; }
    hidePalette() { if (this.paletteEl) this.paletteEl.style.display = 'none'; }

    surround(range) {
        this.range = range;
        if (this.paletteEl && this.paletteEl.style.display === 'flex') {
            this.hidePalette();
        } else {
            this.showPalette();
        }
    }

    applyColor(color) {
        if (!this.range) {
            const sel = window.getSelection();
            if (sel && sel.rangeCount) this.range = sel.getRangeAt(0);
        }
        if (!this.range) return;

        // Remove existing color wrapper if empty color (Default)
        if (!color) {
            const contents = this.range.extractContents();
            // Strip color styles from all spans
            const wrapper = document.createElement('div');
            wrapper.appendChild(contents);
            wrapper.querySelectorAll('span[style]').forEach(span => {
                span.style.color = '';
                if (!span.getAttribute('style')) span.removeAttribute('style');
            });
            const frag = document.createDocumentFragment();
            while (wrapper.firstChild) frag.appendChild(wrapper.firstChild);
            this.range.insertNode(frag);
            return;
        }

        const span = document.createElement('span');
        span.style.color = color;
        span.appendChild(this.range.extractContents());
        this.range.insertNode(span);

        // Re-select the new span
        const sel = window.getSelection();
        sel.removeAllRanges();
        const newRange = document.createRange();
        newRange.selectNodeContents(span);
        sel.addRange(newRange);
    }

    checkState() {
        // Could highlight the button if selection is already colored — skipped for simplicity
        return false;
    }
};

/* ─── Custom ColumnsTool for EditorJS (2 / 3 / 4 columns) ─── */
window.ColumnsTool = class ColumnsTool {
    static get toolbox() {
        return { title: 'Columns', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="6" height="16" rx="1"/><rect x="10" y="4" width="4" height="16"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }
    /** Same reason as ContainerTool — Enter in a nested column shouldn't kick the user out. */
    static get enableLineBreaks() { return true; }
    constructor({ data, api, config }) {
        this.api = api;
        const d = data && data.columns ? data : {};
        const cols = d.cols || 2;
        let columns = Array.isArray(d.columns) ? d.columns.map(c => typeof c === 'string' ? c : '') : [];
        while (columns.length < cols) columns.push('');
        this.data = { cols, columns };
    }
    renderSettings() {
        const wrapper = document.createElement('div');
        wrapper.style.padding = '6px';
        [2, 3, 4].forEach(n => {
            const btn = document.createElement('div');
            btn.classList.add('cdx-settings-button');
            btn.innerHTML = `${n}<span style="font-size:10px;margin-left:3px">cols</span>`;
            btn.style.cssText = 'display:inline-flex;align-items:center;padding:6px 10px;margin-right:4px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600;border:1px solid #e5e7eb;';
            if (this.data.cols === n) btn.style.background = '#dbeafe';
            btn.addEventListener('click', () => { this.setCols(n); });
            wrapper.appendChild(btn);
        });
        return wrapper;
    }
    setCols(n) {
        this.data.cols = n;
        const curr = this.data.columns.length;
        if (n > curr) { for (let i = curr; i < n; i++) this.data.columns.push(''); }
        else if (n < curr) { this.data.columns = this.data.columns.slice(0, n); }
        this.rebuild();
    }
    render() {
        this.wrap = document.createElement('div');
        this.wrap.style.cssText = 'display:grid;gap:12px;padding:8px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;';
        this.rebuild();
        return this.wrap;
    }
    rebuild() {
        if (!this.wrap) return;
        this.wrap.style.gridTemplateColumns = `repeat(${this.data.cols || 2}, 1fr)`;
        this.wrap.innerHTML = '';
        this.data.columns.forEach((html, idx) => {
            const col = document.createElement('div');
            col.contentEditable = 'true';
            col.dataset.col = idx;
            col.style.cssText = 'min-height:80px;padding:10px;background:#fff;border:1px solid #e5e7eb;border-radius:4px;outline:none;font-size:14px;';
            col.setAttribute('data-placeholder', `Column ${idx + 1}`);
            col.innerHTML = html || '';
            col.addEventListener('input', () => { this.data.columns[idx] = col.innerHTML; });
            this.wrap.appendChild(col);
        });
    }
    save() {
        return { cols: this.data.cols, columns: this.data.columns };
    }
    static get sanitize() {
        return { cols: false, columns: { br: true, p: true, strong: true, em: true, a: { href: true, target: true }, ul: true, ol: true, li: true, span: { class: true, style: true }, div: { class: true, style: true } } };
    }
}

function editorjsField(config) {
    return {
        editor: null,
        uid: config.uid,
        wireModel: config.wireModel,
        initialValue: config.initialValue || '',
        uploadImageUrl: config.uploadImageUrl,
        fetchImageUrl: config.fetchImageUrl,
        uploadFileUrl: config.uploadFileUrl,
        csrfToken: config.csrfToken,
        placeholder: config.placeholder,

        parseInitialData() {
            if (!this.initialValue || this.initialValue === '') return null;

            const val = this.initialValue.trim();

            // Try EditorJS JSON
            if (val.startsWith('{')) {
                try {
                    const parsed = JSON.parse(val);
                    if (parsed.blocks) return parsed;
                } catch (e) {}
            }

            // Legacy HTML — convert common tags to proper blocks
            if (val.startsWith('<') || val.includes('<p') || val.includes('<h')) {
                try {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = val;
                    const blocks = [];
                    tmp.childNodes.forEach(node => {
                        if (node.nodeType !== Node.ELEMENT_NODE) {
                            if (node.textContent.trim()) {
                                blocks.push({ type: 'paragraph', data: { text: node.textContent } });
                            }
                            return;
                        }
                        const tag = node.tagName.toLowerCase();
                        if (tag === 'p' || tag === 'div') {
                            if (node.innerHTML.trim()) {
                                blocks.push({ type: 'paragraph', data: { text: node.innerHTML } });
                            }
                        } else if (/^h[1-6]$/.test(tag)) {
                            blocks.push({ type: 'header', data: { text: node.textContent, level: parseInt(tag[1]) } });
                        } else if (tag === 'ul' || tag === 'ol') {
                            const items = Array.from(node.querySelectorAll('li')).map(li => ({ content: li.innerHTML, items: [] }));
                            if (items.length) { blocks.push({ type: 'list', data: { style: tag === 'ul' ? 'unordered' : 'ordered', items } }); }
                        } else if (tag === 'blockquote') {
                            blocks.push({ type: 'quote', data: { text: node.innerHTML, caption: '', alignment: 'left' } });
                        } else {
                            blocks.push({ type: 'raw', data: { html: node.outerHTML } });
                        }
                    });
                    if (blocks.length) { return { blocks }; }
                } catch (e) {}
                return { blocks: [{ type: 'raw', data: { html: val } }] };
            }

            // Plain text
            if (val.length > 0) {
                return {
                    blocks: [{ type: 'paragraph', data: { text: val } }]
                };
            }

            return null;
        },

        async init() {
            if (this.editor) return;
            this.editor = '_loading_'; // sentinel blocks concurrent calls during await gap

            await this.$nextTick();

            const holderEl = document.getElementById(this.uid);
            if (!holderEl || !window.EditorJS) {
                this.editor = null; // reset so retry can proceed
                setTimeout(() => this.init(), 200);
                return;
            }

            // Destroy any stale EditorJS instance on this DOM node
            if (holderEl._editorjsInstance) {
                try { await holderEl._editorjsInstance.destroy(); } catch (_) {}
                holderEl._editorjsInstance = null;
            }
            holderEl.querySelectorAll('.codex-editor').forEach(el => el.remove());

            const self = this;
            const initialData = this.parseInitialData();

            this.editor = new EditorJS({
                holder: this.uid,
                placeholder: this.placeholder,
                data: initialData || undefined,
                minHeight: 0,

                tools: {
                    // Block tools
                    header: {
                        class: Header,
                        inlineToolbar: true,
                        config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 },
                    },
                    list: {
                        class: NestedList,
                        inlineToolbar: true,
                        config: { defaultStyle: 'unordered' },
                    },
                    checklist: {
                        class: Checklist,
                        inlineToolbar: true,
                    },
                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        config: { quotePlaceholder: 'Enter a quote', captionPlaceholder: 'Quote author' },
                    },
                    code: CodeTool,
                    delimiter: Delimiter,
                    warning: {
                        class: Warning,
                        inlineToolbar: true,
                        config: { titlePlaceholder: 'Title', messagePlaceholder: 'Message' },
                    },
                    table: {
                        class: Table,
                        inlineToolbar: true,
                        config: { rows: 2, cols: 3, withHeadings: true },
                    },
                    image: {
                        class: ImageTool,
                        tunes: window.ImageSizeTune ? ['imageSize'] : [],
                        config: {
                            uploader: {
                                async uploadByFile(file) {
                                    const form = new FormData();
                                    form.append('image', file);
                                    const res = await fetch(self.uploadImageUrl, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                        body: form,
                                    });
                                    return res.json();
                                },
                                async uploadByUrl(url) {
                                    const res = await fetch(self.fetchImageUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': self.csrfToken,
                                        },
                                        body: JSON.stringify({ url }),
                                    });
                                    return res.json();
                                },
                            },
                        },
                    },
                    attaches: {
                        class: AttachesTool,
                        config: {
                            uploader: {
                                async uploadByFile(file) {
                                    const form = new FormData();
                                    form.append('file', file);
                                    const res = await fetch(self.uploadFileUrl, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                        body: form,
                                    });
                                    return res.json();
                                },
                            },
                        },
                    },
                    embed: {
                        class: Embed,
                        config: { services: { youtube: true, vimeo: true, coub: true, imgur: true, gfycat: true, twitch: true, twitter: true } },
                    },
                    linkTool: {
                        class: LinkTool,
                        config: { endpoint: self.fetchImageUrl },
                    },
                    raw: RawTool,

                    // Inline tools
                    marker: Marker,
                    inlineCode: InlineCode,
                    underline: Underline,
                    ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),

                    // Inline alignment (multi-block support — select text across lines, click button)
                    ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),

                    // Block tune — CSS classes per block
                    ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),

                    // Block tune — text alignment (left / center / right / justify)
                    ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),

                    // Block tune — per-image resize (25/50/75/100% or custom)
                    ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),

                    // Columns block (custom)
                    ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),

                    // Container block (custom) — responsive max-width
                    ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),

                    // Space block (custom) — vertical spacer
                    ...(window.SpaceTool ? { space: { class: window.SpaceTool } } : {}),
                },
                tunes: [
                    ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                    ...(window.BlockClassesTune ? ['blockClasses'] : []),
                ],

                onChange: (api, event) => {
                    // Debounce: wait 600ms after last change before syncing to Livewire (triggers server save + preview)
                    clearTimeout(self._saveTimer);
                    self._saveTimer = setTimeout(async () => {
                        try {
                            const outputData = await self.editor.save();
                            // Inject textAlignment tune data for any blocks that were
                            // bulk-aligned via dataset (multi-select scenario).
                            if (typeof window.patchAlignmentTunes === 'function') {
                                window.patchAlignmentTunes(outputData, document.getElementById(self.uid));
                            }
                            const json = JSON.stringify(outputData);
                            if (self.wireModel) {
                                const el = document.getElementById(self.uid);
                                if (el) {
                                    const lwEl = el.closest('[wire\\:id]');
                                    if (lwEl && window.Livewire) {
                                        // true = sync with server immediately (triggers updatedSectionContent → save + preview refresh)
                                        Livewire.find(lwEl.getAttribute('wire:id'))?.set(self.wireModel, json, true);
                                    }
                                }
                            }
                        } catch (e) {
                            console.error('EditorJS save error:', e);
                        }
                    }, 600);
                },

                onReady: () => {
                    // Stamp instance on DOM node so re-init guard can clean it up
                    const el = document.getElementById(self.uid);
                    if (el) el._editorjsInstance = self.editor;

                    // Undo / Redo (Ctrl+Z / Ctrl+Shift+Z). Wrapped — if the plugin
                    // crashes on a particular block type we don't take the editor down.
                    try {
                        if (window.Undo) {
                            self.undo = new window.Undo({ editor: self.editor });
                        }
                    } catch (e) {
                        console.warn('[EditorJS] Undo init failed (non-fatal):', e);
                    }

                    // Floating multi-block alignment toolbar (text selection across blocks)
                    if (el && typeof window.initMultiBlockAlignmentBar === 'function') {
                        window.initMultiBlockAlignmentBar(el);
                    }
                },
            });
        },

        destroy() {
            if (this.editor && typeof this.editor.destroy === 'function') {
                this.editor.destroy();
                this.editor = null;
            }
        },
    };
}
</script>
@endpush
@endonce

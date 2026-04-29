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
.editorjs-container h1.ce-header { font-size: 2em; font-weight: 700; }
.editorjs-container h2.ce-header { font-size: 1.5em; font-weight: 700; }
.editorjs-container h3.ce-header { font-size: 1.25em; font-weight: 600; }
.editorjs-container h4.ce-header { font-size: 1.1em; font-weight: 600; }
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

{{-- Undo/Redo — disabled (causes errors with custom tools) --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/editorjs-undo@2.0.1/dist/bundle.js"></script> --}}

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

/* ─── Container block: wraps content with responsive max-width + custom classes ─── */
window.ContainerTool = class ContainerTool {
    static get toolbox() {
        return { title: 'Container', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h12M6 14h8"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }

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
                ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
            };
            if (window.__editorImageTool) subTools.image = window.__editorImageTool;

            this.subEditor = new EditorJS({
                holder: holder,
                placeholder: 'Container content...',
                data: this.data.content || { blocks: [] },
                minHeight: 80,
                tools: subTools,
                tunes: window.BlockClassesTune ? ['blockClasses'] : [],
                onChange: async () => {
                    try { this.data.content = await this.subEditor.save(); } catch (e) {}
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
            // Find the primary content element inside (h1-h6, p, img, ul/ol, blockquote, etc.)
            const primary = blockEl.querySelector('h1,h2,h3,h4,h5,h6,p,blockquote,ul,ol,img,pre,figure');
            if (primary) {
                primary.className = (this.data.classes || '');
            }
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

                    // Block tune — CSS classes per block
                    ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),

                    // Columns block (custom)
                    ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),

                    // Container block (custom) — responsive max-width
                    ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
                },
                tunes: window.BlockClassesTune ? ['blockClasses'] : [],

                onChange: (api, event) => {
                    // Debounce: wait 600ms after last change before syncing to Livewire (triggers server save + preview)
                    clearTimeout(self._saveTimer);
                    self._saveTimer = setTimeout(async () => {
                        try {
                            const outputData = await self.editor.save();
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
                    // Undo disabled — caused issues with custom tools
                    console.log('[EditorJS] window.ColumnsTool:', typeof window.ColumnsTool);
                    console.log('[EditorJS] Tools registered:', Object.keys(self.editor?.configuration?.tools || {}));
                    console.log('[EditorJS] Tools in toolbox:', Array.from(document.querySelectorAll('.ce-popover-item__title')).map(el => el.textContent));
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

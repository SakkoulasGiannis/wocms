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
/* Fullscreen mode — use full width of parent container */
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
.editorjs-fullscreen-mode .editorjs-container .codex-editor__redactor {
    padding-right: 5rem !important;
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
        const input = prompt('Tailwind / CSS classes for this block:\n(applied to the block element itself — h1, p, img, etc.)', current);
        if (input === null) return;
        this.data.classes = input.trim();
        // Visually reflect on the block element in the editor
        this.applyToBlock();
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

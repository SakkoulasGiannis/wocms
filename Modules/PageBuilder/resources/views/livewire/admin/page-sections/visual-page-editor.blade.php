@push('scripts')
{{-- Container block: responsive max-width per breakpoint + live visual preview --}}
<style>
.editorjs-container .ce-block__content:has(.ctr-tool-wrap) {
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
}
.editorjs-container .ctr-tool-wrap {
    margin-left: auto !important;
    margin-right: auto !important;
    transition: max-width .18s ease;
}
</style>
<script>
window.ContainerTool = class ContainerTool {
    static get toolbox() { return { title: 'Container', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h12M6 14h8"/></svg>' }; }
    static get isReadOnlySupported() { return true; }
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
    constructor({ data, api }) {
        this.api = api;
        const d = data && typeof data === 'object' ? data : {};
        this.data = { desktop: d.desktop || '7xl', tablet: d.tablet || 'full', mobile: d.mobile || 'full', wrapperClass: d.wrapperClass || '', innerClass: d.innerClass || '', content: d.content || { blocks: [] } };
        this.subEditor = null;
    }
    renderSettings() {
        const w = document.createElement('div');
        w.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:280px';
        const mkSel = (lab, k) => {
            const lbl = document.createElement('label'); lbl.textContent = lab; lbl.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            const s = document.createElement('select'); s.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px';
            Object.entries(ContainerTool.WIDTHS).forEach(([kk, v]) => { const o = document.createElement('option'); o.value = kk; o.textContent = v.label; if (this.data[k] === kk) o.selected = true; s.appendChild(o); });
            s.addEventListener('change', (e) => { this.data[k] = e.target.value; this.updateLabel(); this.applyVisualWidth(); });
            const ww = document.createElement('div'); ww.appendChild(lbl); ww.appendChild(s); return ww;
        };
        w.appendChild(mkSel('📱 Mobile', 'mobile'));
        w.appendChild(mkSel('📱 Tablet', 'tablet'));
        w.appendChild(mkSel('🖥️ Desktop', 'desktop'));
        const mkInp = (lab, k, ph) => {
            const lbl = document.createElement('label'); lbl.textContent = lab; lbl.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            const row = document.createElement('div'); row.style.cssText = 'display:flex;gap:4px';
            const i = document.createElement('input'); i.type = 'text'; i.placeholder = ph; i.value = this.data[k] || ''; i.style.cssText = 'flex:1;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:monospace';
            i.addEventListener('input', (e) => { this.data[k] = e.target.value; this.applyLiveClasses(); });
            const pick = document.createElement('button');
            pick.type = 'button'; pick.textContent = '.tw'; pick.title = 'Open Tailwind class picker';
            pick.style.cssText = 'padding:6px 10px;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;font-size:11px;font-family:monospace;font-weight:700;cursor:pointer;color:#4f46e5';
            pick.addEventListener('click', () => {
                if (typeof window.openTailwindClassPicker === 'function') {
                    window.openTailwindClassPicker({
                        current: this.data[k] || '',
                        title: lab,
                        onApply: (classes) => { this.data[k] = (classes || '').trim(); i.value = this.data[k]; this.applyLiveClasses(); },
                    });
                }
            });
            row.appendChild(i); row.appendChild(pick);
            const ww = document.createElement('div'); ww.appendChild(lbl); ww.appendChild(row); return ww;
        };
        w.appendChild(mkInp('Wrapper classes (outer)', 'wrapperClass', 'py-12 bg-slate-50'));
        w.appendChild(mkInp('Inner classes (content)', 'innerClass', 'mx-auto px-4 sm:px-6 lg:px-8'));
        return w;
    }
    applyLiveClasses() {
        // Apply classes live on the editor preview
        if (this.wrap) {
            const base = 'ctr-tool-wrap';
            this.wrap.className = (base + ' ' + (this.data.wrapperClass || '')).trim();
        }
        if (this.innerEl) {
            this.innerEl.className = (this.data.innerClass || '').trim();
        }
    }
    updateLabel() {
        if (this.labelEl) this.labelEl.textContent = `Container · M:${this.data.mobile} T:${this.data.tablet} D:${this.data.desktop}`;
    }
    applyVisualWidth() {
        if (!this.wrap) return;
        const w = ContainerTool.WIDTHS[this.data.desktop] || ContainerTool.WIDTHS['full'];
        const parent = this.wrap.closest('.ce-block__content');
        if (parent) {
            parent.style.setProperty('max-width', 'none', 'important');
            parent.style.setProperty('width', '100%', 'important');
            parent.style.setProperty('margin', '0', 'important');
        }
        this.wrap.style.setProperty('max-width', w.css, 'important');
        this.wrap.style.setProperty('width', '100%', 'important');
        this.wrap.style.setProperty('margin-left', 'auto', 'important');
        this.wrap.style.setProperty('margin-right', 'auto', 'important');
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

        const h = document.createElement('div');
        h.id = `ej-cont-${Math.random().toString(36).slice(2, 9)}`;
        this.innerEl.appendChild(h);
        setTimeout(() => this.applyVisualWidth(), 30);
        setTimeout(() => this.applyVisualWidth(), 200);
        this._onResize = () => this.applyVisualWidth();
        window.addEventListener('resize', this._onResize);
        try {
            const subTools = {
                header: { class: Header, inlineToolbar: true, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                list: { class: NestedList, inlineToolbar: true },
                quote: { class: Quote, inlineToolbar: true },
                marker: Marker, inlineCode: InlineCode, underline: Underline,
                ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
                ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
            };
            if (window.__editorImageTool) subTools.image = window.__editorImageTool;
            this.subEditor = new EditorJS({
                holder: h, placeholder: 'Container content...', data: this.data.content || { blocks: [] }, minHeight: 80, tools: subTools,
                tunes: window.BlockClassesTune ? ['blockClasses'] : [],
                onChange: async () => { try { this.data.content = await this.subEditor.save(); } catch (e) {} },
            });
        } catch (e) { console.warn('Container sub-editor init failed:', e); }
        return this.wrap;
    }
    async save() { if (this.subEditor?.save) { try { this.data.content = await this.subEditor.save(); } catch (e) {} } return { ...this.data }; }
    destroy() {
        try { this.subEditor?.destroy?.(); } catch (e) {} this.subEditor = null;
        if (this._onResize) { window.removeEventListener('resize', this._onResize); this._onResize = null; }
    }
    static get sanitize() { return { desktop: false, tablet: false, mobile: false, wrapperClass: false, innerClass: false, content: false }; }
};
</script>

{{-- Block Tune: per-block CSS classes (Tailwind) --}}
<script>
if (!window.BlockClassesTune) {
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
            if (this.block && this.block.holder) blockEl = this.block.holder;
            else if (blockIndex >= 0) { const nodes = document.querySelectorAll('.ce-block'); blockEl = nodes[blockIndex]; }
            if (!blockEl) return;
            const primary = blockEl.querySelector('h1,h2,h3,h4,h5,h6,p,blockquote,ul,ol,img,pre,figure');
            if (primary) primary.className = (this.data.classes || '');
        } catch (e) {}
    }
    save() { return { classes: this.data.classes || '' }; }
    wrap(blockContent) { setTimeout(() => this.applyToBlock(), 50); return blockContent; }
};
}
</script>

{{-- Inline Text Color tool --}}
<script>
if (!window.ColorTool) {
window.ColorTool = class ColorTool {
    static get isInline() { return true; }
    static get title() { return 'Text Color'; }
    static get sanitize() { return { span: { style: true, class: true } }; }
    constructor({ api }) {
        this.api = api;
        this.palette = [
            { name: 'Default', value: '' },
            { name: 'Brand', value: 'var(--color-brand, #1563DF)' },
            { name: 'Brand Dark', value: 'var(--color-brand-dark, #0d47a1)' },
            { name: 'Red', value: '#dc2626' }, { name: 'Orange', value: '#ea580c' }, { name: 'Amber', value: '#d97706' },
            { name: 'Green', value: '#16a34a' }, { name: 'Teal', value: '#0d9488' }, { name: 'Blue', value: '#2563eb' },
            { name: 'Purple', value: '#9333ea' }, { name: 'Pink', value: '#db2777' }, { name: 'Gray', value: '#6b7280' }, { name: 'Black', value: '#111827' },
        ];
    }
    render() {
        const btn = document.createElement('button');
        btn.type = 'button'; btn.classList.add('ce-inline-tool'); btn.title = 'Text Color';
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16M6 16L12 4l6 12M8 12h8"/></svg>';
        return btn;
    }
    renderActions() {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'padding:6px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.08);display:flex;flex-wrap:wrap;gap:4px;max-width:240px';
        this.palette.forEach(c => {
            const b = document.createElement('button');
            b.type = 'button'; b.title = c.name;
            b.style.cssText = `width:24px;height:24px;border-radius:4px;cursor:pointer;border:1px solid #e5e7eb;${c.value ? `background:${c.value}` : 'background:linear-gradient(45deg,#fff 48%,#ef4444 48%,#ef4444 52%,#fff 52%)'}`;
            b.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this.applyColor(c.value); this.hidePalette(); });
            wrap.appendChild(b);
        });
        wrap.style.display = 'none';
        this.paletteEl = wrap;
        return wrap;
    }
    showPalette() { if (this.paletteEl) this.paletteEl.style.display = 'flex'; }
    hidePalette() { if (this.paletteEl) this.paletteEl.style.display = 'none'; }
    surround(range) {
        this.range = range;
        if (this.paletteEl && this.paletteEl.style.display === 'flex') this.hidePalette();
        else this.showPalette();
    }
    applyColor(color) {
        if (!this.range) { const sel = window.getSelection(); if (sel && sel.rangeCount) this.range = sel.getRangeAt(0); }
        if (!this.range) return;
        if (!color) {
            const contents = this.range.extractContents();
            const w = document.createElement('div'); w.appendChild(contents);
            w.querySelectorAll('span[style]').forEach(s => { s.style.color = ''; if (!s.getAttribute('style')) s.removeAttribute('style'); });
            const frag = document.createDocumentFragment(); while (w.firstChild) frag.appendChild(w.firstChild);
            this.range.insertNode(frag);
            return;
        }
        const span = document.createElement('span'); span.style.color = color;
        span.appendChild(this.range.extractContents());
        this.range.insertNode(span);
    }
    checkState() { return false; }
};
}
</script>

{{-- Custom Columns block for EditorJS (2/3/4/5/6 cols) with nested EditorJS per column --}}
<script>
window.ColumnsTool = class ColumnsTool {
    static get toolbox() {
        return { title: 'Columns', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="6" height="16" rx="1"/><rect x="10" y="4" width="4" height="16"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }
    constructor({ data, api, config }) {
        this.api = api;
        const d = data && typeof data === 'object' ? data : {};
        const cols = d.cols || 2;
        let columns = Array.isArray(d.columns) ? d.columns : [];
        // Normalize each column to an EditorJS data object { blocks: [] }
        columns = columns.map(c => {
            if (c && typeof c === 'object' && Array.isArray(c.blocks)) return c;
            if (typeof c === 'string' && c.trim()) return { blocks: [{ type: 'paragraph', data: { text: c } }] };
            return { blocks: [] };
        });
        while (columns.length < cols) columns.push({ blocks: [] });
        this.data = {
            cols,
            columns,
            wrapperClass: d.wrapperClass || '',
            columnClass: d.columnClass || '',
        };
        this.subEditors = [];
    }
    renderSettings() {
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:280px';

        // Column count buttons
        const row1 = document.createElement('div');
        row1.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px';
        [2, 3, 4, 5, 6].forEach(n => {
            const btn = document.createElement('div');
            btn.classList.add('cdx-settings-button');
            btn.innerHTML = `${n} cols`;
            btn.style.cssText = 'display:inline-flex;align-items:center;padding:6px 10px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600;border:1px solid #e5e7eb;background:#fff';
            if (this.data.cols === n) btn.style.background = '#dbeafe';
            btn.addEventListener('click', () => { this.setCols(n); });
            row1.appendChild(btn);
        });
        wrapper.appendChild(row1);

        // Wrapper CSS classes input
        const wrapLabel = document.createElement('label');
        wrapLabel.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
        wrapLabel.textContent = 'Wrapper classes (Tailwind)';
        const wrapInput = document.createElement('input');
        wrapInput.type = 'text';
        wrapInput.placeholder = 'e.g. my-8 py-6 bg-slate-50 rounded-xl';
        wrapInput.value = this.data.wrapperClass || '';
        wrapInput.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:monospace';
        wrapInput.addEventListener('input', (e) => { this.data.wrapperClass = e.target.value; this.applyLiveClasses(); });
        const wrapWrap = document.createElement('div'); wrapWrap.appendChild(wrapLabel); wrapWrap.appendChild(wrapInput);
        wrapper.appendChild(wrapWrap);

        // Column CSS classes input
        const colLabel = document.createElement('label');
        colLabel.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
        colLabel.textContent = 'Column classes (applied to each)';
        const colInput = document.createElement('input');
        colInput.type = 'text';
        colInput.placeholder = 'e.g. p-6 bg-white rounded-lg shadow';
        colInput.value = this.data.columnClass || '';
        colInput.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:monospace';
        colInput.addEventListener('input', (e) => { this.data.columnClass = e.target.value; this.applyLiveClasses(); });
        const colWrap = document.createElement('div'); colWrap.appendChild(colLabel); colWrap.appendChild(colInput);
        wrapper.appendChild(colWrap);

        return wrapper;
    }
    applyLiveClasses() {
        if (!this.wrap) return;
        // Visual preview hint — apply as data attribute (actual classes are rendered by backend EditorJsRenderer)
        this.wrap.dataset.wrapperClass = this.data.wrapperClass || '';
        // For columns, store on each col element
        Array.from(this.wrap.querySelectorAll(':scope > div')).forEach((col, idx) => {
            if (idx === 0 && col.textContent.match(/^\d+ Columns$/i)) return; // skip label
            col.dataset.columnClass = this.data.columnClass || '';
        });
    }
    async setCols(n) {
        // Save existing column data first
        await this.syncAllColumnsData();
        this.data.cols = n;
        const curr = this.data.columns.length;
        if (n > curr) { for (let i = curr; i < n; i++) this.data.columns.push({ blocks: [] }); }
        else if (n < curr) { this.data.columns = this.data.columns.slice(0, n); }
        this.rebuild();
    }
    async syncAllColumnsData() {
        for (let i = 0; i < this.subEditors.length; i++) {
            const ed = this.subEditors[i];
            if (ed && typeof ed.save === 'function') {
                try { this.data.columns[i] = await ed.save(); } catch(e) {}
            }
        }
    }
    render() {
        this.wrap = document.createElement('div');
        this.wrap.style.cssText = 'display:grid;gap:12px;padding:12px;border:2px dashed #d1d5db;border-radius:8px;background:#f9fafb';
        const label = document.createElement('div');
        label.style.cssText = 'position:absolute;top:-10px;left:10px;background:#f9fafb;padding:0 6px;font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.05em';
        label.textContent = `${this.data.cols} Columns`;
        this.wrap.style.position = 'relative';
        this.wrap.appendChild(label);
        this.rebuild();
        return this.wrap;
    }
    rebuild() {
        if (!this.wrap) return;
        // Destroy existing sub-editors
        this.subEditors.forEach(ed => { try { ed.destroy?.(); } catch(e) {} });
        this.subEditors = [];
        // Keep label, clear rest
        const label = this.wrap.firstChild;
        this.wrap.innerHTML = '';
        this.wrap.appendChild(label);
        label.textContent = `${this.data.cols} Columns`;

        this.wrap.style.gridTemplateColumns = `repeat(${this.data.cols || 2}, 1fr)`;

        this.data.columns.forEach((colData, idx) => {
            const col = document.createElement('div');
            col.style.cssText = 'background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-height:120px';
            const holder = document.createElement('div');
            holder.id = `ej-col-${Math.random().toString(36).slice(2, 9)}`;
            col.appendChild(holder);
            this.wrap.appendChild(col);

            // Initialize nested EditorJS in this column
            try {
                const subTools = {
                    header: { class: Header, inlineToolbar: true, config: { levels: [2, 3, 4], defaultLevel: 3 } },
                    list: { class: NestedList, inlineToolbar: true },
                    quote: { class: Quote, inlineToolbar: true },
                    marker: Marker,
                    inlineCode: InlineCode,
                    underline: Underline,
                };
                // Add image tool only if uploader is configured
                if (window.__editorImageTool) subTools.image = window.__editorImageTool;

                const subEditor = new EditorJS({
                    holder: holder,
                    placeholder: `Column ${idx + 1}...`,
                    data: colData || { blocks: [] },
                    minHeight: 80,
                    tools: subTools,
                    onChange: async () => {
                        try { this.data.columns[idx] = await subEditor.save(); } catch(e) {}
                    },
                });
                this.subEditors.push(subEditor);
            } catch (e) {
                console.warn('Failed to init sub-editor:', e);
                // Fallback to contenteditable
                col.innerHTML = '';
                const ce = document.createElement('div');
                ce.contentEditable = 'true';
                ce.style.cssText = 'min-height:80px;padding:8px;outline:none';
                ce.setAttribute('data-placeholder', `Column ${idx + 1}`);
                ce.addEventListener('input', () => {
                    this.data.columns[idx] = { blocks: [{ type: 'paragraph', data: { text: ce.innerHTML } }] };
                });
                col.appendChild(ce);
                this.subEditors.push(null);
            }
        });
    }
    async save() {
        await this.syncAllColumnsData();
        return { cols: this.data.cols, columns: this.data.columns };
    }
    destroy() {
        this.subEditors.forEach(ed => { try { ed?.destroy?.(); } catch(e) {} });
        this.subEditors = [];
    }
};
</script>

{{-- EditorJS --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.30.8/dist/editorjs.umd.min.js"></script>
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
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@1.4.0/dist/marker.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.5.0/dist/inline-code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@1.2.1/dist/underline.umd.js"></script>
{{-- editorjs-undo disabled — crashes with custom tools (reads .type of undefined) --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/editorjs-undo@2.0.1/dist/bundle.js"></script> --}}
{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
if (typeof window.editorjsField === 'undefined') {
    window.editorjsField = function(config) {
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
                if (val.startsWith('{')) {
                    try { const p = JSON.parse(val); if (p.blocks) return p; } catch(e) {}
                }
                if (val.startsWith('<') || val.includes('<p') || val.includes('<h')) {
                    try {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = val;
                        const blocks = [];
                        tmp.childNodes.forEach(node => {
                            if (node.nodeType !== Node.ELEMENT_NODE) {
                                if (node.textContent.trim()) blocks.push({ type: 'paragraph', data: { text: node.textContent } });
                                return;
                            }
                            const tag = node.tagName.toLowerCase();
                            if (tag === 'p' || tag === 'div') {
                                if (node.innerHTML.trim()) blocks.push({ type: 'paragraph', data: { text: node.innerHTML } });
                            } else if (/^h[1-6]$/.test(tag)) {
                                blocks.push({ type: 'header', data: { text: node.textContent, level: parseInt(tag[1]) } });
                            } else if (tag === 'ul' || tag === 'ol') {
                                const items = Array.from(node.querySelectorAll('li')).map(li => ({ content: li.innerHTML, items: [] }));
                                if (items.length) blocks.push({ type: 'list', data: { style: tag === 'ul' ? 'unordered' : 'ordered', items } });
                            } else {
                                blocks.push({ type: 'raw', data: { html: node.outerHTML } });
                            }
                        });
                        if (blocks.length) return { blocks };
                    } catch(e) {}
                    return { blocks: [{ type: 'raw', data: { html: val } }] };
                }
                if (val.length > 0) return { blocks: [{ type: 'paragraph', data: { text: val } }] };
                return null;
            },

            async init() {
                if (this.editor) return;
                this.editor = '_loading_';
                await this.$nextTick();
                const holderEl = document.getElementById(this.uid);
                if (!holderEl || !window.EditorJS) { this.editor = null; setTimeout(() => this.init(), 200); return; }
                if (holderEl._editorjsInstance) {
                    try { await holderEl._editorjsInstance.destroy(); } catch (_) {}
                    holderEl._editorjsInstance = null;
                }
                holderEl.querySelectorAll('.codex-editor').forEach(el => el.remove());
                const self = this;
                const initialData = this.parseInitialData();

                // Expose image tool to window so nested editors (ColumnsTool) can reuse
                if (!window.__editorImageTool) {
                    window.__editorImageTool = {
                        class: ImageTool,
                        config: { uploader: {
                            async uploadByFile(file) {
                                const form = new FormData(); form.append('image', file);
                                const res = await fetch(self.uploadImageUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': self.csrfToken }, body: form });
                                return res.json();
                            },
                            async uploadByUrl(url) {
                                const res = await fetch(self.fetchImageUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': self.csrfToken }, body: JSON.stringify({ url }) });
                                return res.json();
                            },
                        }},
                    };
                }

                this.editor = new EditorJS({
                    holder: this.uid,
                    placeholder: this.placeholder,
                    data: initialData || undefined,
                    minHeight: 0,
                    tools: {
                        header: { class: Header, inlineToolbar: true, config: { levels: [1,2,3,4,5,6], defaultLevel: 2 } },
                        list: { class: NestedList, inlineToolbar: true, config: { defaultStyle: 'unordered' } },
                        checklist: { class: Checklist, inlineToolbar: true },
                        quote: { class: Quote, inlineToolbar: true },
                        code: CodeTool,
                        delimiter: Delimiter,
                        warning: { class: Warning, inlineToolbar: true },
                        table: { class: Table, inlineToolbar: true },
                        image: { class: ImageTool, config: { uploader: {
                            async uploadByFile(file) {
                                const form = new FormData(); form.append('image', file);
                                const res = await fetch(self.uploadImageUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': self.csrfToken }, body: form });
                                return res.json();
                            },
                            async uploadByUrl(url) {
                                const res = await fetch(self.fetchImageUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': self.csrfToken }, body: JSON.stringify({ url }) });
                                return res.json();
                            },
                        }}},
                        attaches: { class: AttachesTool, config: { uploader: {
                            async uploadByFile(file) {
                                const form = new FormData(); form.append('file', file);
                                const res = await fetch(self.uploadFileUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': self.csrfToken }, body: form });
                                return res.json();
                            },
                        }}},
                        embed: { class: Embed, config: { services: { youtube: true, vimeo: true, twitter: true } } },
                        linkTool: { class: LinkTool, config: { endpoint: self.fetchImageUrl } },
                        raw: RawTool, marker: Marker, inlineCode: InlineCode, underline: Underline,
                        ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
                        ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
                        ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),
                        ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
                    },
                    tunes: window.BlockClassesTune ? ['blockClasses'] : [],
                    onChange: () => {
                        // Debounce: auto-save after 600ms of inactivity, sync immediately so preview refreshes
                        clearTimeout(self._saveTimer);
                        self._saveTimer = setTimeout(async () => {
                            try {
                                const data = await self.editor.save();
                                const json = JSON.stringify(data);
                                if (self.wireModel) {
                                    const el = document.getElementById(self.uid);
                                    if (el) {
                                        const lw = el.closest('[wire\\:id]');
                                        if (lw && window.Livewire) Livewire.find(lw.getAttribute('wire:id'))?.set(self.wireModel, json, true);
                                    }
                                }
                            } catch (e) { console.error(e); }
                        }, 600);
                    },
                    onReady: () => {
                        const el = document.getElementById(self.uid);
                        if (el) el._editorjsInstance = self.editor;
                        if (window.Undo) new Undo({ editor: self.editor });
                    },
                });
            },
            destroy() {
                if (this.editor && typeof this.editor.destroy === 'function') { this.editor.destroy(); this.editor = null; }
            },
        };
    };
}
</script>
@endpush

<div class="flex h-screen overflow-hidden" style="height:100vh; position:relative;"
     x-data="{
         previewUrl: '{{ $previewUrl }}',
         iframeLoading: true,
         reloadPreview() {
             this.iframeLoading = true;
             const f = document.getElementById('preview-frame');
             if (!f) return;
             const url = new URL(f.src, location.href);
             url.searchParams.set('_t', Date.now());
             f.src = url.toString();
         }
     }"
     x-on:preview-reload.window="reloadPreview()">

    {{-- Toast notifications --}}
    <div x-data="{ show: false, message: '', type: 'success', _timer: null }"
         x-on:notify.window="
             message = $event.detail.message;
             type = $event.detail.type ?? 'success';
             show = true;
             clearTimeout(_timer);
             _timer = setTimeout(() => show = false, 4000);
         "
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         style="position:fixed; bottom:24px; right:24px; z-index:9999;"
         :class="type === 'error' ? 'bg-red-600' : 'bg-gray-900'"
         class="flex items-center gap-2.5 px-4 py-3 rounded-lg shadow-xl text-white text-sm max-w-sm">
        <svg x-show="type === 'success'" class="w-4 h-4 flex-shrink-0 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg x-show="type === 'error'" class="w-4 h-4 flex-shrink-0 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span x-text="message"></span>
    </div>

    {{-- ===== LEFT PANEL: Sections List ===== --}}
    <div class="w-72 bg-white border-r border-gray-200 flex flex-col shadow-lg z-10 overflow-hidden" style="min-width:288px; max-width:288px; height:100vh;">

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
            <a href="{{ $previewUrl }}" target="_blank" class="text-gray-400 hover:text-white flex-shrink-0 ml-2" title="Open page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>

        {{-- Sections list --}}
        @php
            $allSectionsCollection = collect($sections);
            $rootSections = $allSectionsCollection->whereNull('parent_section_id')->sortBy('order')->values();
        @endphp
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
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    No sections yet
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
    </div>

    {{-- ===== EDIT PANEL (floats over preview) ===== --}}
    @if($selectedSectionId || $showAddPanel)
        <div class="bg-white border-r border-gray-200 flex flex-col shadow-2xl overflow-hidden"
             style="position:absolute; left:0; top:0; width:288px; height:100vh; z-index:30;">

            {{-- Panel Header --}}
            <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between flex-shrink-0">
                <h3 class="font-semibold text-gray-800 text-sm">
                    @if($showAddPanel)
                        Add Section
                    @else
                        Edit: {{ collect($sections)->firstWhere('id', $selectedSectionId)['name'] ?? 'Section' }}
                    @endif
                </h3>
                @if($showAddPanel && $addingChildOfSectionId)
                    <span class="text-xs text-green-600 font-normal ml-auto mr-2">child</span>
                @endif
                <button wire:click="closePanel"
                        class="text-gray-400 hover:text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">

                @if($showAddPanel)
                    {{-- Template picker --}}
                    @if(!$selectedTemplateId)
                        <div x-data="{ tplSearch: '' }">
                            @if($addingChildOfSectionId)
                                @php $parentName = collect($sections)->firstWhere('id', $addingChildOfSectionId)['name'] ?? 'section'; @endphp
                                <div class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-2.5 py-1.5 mb-3">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Adding child of <span class="font-semibold ml-0.5">{{ $parentName }}</span>
                                </div>
                            @endif
                            <input type="text"
                                   x-model="tplSearch"
                                   placeholder="Search templates..."
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            @php
                                $grouped = collect($availableTemplates)->groupBy('category');
                            @endphp
                            @foreach($grouped as $category => $templates)
                                <div class="mb-3"
                                     x-show="tplSearch === '' || {{ collect($templates)->map(fn($t) => "'" . addslashes(strtolower($t['name'] . ' ' . $t['description'])) . "'.includes(tplSearch.toLowerCase())") ->implode(' || ') }}">
                                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ $category ?: 'General' }}</div>
                                    <div class="space-y-1">
                                        @foreach($templates as $tpl)
                                            <button type="button"
                                                    wire:click="selectTemplate({{ $tpl['id'] }})"
                                                    x-show="tplSearch === '' || '{{ addslashes(strtolower($tpl['name'] . ' ' . $tpl['description'])) }}'.includes(tplSearch.toLowerCase())"
                                                    class="w-full text-left px-3 py-2 rounded-lg border border-gray-200 hover:border-purple-400 hover:bg-purple-50 transition text-sm">
                                                <div class="font-medium text-gray-800">{{ $tpl['name'] }}</div>
                                                @if($tpl['description'])
                                                    <div class="text-xs text-gray-400 mt-0.5">{{ $tpl['description'] }}</div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Selected template info --}}
                        <div class="flex items-center justify-between bg-purple-50 border border-purple-200 rounded-lg px-3 py-2">
                            <div class="text-sm font-medium text-purple-800">
                                {{ collect($availableTemplates)->firstWhere('id', $selectedTemplateId)['name'] ?? '' }}
                            </div>
                            <button wire:click="$set('selectedTemplateId', null)" class="text-purple-400 hover:text-purple-700 text-xs">Change</button>
                        </div>
                    @endif
                @endif

                {{-- Fields form (shown when editing OR when template is selected for add) --}}
                @if($selectedTemplate && ($selectedSectionId || $showAddPanel))
                    {{-- Section name --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Section Name</label>
                        <input type="text" wire:model.live.debounce.500ms="sectionName"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="Section name">
                    </div>

                    {{-- Dynamic Fields --}}
                    @foreach($selectedTemplate->fields as $field)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                {{ $field->label }}
                                @if($field->is_required)<span class="text-red-500">*</span>@endif
                            </label>

                            @switch($field->type)
                                @case('text')
                                @case('url')
                                @case('email')
                                    @if($field->name === 'class' || str_ends_with($field->name, '_class'))
                                        <div x-data="{
                                            tags: (@js($sectionContent[$field->name] ?? '')).split(/\s+/).filter(t => t !== ''),
                                            inputVal: '',
                                            open: false,
                                            suggestions: [],
                                            addTag() {
                                                this.inputVal.trim().split(/\s+/).forEach(part => {
                                                    const t = part.replace(/[,;]/g, '').trim();
                                                    if (t && !this.tags.includes(t)) this.tags.push(t);
                                                });
                                                this.inputVal = '';
                                                this.open = false;
                                                this.sync();
                                            },
                                            removeTag(tag) { this.tags = this.tags.filter(t => t !== tag); this.sync(); },
                                            sync() {
                                                $wire.set('sectionContent.{{ $field->name }}', this.tags.filter(t => t.trim()).join(' '));
                                            },
                                            search() {
                                                const q = this.inputVal.trim().toLowerCase();
                                                if (!q) { this.open = false; return; }
                                                this.suggestions = (window._twClasses||[]).filter(c => c.startsWith(q) || c.includes(q)).slice(0,15);
                                                this.open = this.suggestions.length > 0;
                                            },
                                            pick(s) {
                                                if (!this.tags.includes(s)) { this.tags.push(s); this.sync(); }
                                                this.inputVal = '';
                                                this.open = false;
                                            }
                                        }">
                                            <div class="flex flex-wrap gap-1 p-2 border border-gray-300 rounded-lg min-h-[38px] cursor-text bg-white" @click="$refs.twInput.focus()">
                                                <template x-for="tag in tags" :key="tag">
                                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded text-xs font-mono">
                                                        <span x-text="tag"></span>
                                                        <button type="button" @click.stop="removeTag(tag)" class="text-purple-400 hover:text-purple-700 leading-none ml-0.5">&times;</button>
                                                    </span>
                                                </template>
                                                <input type="text" x-ref="twInput" x-model="inputVal"
                                                       @keydown.enter.prevent="addTag()"
                                                       @keydown.188.prevent="addTag()"
                                                       @input.debounce.200ms="search()"
                                                       @blur="setTimeout(() => open = false, 150)"
                                                       class="flex-1 min-w-20 outline-none text-xs font-mono bg-transparent"
                                                       placeholder="e.g. flex gap-4">
                                            </div>
                                            <div x-show="open" class="relative">
                                                <div class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-36 overflow-y-auto">
                                                    <template x-for="s in suggestions" :key="s">
                                                        <button type="button" @mousedown.prevent="pick(s)"
                                                                class="w-full text-left px-3 py-1.5 text-xs font-mono hover:bg-purple-50 hover:text-purple-700">
                                                            <span x-text="s"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <input type="{{ $field->type }}"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                               placeholder="{{ $field->placeholder ?? $field->label }}">
                                    @endif
                                    @break

                                @case('textarea')
                                    <textarea wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                              rows="3"
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                              placeholder="{{ $field->placeholder ?? $field->label }}"></textarea>
                                    @break

                                @case('number')
                                    <input type="number"
                                           wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    @break

                                @case('checkbox')
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model.live="sectionContent.{{ $field->name }}"
                                               class="w-4 h-4 text-purple-600 rounded">
                                        <span class="text-sm text-gray-700">{{ $field->placeholder ?? 'Enable' }}</span>
                                    </label>
                                    @break

                                @case('select')
                                    @php
                                        // Dynamic model-based select fields
                                        $dynamicOptions = null;
                                        if ($field->name === 'slider_id' && class_exists(\Modules\Slider\Models\Slider::class)) {
                                            $dynamicOptions = \Modules\Slider\Models\Slider::where('is_active', true)
                                                ->orderBy('name')->get()
                                                ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
                                                ->toArray();
                                        }

                                        if ($dynamicOptions === null) {
                                            // Static options from field->options OR field->settings['options']
                                            $rawOptions = $field->options ?? null;
                                            if ($rawOptions) {
                                                $dynamicOptions = is_string($rawOptions) ? json_decode($rawOptions, true) : $rawOptions;
                                            } else {
                                                $opts = is_string($field->settings) ? json_decode($field->settings, true) : ($field->settings ?? []);
                                                $dynamicOptions = $opts['options'] ?? [];
                                            }
                                        }
                                        $dynamicOptions = $dynamicOptions ?: [];
                                    @endphp
                                    <select wire:model.live="sectionContent.{{ $field->name }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">— Select —</option>
                                        @foreach($dynamicOptions as $opt)
                                            <option value="{{ $opt['value'] ?? $opt }}">{{ $opt['label'] ?? $opt }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('color')
                                    <div class="flex items-center gap-2">
                                        <input type="color"
                                               wire:model.live="sectionContent.{{ $field->name }}"
                                               class="w-10 h-10 rounded cursor-pointer border border-gray-300">
                                        <input type="text"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                               placeholder="#000000">
                                    </div>
                                    @break

                                @case('image')
                                    @if(!empty($sectionContent[$field->name]))
                                        <div class="relative inline-block mb-2">
                                            <img src="{{ $sectionContent[$field->name] }}" alt="" class="h-20 rounded-lg border border-gray-200 object-cover">
                                            <button type="button" wire:click="$set('sectionContent.{{ $field->name }}', '')"
                                                    class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
                                        </div>
                                    @endif
                                    <div class="flex gap-2">
                                        <label class="cursor-pointer flex items-center gap-1.5 px-2 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-200 text-xs transition" title="Upload new file">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            <input type="file" wire:model="sectionImageUploads.{{ $field->name }}" accept="image/*" class="hidden">
                                        </label>
                                        <button type="button"
                                                wire:click="openMediaLibrary('{{ $field->name }}')"
                                                class="flex items-center gap-1 px-2 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg border border-purple-200 text-xs transition" title="Browse media library">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <input type="text"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-xs"
                                               placeholder="or paste URL">
                                    </div>
                                    @break

                                @case('wysiwyg')
                                    <div x-data="{
                                            fullscreen: false,
                                            _mo: null,
                                            _forceWide(root) {
                                                if (!root) return;
                                                const widthSelectors = ['.codex-editor', '.codex-editor__redactor'];
                                                widthSelectors.forEach(sel => root.querySelectorAll(sel).forEach(el => {
                                                    el.style.setProperty('max-width','100%','important');
                                                    el.style.setProperty('width','100%','important');
                                                }));
                                                const blockSelectors = ['.ce-block__content','.ce-toolbar__content'];
                                                blockSelectors.forEach(sel => root.querySelectorAll(sel).forEach(el => {
                                                    el.style.setProperty('max-width','100%','important');
                                                    el.style.setProperty('width','100%','important');
                                                    el.style.setProperty('margin','0','important');
                                                }));
                                            },
                                            _clearWide(root) {
                                                if (!root) return;
                                                ['.codex-editor','.codex-editor__redactor','.ce-block__content','.ce-toolbar__content'].forEach(sel => {
                                                    root.querySelectorAll(sel).forEach(el => {
                                                        el.style.removeProperty('max-width');
                                                        el.style.removeProperty('width');
                                                        el.style.removeProperty('margin');
                                                    });
                                                });
                                            },
                                            applyFullscreen() {
                                                document.body.classList.toggle('editorjs-fullscreen-mode', this.fullscreen);
                                                const root = this.$el.querySelector('.editorjs-container');
                                                if (!root) return;
                                                if (this.fullscreen) {
                                                    this._forceWide(root);
                                                    if (this._mo) this._mo.disconnect();
                                                    this._mo = new MutationObserver(() => this._forceWide(root));
                                                    this._mo.observe(root, { childList: true, subtree: true });
                                                } else {
                                                    if (this._mo) { this._mo.disconnect(); this._mo = null; }
                                                    this._clearWide(root);
                                                }
                                            },
                                         }"
                                         x-init="$watch('fullscreen', () => applyFullscreen())"
                                         x-on:beforeunload.window="document.body.classList.remove('editorjs-fullscreen-mode'); if(_mo){_mo.disconnect();_mo=null;}"
                                         x-on:livewire:navigated.window="document.body.classList.remove('editorjs-fullscreen-mode'); if(_mo){_mo.disconnect();_mo=null;}"
                                         :class="fullscreen ? 'fixed inset-0 z-[1000] bg-white flex flex-col editorjs-fullscreen-mode' : ''">
                                        <div :class="fullscreen ? 'flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-gray-50' : 'flex items-center justify-end mb-1'">
                                            <span x-show="fullscreen" class="text-sm font-semibold text-gray-700">{{ $field->label }}</span>
                                            <button type="button"
                                                    @click="fullscreen = !fullscreen"
                                                    class="inline-flex items-center gap-1 text-xs text-purple-600 hover:text-purple-800 font-medium">
                                                <svg x-show="!fullscreen" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                                </svg>
                                                <svg x-show="fullscreen" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                <span x-text="fullscreen ? 'Κλείσιμο' : 'Fullscreen'"></span>
                                            </button>
                                        </div>
                                        <div :class="fullscreen ? 'flex-1 overflow-y-auto' : ''"
                                             :style="fullscreen ? 'padding:15px' : ''">
                                            <div :class="fullscreen ? 'w-full' : ''">
                                                <x-editorjs-field
                                                    :name="'ve.' . $field->name"
                                                    :value="$sectionContent[$field->name] ?? ''"
                                                    wire-model="sectionContent.{{ $field->name }}"
                                                    :uid="'ejs-ve-' . $field->name . ($selectedSectionId ?? 'new')"
                                                    min-height="400px"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('repeater')
                                    @php
                                        $rfSettings = $field->settings;
                                        if (is_string($rfSettings)) $rfSettings = json_decode($rfSettings, true);
                                        $rfSubFields = $rfSettings['sub_fields'] ?? [];
                                        $rfItems = $sectionContent[$field->name] ?? [];
                                        if (!is_array($rfItems)) $rfItems = [];
                                    @endphp
                                    <div class="space-y-2">
                                        @foreach($rfItems as $rfIdx => $rfItem)
                                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-medium text-gray-500">Item {{ $rfIdx + 1 }}</span>
                                                    <button type="button"
                                                            wire:click="removeRepeaterItem('{{ $field->name }}', {{ $rfIdx }})"
                                                            class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                                                </div>
                                                @foreach($rfSubFields as $sf)
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-0.5">{{ $sf['label'] ?? $sf['name'] }}</label>
                                                        @if(($sf['type'] ?? 'text') === 'textarea')
                                                            <textarea wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                      rows="2"
                                                                      class="w-full border border-gray-300 rounded px-2 py-1 text-xs"></textarea>
                                                        @elseif(($sf['type'] ?? 'text') === 'image')
                                                            <input type="text"
                                                                   wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs"
                                                                   placeholder="Image URL">
                                                        @else
                                                            <input type="text"
                                                                   wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        <button type="button"
                                                wire:click="addRepeaterItem('{{ $field->name }}')"
                                                class="w-full py-1.5 border border-dashed border-gray-300 rounded-lg text-xs text-gray-500 hover:border-purple-400 hover:text-purple-600 transition">
                                            + Add Item
                                        </button>
                                    </div>
                                    @break

                                @default
                                    <input type="text"
                                           wire:model="sectionContent.{{ $field->name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @endswitch
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Panel Footer --}}
            @if($selectedTemplate && ($selectedSectionId || $showAddPanel))
                <div class="border-t border-gray-200 flex-shrink-0">
                    @if(!$showAddPanel)
                        {{-- Save as template --}}
                        <div class="px-3 py-2 border-b border-gray-100"
                             x-data="{ open: false, name: '{{ addslashes($sectionName) }}' }">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                                Save as reusable template
                            </button>
                            <div x-show="open" x-cloak class="mt-1.5 flex gap-1.5">
                                <input type="text"
                                       x-model="name"
                                       placeholder="Template name..."
                                       class="flex-1 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <button type="button"
                                        @click="$wire.saveAsTemplate(name); open = false"
                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium transition">
                                    Save
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="px-3 py-2.5 flex items-center justify-between">
                        @if($showAddPanel)
                            <button wire:click="saveSection"
                                    class="flex-1 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="saveSection">Add Section</span>
                                <span wire:loading wire:target="saveSection">Adding...</span>
                            </button>
                        @else
                            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                <span wire:loading wire:target="updateSection">
                                    <svg class="w-3.5 h-3.5 animate-spin text-purple-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span class="text-purple-500">Saving…</span>
                                </span>
                                <span wire:loading.remove wire:target="updateSection" class="text-gray-400">Auto-save on</span>
                            </div>
                            <button wire:click="deleteSection({{ $selectedSectionId }})"
                                    onclick="return confirm('Delete this section?')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-xs transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== PREVIEW IFRAME ===== --}}
    <div class="flex-1 flex flex-col bg-gray-200 overflow-hidden"
         x-data="{
             showJsonModal: false,
             showImportModal: false,
             jsonContent: '',
             importContent: '',
             copied: false,
             viewport: 'desktop',
             viewports: {
                 mobile:  { width: '390px',  label: 'Mobile',  icon: 'phone' },
                 tablet:  { width: '768px',  label: 'Tablet',  icon: 'tablet' },
                 desktop: { width: '100%',   label: 'Desktop', icon: 'desktop' },
             },
             openJson() {
                 $wire.getPageJson().then(data => {
                     this.jsonContent = JSON.stringify(data, null, 2);
                     this.showJsonModal = true;
                 });
             },
             copyJson() {
                 navigator.clipboard.writeText(this.jsonContent);
                 this.copied = true;
                 setTimeout(() => this.copied = false, 2000);
             },
             submitImport() {
                 $wire.importJson(this.importContent);
                 this.showImportModal = false;
                 this.importContent = '';
             }
         }"
         x-on:notify.window="console.log($event.detail)"
         x-on:preview-patch.window="patchPreview($event.detail)"
         x-on:preview-visibility.window="patchVisibility($event.detail)"
         x-init="
             $data.patchPreview = function(detail) {
                 if (!detail || !detail.sectionId) return;
                 const frame = document.getElementById('preview-frame');
                 if (!frame?.contentWindow) { reloadPreview(); return; }
                 frame.contentWindow.postMessage({ type: 've-patch', sectionId: detail.sectionId, html: detail.html }, '*');
             };
             $data.patchVisibility = function(detail) {
                 if (!detail || !detail.sectionId) return;
                 const frame = document.getElementById('preview-frame');
                 if (!frame?.contentDocument) return;
                 const wrapper = frame.contentDocument.querySelector('[data-ve-section-id=\'' + detail.sectionId + '\']');
                 if (wrapper) wrapper.classList.toggle('ve-hidden', !detail.visible);
             };
         ">

        {{-- Preview toolbar --}}
        <div class="flex items-center gap-3 px-4 py-2 bg-gray-800 flex-shrink-0">
            <div class="flex items-center gap-1 bg-gray-700 rounded-lg px-3 py-1.5 flex-1 max-w-md">
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                </svg>
                <span class="text-gray-300 text-xs truncate">{{ config('app.url') }}{{ $previewUrl }}</span>
            </div>

            {{-- Build assets --}}
            <button wire:click="buildAssets"
                    wire:loading.attr="disabled"
                    title="Run npm run build"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition bg-gray-700 text-gray-400 hover:text-white hover:bg-gray-600 disabled:opacity-50">
                <svg wire:loading.remove wire:target="buildAssets" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <svg wire:loading wire:target="buildAssets" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span wire:loading.remove wire:target="buildAssets">Build</span>
                <span wire:loading wire:target="buildAssets">Building…</span>
            </button>

            {{-- Tailwind CDN toggle --}}
            <button wire:click="toggleTailwindCdn"
                    title="{{ $veTailwindCdn ? 'Tailwind CDN active — click to disable' : 'Enable Tailwind browser CDN in preview' }}"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition
                           {{ $veTailwindCdn ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-400 hover:text-white hover:bg-gray-600' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                Tailwind CDN
            </button>

            {{-- Undo / Redo --}}
            <div class="flex items-center bg-gray-700 rounded-lg p-0.5 gap-0.5">
                <button wire:click="undo"
                        class="flex items-center px-2.5 py-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-500 transition disabled:opacity-40"
                        title="Undo (Ctrl+Z)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button wire:click="redo"
                        class="flex items-center px-2.5 py-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-500 transition disabled:opacity-40"
                        title="Redo (Ctrl+Y)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>
            </div>

            {{-- Viewport switcher --}}
            <div class="flex items-center bg-gray-700 rounded-lg p-0.5 gap-0.5">
                {{-- Mobile --}}
                <button x-on:click="viewport = 'mobile'"
                        :class="viewport === 'mobile' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Mobile (390px)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </button>
                {{-- Tablet --}}
                <button x-on:click="viewport = 'tablet'"
                        :class="viewport === 'tablet' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Tablet (768px)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </button>
                {{-- Desktop --}}
                <button x-on:click="viewport = 'desktop'"
                        :class="viewport === 'desktop' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Desktop (full width)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>

            {{-- JSON / Export / Import buttons --}}
            <div class="flex items-center gap-2 border-l border-gray-600 pl-3">
                {{-- Preview JSON --}}
                <button x-on:click="openJson()"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Preview JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    JSON
                </button>

                {{-- Export --}}
                <button wire:click="exportJson"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Export JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </button>

                {{-- Import --}}
                <button x-on:click="showImportModal = true; importContent = ''"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Import JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                    Import
                </button>
            </div>

            <button x-on:click="reloadPreview()" class="text-gray-400 hover:text-white transition" title="Reload preview">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <a href="{{ $previewUrl }}" target="_blank" class="text-gray-400 hover:text-white transition" title="Open in new tab">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>

        {{-- JSON Preview Modal --}}
        <div x-show="showJsonModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-6"
             style="background:rgba(0,0,0,0.7);">
            <div class="bg-gray-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-700">
                    <h3 class="text-white font-semibold text-sm">Page JSON</h3>
                    <div class="flex items-center gap-2">
                        <button x-on:click="copyJson()"
                                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied ✓</span>
                        </button>
                        <button x-on:click="showJsonModal = false" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4">
                    <pre class="text-green-400 text-xs font-mono whitespace-pre-wrap" x-text="jsonContent"></pre>
                </div>
            </div>
        </div>

        {{-- Import Modal --}}
        <div x-show="showImportModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-6"
             style="background:rgba(0,0,0,0.7);">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h3 class="font-semibold text-gray-800 text-sm">Import Page JSON</h3>
                    <button x-on:click="showImportModal = false" class="text-gray-400 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-3">Paste a valid page JSON below. This will replace all current sections.</p>
                    <textarea x-model="importContent"
                              rows="12"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder='{ "version": "1.0", "sections": [...] }'></textarea>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button x-on:click="showImportModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                    <button x-on:click="submitImport()"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition">
                        Import
                    </button>
                </div>
            </div>
        </div>

        {{-- Media Library Modal --}}
        @if($showMediaLibrary)
            @php $mediaFiles = $this->getMediaFiles(); @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-6"
                 style="background:rgba(0,0,0,0.7);">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl flex flex-col" style="max-height:80vh;">
                    <div class="flex items-center justify-between px-5 py-3 border-b flex-shrink-0">
                        <h3 class="font-semibold text-gray-800 text-sm">Media Library</h3>
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Upload
                                <input type="file" wire:model="sectionImageUploads.{{ $mediaTargetField }}" accept="image/*" class="hidden">
                            </label>
                            <button wire:click="closeMediaLibrary" class="text-gray-400 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        @if(empty($mediaFiles))
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm">No images found. Upload one above.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-4 gap-3">
                                @foreach($mediaFiles as $file)
                                    <button type="button"
                                            wire:click="selectMedia('{{ $file['url'] }}')"
                                            class="group relative aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-purple-500 transition">
                                        <img src="{{ $file['url'] }}"
                                             alt="{{ $file['name'] }}"
                                             class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition flex items-end">
                                            <p class="w-full px-1.5 py-1 bg-black/60 text-white text-[10px] truncate opacity-0 group-hover:opacity-100 transition">{{ $file['name'] }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Iframe --}}
        <div class="flex-1 relative overflow-auto bg-gray-300">
            <div x-show="iframeLoading"
                 class="absolute inset-0 bg-gray-100 flex items-center justify-center z-10 pointer-events-none">
                <div class="text-center text-gray-400">
                    <svg class="w-8 h-8 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="text-sm">Loading preview…</span>
                </div>
            </div>
            {{-- Wrapper centers the iframe and applies a drop shadow when not full-width --}}
            <div class="h-full flex flex-col items-center transition-all duration-300"
                 :class="viewport !== 'desktop' ? 'py-4' : ''"
                 :style="viewport !== 'desktop' ? 'min-height:100%' : 'height:100%'">
                <div class="transition-all duration-300 shadow-2xl bg-white h-full"
                     :style="'width:' + viewports[viewport].width + '; height:100%; ' + (viewport !== 'desktop' ? 'border-radius:12px; overflow:hidden;' : '')">
                    <iframe id="preview-frame"
                            src="{{ $previewUrl }}"
                            class="border-0 w-full h-full"
                            x-on:load="iframeLoading = false"
                            style="display:block;"></iframe>
                </div>
                <div x-show="viewport !== 'desktop'"
                     class="mt-2 text-xs text-gray-500 font-medium"
                     x-text="viewports[viewport].label + ' — ' + viewports[viewport].width"></div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.ve-ghost { opacity: 0.4; background: #ede9fe !important; border-radius: 6px; }
.ve-dragging { opacity: 0.9; }
.ve-children-list { transition: background 0.15s; }
.ve-children-list.sortable-over { background: #f5f3ff; border-color: #a78bfa !important; }
</style>
@endpush

@push('scripts')
<script>
window._twClasses = (function(){
    const spacing = [0,0.5,1,1.5,2,2.5,3,3.5,4,5,6,7,8,9,10,11,12,14,16,20,24,28,32,36,40,44,48,52,56,60,64,72,80,96];
    const s = spacing.map(String);
    const prefixes = ['p','px','py','pt','pr','pb','pl','m','mx','my','mt','mr','mb','ml','gap','gap-x','gap-y','space-x','space-y'];
    const colors = ['slate','gray','zinc','neutral','stone','red','orange','amber','yellow','lime','green','emerald','teal','cyan','sky','blue','indigo','violet','purple','fuchsia','pink','rose'];
    const shades = [50,100,200,300,400,500,600,700,800,900,950];
    const list = [];
    prefixes.forEach(p => s.forEach(n => list.push(p+'-'+n)));
    ['w','h','min-w','min-h','max-w','max-h'].forEach(p => {
        s.forEach(n => list.push(p+'-'+n));
        ['full','screen','auto','fit','min','max','px','svh','dvh'].forEach(k => list.push(p+'-'+k));
        [2,3,4,5,6,12].forEach(d => list.push(p+'-1/'+d, p+'-2/'+d));
    });
    ['text','bg','border','ring','fill','stroke','from','to','via','decoration','accent','caret','shadow','outline','placeholder'].forEach(p => {
        list.push(p+'-white', p+'-black', p+'-transparent', p+'-current', p+'-inherit');
        colors.forEach(c => shades.forEach(s => list.push(p+'-'+c+'-'+s)));
    });
    ['text-xs','text-sm','text-base','text-lg','text-xl','text-2xl','text-3xl','text-4xl','text-5xl','text-6xl',
     'font-thin','font-extralight','font-light','font-normal','font-medium','font-semibold','font-bold','font-extrabold','font-black',
     'italic','not-italic','underline','line-through','no-underline','uppercase','lowercase','capitalize','normal-case',
     'text-left','text-center','text-right','text-justify','truncate','text-ellipsis','whitespace-nowrap','whitespace-pre','whitespace-pre-wrap',
     'leading-none','leading-tight','leading-snug','leading-normal','leading-relaxed','leading-loose',
     'tracking-tighter','tracking-tight','tracking-normal','tracking-wide','tracking-wider','tracking-widest',
     'flex','inline-flex','grid','inline-grid','block','inline-block','inline','hidden','contents','table','table-cell','table-row',
     'flex-row','flex-col','flex-row-reverse','flex-col-reverse','flex-wrap','flex-nowrap','flex-wrap-reverse',
     'flex-1','flex-auto','flex-none','flex-shrink','flex-grow','grow','shrink',
     'items-start','items-center','items-end','items-stretch','items-baseline',
     'justify-start','justify-center','justify-end','justify-between','justify-around','justify-evenly',
     'self-start','self-center','self-end','self-stretch','self-auto',
     'grid-cols-1','grid-cols-2','grid-cols-3','grid-cols-4','grid-cols-5','grid-cols-6','grid-cols-7','grid-cols-8','grid-cols-9','grid-cols-10','grid-cols-11','grid-cols-12','grid-cols-none',
     'col-span-1','col-span-2','col-span-3','col-span-4','col-span-5','col-span-6','col-span-full',
     'grid-rows-1','grid-rows-2','grid-rows-3','grid-rows-4','grid-rows-5','grid-rows-6','grid-rows-none',
     'row-span-1','row-span-2','row-span-3','row-span-4','row-span-5','row-span-6','row-span-full',
     'place-items-start','place-items-center','place-items-end','place-items-stretch',
     'place-content-start','place-content-center','place-content-end','place-content-between','place-content-around','place-content-evenly',
     'relative','absolute','fixed','sticky','static',
     'top-0','bottom-0','left-0','right-0','inset-0','inset-x-0','inset-y-0',
     'z-0','z-10','z-20','z-30','z-40','z-50','z-auto',
     'overflow-hidden','overflow-auto','overflow-scroll','overflow-visible','overflow-x-hidden','overflow-y-auto','overflow-y-scroll',
     'rounded','rounded-sm','rounded-md','rounded-lg','rounded-xl','rounded-2xl','rounded-3xl','rounded-full','rounded-none',
     'rounded-t','rounded-b','rounded-l','rounded-r','rounded-tl','rounded-tr','rounded-bl','rounded-br',
     'border','border-0','border-2','border-4','border-8','border-t','border-b','border-l','border-r',
     'border-solid','border-dashed','border-dotted','border-none',
     'shadow','shadow-sm','shadow-md','shadow-lg','shadow-xl','shadow-2xl','shadow-inner','shadow-none',
     'ring','ring-0','ring-1','ring-2','ring-4','ring-8','ring-inset',
     'opacity-0','opacity-5','opacity-10','opacity-20','opacity-25','opacity-30','opacity-40','opacity-50','opacity-60','opacity-70','opacity-75','opacity-80','opacity-90','opacity-95','opacity-100',
     'transition','transition-all','transition-colors','transition-opacity','transition-shadow','transition-transform',
     'duration-75','duration-100','duration-150','duration-200','duration-300','duration-500','duration-700','duration-1000',
     'ease-linear','ease-in','ease-out','ease-in-out',
     'cursor-pointer','cursor-default','cursor-not-allowed','cursor-move','cursor-grab','cursor-text',
     'select-none','select-text','select-all',
     'pointer-events-none','pointer-events-auto',
     'object-cover','object-contain','object-fill','object-none','object-scale-down',
     'object-center','object-top','object-bottom','object-left','object-right',
     'aspect-auto','aspect-square','aspect-video',
     'prose','prose-sm','prose-lg','prose-xl','prose-2xl',
     'container','mx-auto','sr-only','not-sr-only',
     'appearance-none','outline-none','outline','resize','resize-none','resize-y',
    ].forEach(c => list.push(c));
    ['sm','md','lg','xl','2xl'].forEach(bp => {
        list.push(bp+':flex', bp+':grid', bp+':hidden', bp+':block', bp+':flex-row', bp+':flex-col', bp+':grid-cols-1', bp+':grid-cols-2', bp+':grid-cols-3', bp+':grid-cols-4');
    });
    return [...new Set(list)].sort();
})();
</script>
<script>
document.addEventListener('livewire:initialized', function () {
    const lwEl = () => document.querySelector('[wire\\:id]');
    const wireCall = (method, ...args) => {
        const el = lwEl();
        if (el && window.Livewire) Livewire.find(el.getAttribute('wire:id')).call(method, ...args);
    };

    const sortableOpts = {
        group: { name: 've-sections', pull: true, put: true },
        handle: '.ve-drag-handle',
        animation: 150,
        fallbackOnBody: true,
        swapThreshold: 0.5,
        ghostClass: 've-ghost',
        dragClass: 've-dragging',
        onEnd(evt) {
            const itemId = parseInt(evt.item.dataset.id);
            if (!itemId) return;

            const toContainerAttr = evt.to.dataset.container;
            const newParentId = (toContainerAttr !== '' && toContainerAttr !== undefined)
                ? parseInt(toContainerAttr)
                : null;

            const siblings = Array.from(evt.to.children)
                .filter(el => el.dataset && el.dataset.id);
            const newOrder = siblings.indexOf(evt.item) + 1;

            wireCall('moveSection', itemId, newParentId, newOrder);
        }
    };

    function initAllSortables() {
        if (!window.Sortable) return;

        const root = document.getElementById('ve-sections-list');
        if (root) {
            if (root._sortable) root._sortable.destroy();
            root._sortable = new Sortable(root, sortableOpts);
        }

        document.querySelectorAll('.ve-children-list').forEach(el => {
            if (el._sortable) el._sortable.destroy();
            el._sortable = new Sortable(el, sortableOpts);
        });
    }

    initAllSortables();
    Livewire.hook('morph.updated', () => setTimeout(initAllSortables, 100));

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        const tag = document.activeElement?.tagName;
        const isEditable = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)
            || document.activeElement?.contentEditable === 'true';

        if (e.key === 'Escape') {
            wireCall('closePanel');
            return;
        }
        if (isEditable) return;

        const ctrl = e.metaKey || e.ctrlKey;
        if (ctrl && e.key === 'z' && !e.shiftKey) {
            e.preventDefault();
            wireCall('undo');
        } else if (ctrl && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
            e.preventDefault();
            wireCall('redo');
        } else if (ctrl && e.key === 'd') {
            e.preventDefault();
            const el = lwEl();
            if (el) {
                const cmp = Livewire.find(el.getAttribute('wire:id'));
                const selId = cmp?.get('selectedSectionId');
                if (selId) wireCall('duplicateSection', selId);
            }
        } else if ((e.key === 'Delete' || e.key === 'Backspace') && !isEditable) {
            const el = lwEl();
            if (el) {
                const cmp = Livewire.find(el.getAttribute('wire:id'));
                const selId = cmp?.get('selectedSectionId');
                if (selId && confirm('Delete this section?')) wireCall('deleteSection', selId);
            }
        }
    });

    // Listen for section clicks from the preview iframe
    window.addEventListener('message', function (e) {
        if (e.data && e.data.type === 've-section-click') {
            wireCall('selectSection', e.data.sectionId);
        }
    });

    // Highlight active section in iframe when selected from sidebar
    document.addEventListener('livewire:navigated', syncActiveSection);
    Livewire.hook('morph.updated', syncActiveSection);
    function syncActiveSection() {
        const frame = document.getElementById('preview-frame');
        if (!frame || !frame.contentDocument) return;
        const activeSectionId = {{ $selectedSectionId ?? 'null' }};
        frame.contentDocument.querySelectorAll('.ve-section-wrapper').forEach(el => {
            el.classList.toggle('ve-active', parseInt(el.dataset.veSectionId) === activeSectionId);
        });
    }
});
</script>
@endpush

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
    /** Enter inside the nested sub-editor must NOT bubble up and create a new outer block. */
    static get enableLineBreaks() { return true; }
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
        w.setAttribute('data-ctr-settings', '');
        w.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:100%;min-width:0;max-width:100%;box-sizing:border-box';
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
                header: { class: (window.HeaderWithInlineTools || Header), inlineToolbar: true, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                list: { class: NestedList, inlineToolbar: true },
                quote: { class: Quote, inlineToolbar: true },
                code: CodeTool,
                delimiter: Delimiter,
                raw: RawTool,
                ...(typeof Table !== 'undefined' ? { table: { class: Table, inlineToolbar: true } } : {}),
                marker: Marker, inlineCode: InlineCode, underline: Underline,
                ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
                ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),
                ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
                ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),
                ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),
                ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),
                ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
                ...(window.LiveHtmlTool ? { liveHtml: { class: window.LiveHtmlTool } } : {}),
                ...(window.SpaceTool   ? { space:   { class: window.SpaceTool   } } : {}),
                ...(window.SectionEmbedTool ? { sectionEmbed: { class: window.SectionEmbedTool } } : {}),
            };
            if (window.__editorImageTool) {
                subTools.image = {
                    ...window.__editorImageTool,
                    tunes: window.ImageSizeTune ? ['imageSize'] : [],
                };
            }
            this.subEditor = new EditorJS({
                holder: h, placeholder: 'Container content...', data: this.data.content || { blocks: [] }, minHeight: 80, tools: subTools,
                tunes: [
                    ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                    ...(window.BlockClassesTune ? ['blockClasses'] : []),
                ],
                onChange: async () => { try { this.data.content = await this.subEditor.save(); } catch (e) {} },
                onReady: () => {
                    if (typeof window.initMultiBlockAlignmentBar === 'function') {
                        window.initMultiBlockAlignmentBar(h);
                    }
                },
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
            const primary = blockEl.querySelector('.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, img, pre, figure');
            if (!primary) return;
            // ADDITIVE: preserve EditorJS internal classes (ce-header, ce-paragraph) — only manage user-applied tokens
            const prev = (primary.dataset.btcClasses || '').split(/\s+/).filter(Boolean);
            prev.forEach(c => primary.classList.remove(c));
            const next = (this.data.classes || '').trim().split(/\s+/).filter(Boolean);
            next.forEach(c => primary.classList.add(c));
            primary.dataset.btcClasses = next.join(' ');
        } catch (e) {}
    }
    save() { return { classes: this.data.classes || '' }; }
    wrap(blockContent) { setTimeout(() => this.applyToBlock(), 50); return blockContent; }
};
}
</script>

{{-- Block Tune: Text Alignment (fallback if component hasn't defined it) --}}
<script>
window.findBlockPrimary = window.findBlockPrimary || function(blockEl) {
    if (!blockEl) return null;
    return blockEl.querySelector('.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, figure, pre, [contenteditable="true"]');
};
window.applyAlignmentToBlockElement = window.applyAlignmentToBlockElement || function(blockEl, alignment) {
    if (!blockEl) return;
    const primary = window.findBlockPrimary(blockEl);
    if (primary) primary.style.textAlign = alignment || '';
    if (alignment) blockEl.dataset.textAlignment = alignment; else delete blockEl.dataset.textAlignment;
};
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

if (!window.TextAlignmentTune) {
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
        this.api = api; this.block = block;
        this.data = (data && typeof data === 'object') ? data : {};
        this.buttons = []; this.countLabel = null;
    }
    getTargetBlocks() {
        const selected = Array.from(document.querySelectorAll('.ce-block--selected'));
        const own = (this.block && this.block.holder) ? this.block.holder : null;
        if (selected.length > 0) {
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
            btn.type = 'button'; btn.title = opt.label + ' align'; btn.dataset.align = opt.key;
            btn.style.cssText = 'flex:1;min-width:32px;display:inline-flex;align-items:center;justify-content:center;padding:5px 6px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:#fff;color:#374151;transition:all .12s';
            btn.innerHTML = opt.icon;
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const next = (this.data.alignment === opt.key) ? null : opt.key;
                const targets = this.getTargetBlocks();
                targets.forEach(blockEl => window.applyAlignmentToBlockElement(blockEl, next));
                const own = (this.block && this.block.holder) ? this.block.holder : null;
                if (own && targets.includes(own)) {
                    this.data.alignment = next;
                    this.refreshActive();
                }
                try { this.block?.dispatchChange?.(); } catch (er) {}
                if (targets.length > 1) this.flashCount(`Applied to ${targets.length} blocks`);
            });
            this.buttons.push(btn); wrap.appendChild(btn);
        });
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
    save() { return { alignment: this.data.alignment || null }; }
    wrap(blockContent) { setTimeout(() => this.applyToBlock(), 50); return blockContent; }
};
}
</script>

{{-- Space block (fallback definition for visual-page-editor) --}}
<script>
if (!window.SpaceTool) {
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
        lbl.textContent = 'Quick presets'; lbl.style.cssText = 'font-size:11px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:0.04em';
        wrap.appendChild(lbl);
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:2px';
        SpaceTool.PRESETS.forEach(p => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = p.label; b.title = p.value;
            b.style.cssText = `flex:1;padding:6px 4px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:${this.data.height === p.value ? 'linear-gradient(135deg,#10b981,#059669)' : '#fff'};color:${this.data.height === p.value ? '#fff' : '#374151'};font-size:11px;font-weight:600`;
            b.addEventListener('click', () => {
                this.data.height = p.value; this.applyHeight();
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
            if (v) { this.data.height = v; this.applyHeight(); }
        });
        wrap.appendChild(custom);
        return wrap;
    }
    save() { return { height: this.data.height || '2rem' }; }
    static get sanitize() { return { height: false }; }
};
}
</script>

{{-- Image Size Tune (fallback definition for visual-page-editor) --}}
<script>
if (!window.ImageSizeTune) {
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
        this.api = api; this.block = block;
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
            btn.type = 'button'; btn.title = `Resize image to ${opt.label}`; btn.dataset.size = opt.key;
            btn.style.cssText = 'flex:1;min-width:42px;padding:5px 8px;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;background:#fff;color:#374151;font-size:11px;font-weight:600;transition:all .12s';
            btn.textContent = opt.label;
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const next = (this.data.size === opt.key) ? null : opt.key;
                this.data.size = next;
                this.applyToBlock();
                this.refreshActive();
            });
            this.buttons.push(btn); wrap.appendChild(btn);
        });
        const customWrap = document.createElement('div');
        customWrap.style.cssText = 'display:flex;align-items:center;gap:4px;width:100%;margin-top:4px';
        const customLbl = document.createElement('span'); customLbl.textContent = 'Custom:';
        customLbl.style.cssText = 'font-size:11px;color:#6b7280';
        const customInput = document.createElement('input');
        customInput.type = 'text'; customInput.placeholder = '420px or 60%';
        customInput.value = (this.data.custom || '');
        customInput.style.cssText = 'flex:1;padding:4px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:11px;font-family:ui-monospace,monospace';
        customInput.addEventListener('input', (e) => {
            this.data.custom = e.target.value.trim();
            this.data.size = this.data.custom ? 'custom' : null;
            this.applyToBlock();
            this.refreshActive();
        });
        customWrap.appendChild(customLbl); customWrap.appendChild(customInput);
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
            if (this.data.size === 'custom' && this.data.custom) widthValue = this.data.custom;
            else if (this.data.size) {
                const opt = ImageSizeTune.OPTIONS.find(o => o.key === this.data.size);
                if (opt) widthValue = opt.width;
            }
            if (widthValue) {
                img.style.setProperty('width', widthValue, 'important');
                img.style.setProperty('max-width', widthValue, 'important');
                img.style.setProperty('height', 'auto', 'important');
                img.dataset.imgSize = this.data.size + (this.data.size === 'custom' ? ':' + this.data.custom : '');
            } else {
                img.style.removeProperty('width');
                img.style.removeProperty('max-width');
                img.style.removeProperty('height');
                delete img.dataset.imgSize;
            }
        } catch (e) {}
    }
    save() { return { size: this.data.size || null, custom: this.data.custom || null }; }
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
    /** Same as Container — Enter in a column shouldn't bubble out to the outer editor. */
    static get enableLineBreaks() { return true; }
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
        wrapper.setAttribute('data-ctr-settings', '');
        wrapper.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:100%;min-width:0;max-width:100%;box-sizing:border-box';

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
                    header: { class: (window.HeaderWithInlineTools || Header), inlineToolbar: true, config: { levels: [2, 3, 4], defaultLevel: 3 } },
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
                    onReady: () => {
                        if (typeof window.initMultiBlockAlignmentBar === 'function') {
                            const holderEl = (typeof holder === 'string') ? document.getElementById(holder) : holder;
                            if (holderEl) window.initMultiBlockAlignmentBar(holderEl);
                        }
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

{{-- EditorJS scripts loaded by the editorjs-field component (preloaded in layout).
     Self-host loader at /vendor/editorjs/* — no CDN dependency. --}}
{{-- SortableJS still loaded directly here (used by the section list, not by EditorJS) --}}
<script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
<script>
// ----------------------------------------------------------------------
// Reusable HTML → EditorJS blocks parser (recursive).
//
// Structural wrappers (section/div/article/main/header/footer/aside/nav)
// that contain block-level children are RECURSED into:
//   • styled wrapper (class/id/style/data-*) → `container` block whose
//     wrapperClass preserves the wrapper's classes and whose content is
//     the parsed children — children stay individually editable.
//   • plain wrapper → flattened (children emitted directly).
// Leaf nodes follow the per-tag contract: styled → raw (classes intact),
// clean p/h1-6/ul/ol/blockquote/pre/hr/img/figure → editable blocks,
// everything else (tables, iframes, video, svg, …) → raw.
// ----------------------------------------------------------------------
// NOTE: defined UNCONDITIONALLY (no `typeof ... === undefined` guard) so the
// latest parser always wins, even across Livewire wire:navigate transitions
// that keep `window` alive. Editing this file then navigating now picks up the
// new logic without a full page reload.
window._veHtmlToBlocks = function (html) {
        if (typeof html !== 'string' || html.length === 0) return { blocks: [] };

        // CLEAN-NATIVE mode: every element becomes a real EditorJS primitive block
        // (image→image, h1-6→header, p→paragraph, ul/ol→list, …). Tailwind/CSS
        // classes are intentionally DROPPED so blocks are natively editable.
        // Layout wrappers (div/section/…) are flattened. svg/script/style stripped.
        const readAlignment = (el) => {
            const ta = (el.style && el.style.textAlign) ? el.style.textAlign.toLowerCase() : '';
            return ['left', 'center', 'right', 'justify'].includes(ta) ? ta : null;
        };
        const wrapTune = (block, align) => {
            if (align) block.tunes = { textAlignment: { alignment: align } };
            return block;
        };
        const cleanInner = (el) => {
            const c = el.cloneNode(true);
            c.querySelectorAll('svg,script,style,noscript').forEach(n => n.remove());
            return c.innerHTML.trim();
        };

        function parseNodes(nodeList) {
            const out = [];
            Array.from(nodeList).forEach(node => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    if (node.textContent && node.textContent.trim()) {
                        out.push({ type: 'paragraph', data: { text: node.textContent.trim() } });
                    }
                    return;
                }
                const tag = node.tagName.toLowerCase();
                if (['script', 'style', 'svg', 'noscript', 'link', 'meta', 'head'].includes(tag)) return;

                const align = readAlignment(node);
                const elementChildren = Array.from(node.children);

                if (tag === 'img') {
                    out.push({ type: 'image', data: { file: { url: node.getAttribute('src') || '' }, caption: node.getAttribute('alt') || '', withBorder: false, withBackground: false, stretched: false } });
                    return;
                }
                if (tag === 'figure') {
                    const fImg = node.querySelector('img');
                    if (fImg) {
                        const fCap = node.querySelector('figcaption');
                        out.push({ type: 'image', data: { file: { url: fImg.getAttribute('src') || '' }, caption: (fCap ? fCap.textContent.trim() : (fImg.getAttribute('alt') || '')), withBorder: false, withBackground: false, stretched: false } });
                    } else {
                        parseNodes(node.childNodes).forEach(b => out.push(b));
                    }
                    return;
                }
                if (/^h[1-6]$/.test(tag)) {
                    const t = cleanInner(node);
                    if (t) out.push(wrapTune({ type: 'header', data: { text: t, level: parseInt(tag[1]) } }, align));
                    return;
                }
                if (tag === 'ul' || tag === 'ol') {
                    const items = Array.from(node.querySelectorAll(':scope > li')).map(li => ({ content: cleanInner(li), items: [] })).filter(it => it.content);
                    if (items.length) out.push(wrapTune({ type: 'list', data: { style: tag === 'ul' ? 'unordered' : 'ordered', items } }, align));
                    return;
                }
                if (tag === 'blockquote') {
                    const t = cleanInner(node);
                    if (t) out.push(wrapTune({ type: 'quote', data: { text: t, caption: '', alignment: 'left' } }, align));
                    return;
                }
                if (tag === 'pre') {
                    const codeEl = node.querySelector('code') || node;
                    out.push({ type: 'code', data: { code: codeEl.textContent || '' } });
                    return;
                }
                if (tag === 'hr') { out.push({ type: 'delimiter', data: {} }); return; }
                if (tag === 'table') {
                    const rows = Array.from(node.querySelectorAll('tr')).map(tr => Array.from(tr.querySelectorAll('th,td')).map(c => cleanInner(c)));
                    if (rows.length) out.push({ type: 'table', data: { withHeadings: !!node.querySelector('th'), content: rows } });
                    return;
                }
                if (tag === 'a') {
                    const t = node.innerHTML.trim();
                    const href = node.getAttribute('href') || '#';
                    if (t) out.push({ type: 'paragraph', data: { text: '<a href="' + href + '">' + t + '</a>' } });
                    return;
                }

                // Layout wrappers + generic containers → flatten (drop wrapper + classes).
                if (elementChildren.length) {
                    parseNodes(node.childNodes).forEach(b => out.push(b));
                } else {
                    const t = cleanInner(node);
                    if (t) out.push(wrapTune({ type: 'paragraph', data: { text: t } }, align));
                }
            });
            return out;
        }

        let blocks = [];
        try {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            blocks = parseNodes(tmp.childNodes);
        } catch (e) { /* fall through */ }
        if (!blocks.length) {
            const tmp2 = document.createElement('div');
            tmp2.innerHTML = html;
            const txt = (tmp2.textContent || '').trim();
            if (txt) return { blocks: [{ type: 'paragraph', data: { text: txt } }] };
        }
        return { blocks };
};

// Heuristic: does this pasted text look like HTML? Must start with a tag
// AND contain a closing tag (or self-closing), so plain "<3" or stray
// "<" characters do not trigger. Defined unconditionally (see note above).
window._veLooksLikeHtml = function (str) {
        if (typeof str !== 'string') return false;
        const trimmed = str.trim();
        if (trimmed.length < 4) return false;
        if (trimmed[0] !== '<') return false;
        return /<\/?[a-z][\s\S]*?>/i.test(trimmed);
};

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
                    return window._veHtmlToBlocks(val);
                }
                if (val.length > 0) return { blocks: [{ type: 'paragraph', data: { text: val } }] };
                return null;
            },

            // Insert HTML-derived blocks at the current caret position.
            // Replaces the current empty paragraph block (if empty) and
            // inserts each parsed block sequentially.
            async insertHtmlAsBlocks(html) {
                if (!this.editor || typeof this.editor.blocks?.insert !== 'function') return false;
                // Styled markup (has class=/style=) → ONE "HTML (live)" block that
                // renders the full design and is editable in place (text + image
                // picker). Plain markup → clean native blocks via the parser.
                let parsed;
                if (window.LiveHtmlTool && /\b(class|style)\s*=/.test(html)) {
                    parsed = { blocks: [{ type: 'liveHtml', data: { html: html } }] };
                } else {
                    parsed = window._veHtmlToBlocks(html);
                }
                if (!parsed || !parsed.blocks || !parsed.blocks.length) return false;
                let insertIdx;
                try {
                    insertIdx = this.editor.blocks.getCurrentBlockIndex();
                    if (typeof insertIdx !== 'number' || insertIdx < 0) {
                        insertIdx = this.editor.blocks.getBlocksCount();
                    }
                } catch (e) {
                    insertIdx = 0;
                }
                // If current block is empty, replace it with the first
                // parsed block; otherwise insert after it.
                let replaceCurrent = false;
                try {
                    const current = this.editor.blocks.getBlockByIndex(insertIdx);
                    if (current) {
                        // Guard with a timeout: a container block's nested-editor
                        // save() can hang and would otherwise freeze the whole paste.
                        const data = await Promise.race([
                            Promise.resolve(current.save?.()),
                            new Promise(r => setTimeout(() => r(null), 400)),
                        ]);
                        const isEmpty = !data || !data.data || (
                            (typeof data.data.text === 'string' && data.data.text.trim() === '') &&
                            !data.data.items?.length && !data.data.code
                        );
                        if (isEmpty) replaceCurrent = true;
                    }
                } catch (e) { /* keep replaceCurrent=false */ }

                for (let i = 0; i < parsed.blocks.length; i++) {
                    const b = parsed.blocks[i];
                    const idx = insertIdx + (replaceCurrent ? 0 : 1) + i;
                    try {
                        this.editor.blocks.insert(b.type, b.data, {}, idx, replaceCurrent && i === 0);
                    } catch (e) {
                        console.warn('[paste] insert failed for block', b.type, e);
                    }
                }
                return true;
            },

            async init() {
                if (this.editor) return;
                this.editor = '_loading_';
                await this.$nextTick();
                const holderEl = document.getElementById(this.uid);
                // Wait until the FULL editorjs script bundle is loaded — not just EditorJS itself.
                // window._editorjsLoaded is set by the loader in editorjs-field.blade.php after
                // every tool (Header, NestedList, Quote, …) is loaded. Without this guard, init()
                // can proceed after editorjs.js loads but before header.js, throwing
                // "Header is not defined" inside the tools config below.
                if (!holderEl || !window.EditorJS || !window._editorjsLoaded || typeof Header === 'undefined') {
                    this.editor = null; setTimeout(() => this.init(), 200); return;
                }
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
                        header: { class: (window.HeaderWithInlineTools || Header), inlineToolbar: true, config: { levels: [1,2,3,4,5,6], defaultLevel: 2 } },
                        list: { class: NestedList, inlineToolbar: true, config: { defaultStyle: 'unordered' } },
                        checklist: { class: Checklist, inlineToolbar: true },
                        quote: { class: Quote, inlineToolbar: true },
                        code: CodeTool,
                        delimiter: Delimiter,
                        warning: { class: Warning, inlineToolbar: true },
                        table: { class: Table, inlineToolbar: true },
                        image: { class: ImageTool, tunes: window.ImageSizeTune ? ['imageSize'] : [], config: { uploader: {
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
                        ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),
                        ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),
                        ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),
                        ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
                        ...(window.LiveHtmlTool ? { liveHtml: { class: window.LiveHtmlTool } } : {}),
                        ...(window.SpaceTool ? { space: { class: window.SpaceTool } } : {}),
                        ...(window.SectionEmbedTool ? { sectionEmbed: { class: window.SectionEmbedTool } } : {}),
                    },
                    tunes: [
                        ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                        ...(window.BlockClassesTune ? ['blockClasses'] : []),
                    ],
                    onChange: () => {
                        // NO autosave. Saving happens ONLY when the user clicks the
                        // Save / Save & Close button (which collects every editor via
                        // veCollectEditors → saveContent). Here we just keep the
                        // Livewire property in sync CLIENT-SIDE (deferred `false`, no
                        // server round-trip / re-render) so the data is current for
                        // the next save, without disrupting editing of pasted/liveHtml
                        // blocks the way a live commit on every keystroke did.
                        clearTimeout(self._saveTimer);
                        self._saveTimer = setTimeout(async () => {
                            try {
                                const data = await self.editor.save();
                                if (typeof window.patchAlignmentTunes === 'function') {
                                    window.patchAlignmentTunes(data, document.getElementById(self.uid));
                                }
                                const json = JSON.stringify(data);
                                if (self.wireModel) {
                                    const el = document.getElementById(self.uid);
                                    if (el) {
                                        const lw = el.closest('[wire\\:id]');
                                        if (lw && window.Livewire) Livewire.find(lw.getAttribute('wire:id'))?.set(self.wireModel, json, false);
                                    }
                                }
                            } catch (e) { console.error(e); }
                        }, 600);
                    },
                    onReady: () => {
                        const el = document.getElementById(self.uid);
                        if (el) el._editorjsInstance = self.editor;
                        try { if (window.Undo) new window.Undo({ editor: self.editor }); } catch (e) { console.warn('[EditorJS] Undo init failed (non-fatal):', e); }
                        try { if (window.DragDrop) new window.DragDrop(self.editor); } catch (e) { console.warn('[EditorJS] DragDrop init failed (non-fatal):', e); }
                        if (el && typeof window.initMultiBlockAlignmentBar === 'function') {
                            window.initMultiBlockAlignmentBar(el);
                        }

                        // ----------------------------------------------------------
                        // Paste interceptor: if user pastes plain text that looks
                        // like HTML, convert it to editable blocks instead of
                        // letting EditorJS treat each line as a separate paragraph.
                        // We listen in capture phase to beat EditorJS's own paste
                        // handler, but only intercept when the text/plain payload
                        // genuinely starts with a tag — otherwise we let the
                        // default behavior run (so quoting "<3" still works).
                        // ----------------------------------------------------------
                        if (el && !el._vePasteHooked) {
                            el._vePasteHooked = true;
                            el.addEventListener('paste', async function (ev) {
                                try {
                                    const cd = ev.clipboardData || window.clipboardData;
                                    if (!cd) return;
                                    // Bail when the paste target is inside a nested editor
                                    // (Container/Columns subEditor). This handler runs in
                                    // capture mode, so without this guard the outer editor
                                    // grabs the paste FIRST and inserts the blocks at the
                                    // outer level — they land after the Container instead
                                    // of inside it.
                                    const closestEditor = ev.target && ev.target.closest
                                        ? ev.target.closest('.codex-editor')
                                        : null;
                                    const outerEditor = el.querySelector(':scope > .codex-editor')
                                        || el.querySelector('.codex-editor');
                                    if (closestEditor && outerEditor && closestEditor !== outerEditor) {
                                        return; // nested editor — let it handle its own paste
                                    }
                                    // ONLY intercept when the user pasted raw HTML *markup*
                                    // as plain text (e.g. copied source from a code editor).
                                    // In that case text/plain holds the real markup, while
                                    // text/html holds an ESCAPED, line-wrapped pretty version
                                    // (<div>&lt;section…&gt;</div> per line) — parsing that
                                    // produces one paragraph per line. So we use text/plain.
                                    // Genuine rich-content pastes (Word, Docs, web selection)
                                    // have non-markup plain text and are left to EditorJS's
                                    // native handler untouched.
                                    const plain = cd.getData('text/plain') || '';
                                    if (!window._veLooksLikeHtml(plain)) return;
                                    ev.preventDefault();
                                    ev.stopPropagation();
                                    await self.insertHtmlAsBlocks(plain.trim());
                                } catch (e) {
                                    console.warn('[paste] interceptor failed (non-fatal):', e);
                                }
                            }, true);
                        }

                        // Click an image block → open media picker to replace it
                        if (el && typeof window._veAttachImageReplace === 'function') {
                            window._veAttachImageReplace(el, self.editor);
                        }
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

(function () {
    if (window._editorjsLoaderStarted) return;
    window._editorjsLoaderStarted = true;

    const BASE = '{{ asset('vendor/editorjs') }}';
    // Order matters: core first, then plugins (each plugin reads from its own globals).
    const FILES = [
        // Core
        'editorjs.js',
        // Block tools
        'header.js', 'nested-list.js', 'quote.js', 'code.js', 'delimiter.js',
        'image.js', 'embed.js', 'table.js', 'link.js', 'raw.js',
        'checklist.js', 'warning.js', 'attaches.js',
        // Inline tools
        'marker.js', 'inline-code.js', 'underline.js',
        // UX plugins
        'editorjs-undo.js', 'editorjs-drag-drop.js',
    ];

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(s);
        });
    }

    // Cache-bust the vendored EditorJS bundles so Cloudflare / browser caches
    // never serve a stale version after we patch one (e.g. the code.js
    // [object HTMLPreElement] paste bug). Bump this when any vendor/editorjs/*
    // file changes.
    const VENDOR_V = '2026052702';
    async function loadAll() {
        if (window._editorjsLoaded) return;
        for (const f of FILES) {
            try { await loadScript(BASE + '/' + f + '?v=' + VENDOR_V); }
            catch (e) { console.warn('[EditorJS loader]', e.message); }
        }
        window._editorjsLoaded = true;
        // Notify any waiting Alpine components — they retry on a 200ms loop.
    }

    function startWhenNeeded() {
        // If any editorjs-container is already in the DOM, load now.
        if (document.querySelector('.editorjs-container')) {
            loadAll();
            return;
        }
        // Otherwise, watch for one to appear (admin pages mount Livewire components dynamically).
        const obs = new MutationObserver(() => {
            if (document.querySelector('.editorjs-container')) {
                obs.disconnect();
                loadAll();
            }
        });
        obs.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startWhenNeeded);
    } else {
        startWhenNeeded();
    }
})();
</script>

<script>
/* ─── Keep EditorJS popovers inside the viewport ──────────────────────────
   The block-settings / tunes popover (incl. the Container tool's
   Mobile/Tablet/Desktop width panel) opens at the block's position. For a
   block in a nested column near the right edge, the popover extends past the
   viewport and gets clipped — its controls become unreachable.

   Fix: when a popover becomes visible, measure it and nudge it back inside
   the viewport with a transform translate. Pure transform — does not fight
   EditorJS's own absolute top/left positioning, just shifts the painted box.

   Idempotent: the nudge is computed additively from the *current* transform,
   so once the popover sits inside the viewport the next pass writes nothing
   (no observer feedback loop). The stale transform is cleared only on a
   fresh closed→open transition, tracked per-element via a WeakMap. */
(function keepEditorPopoversInView() {
    if (window._ejPopoverFitInstalled) return;
    window._ejPopoverFitInstalled = true;

    var MARGIN = 8;
    var SELECTOR = '.ce-popover, .ce-popover__container, .ce-settings, .ce-conversion-toolbar';
    var openState = new WeakMap();

    /* While a popover is open, force every clipping ancestor (overflow other
       than visible) to overflow:visible so the popover — which is
       position:absolute and otherwise trapped inside a narrow nested
       column/editor — can paint fully. Original values are saved on the
       element and restored when the popover closes. */
    function unclip(popover) {
        var touched = [];
        var node = popover.parentElement;
        while (node && node !== document.body && node !== document.documentElement) {
            var cs = window.getComputedStyle(node);
            // SKIP elements that explicitly opt out (e.g. the fullscreen wysiwyg
            // host whose `overflow-y: auto` IS the scroll mechanism). Also skip
            // any ancestor whose overflow is already `visible` — there's
            // nothing to unclip.
            // Also stop walking past a `position: fixed` ancestor: it creates a
            // new viewport for absolute popovers, so further unclipping above
            // it is pointless AND was leaving orphan state behind when the
            // popover disappeared without firing reclip.
            if (node.hasAttribute && node.hasAttribute('data-no-unclip')) {
                node = node.parentElement;
                continue;
            }
            if (cs.position === 'fixed') {
                break;
            }
            if (cs.overflow !== 'visible' || cs.overflowX !== 'visible' || cs.overflowY !== 'visible') {
                touched.push({
                    el: node,
                    o: node.style.overflow, ox: node.style.overflowX, oy: node.style.overflowY,
                });
                node.style.setProperty('overflow', 'visible', 'important');
            }
            node = node.parentElement;
        }
        popover._ejUnclipped = touched;
    }
    function reclip(popover) {
        (popover._ejUnclipped || []).forEach(function (t) {
            t.el.style.overflow = t.o;
            t.el.style.overflowX = t.ox;
            t.el.style.overflowY = t.oy;
        });
        popover._ejUnclipped = null;
    }

    function fit(el) {
        if (!el || !el.isConnected) return;
        var isOpen = el.offsetParent !== null;
        var wasOpen = openState.get(el) === true;
        openState.set(el, isOpen);

        if (!isOpen) {
            if (wasOpen) { reclip(el); el.style.transform = ''; }
            return;
        }
        // Fresh open → un-clip ancestors and drop any stale transform.
        if (!wasOpen) {
            el.style.transform = '';
            unclip(el);
        }

        var r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) return;

        var vw = window.innerWidth, vh = window.innerHeight, dx = 0, dy = 0;
        if (r.right > vw - MARGIN) dx = (vw - MARGIN) - r.right;
        if (r.left + dx < MARGIN) dx += MARGIN - (r.left + dx);
        if (r.bottom > vh - MARGIN) dy = (vh - MARGIN) - r.bottom;
        if (r.top + dy < MARGIN) dy += MARGIN - (r.top + dy);

        // Add the delta to whatever transform is already applied — converges.
        var m = (el.style.transform || '').match(/translate\(\s*(-?[0-9.]+)px\s*,\s*(-?[0-9.]+)px\s*\)/);
        var cx = m ? parseFloat(m[1]) : 0;
        var cy = m ? parseFloat(m[2]) : 0;
        var nx = cx + dx, ny = cy + dy;
        if (Math.abs(nx - cx) > 0.5 || Math.abs(ny - cy) > 0.5) {
            el.style.transform = 'translate(' + Math.round(nx) + 'px,' + Math.round(ny) + 'px)';
        }
    }

    function fitAllVisible() {
        document.querySelectorAll(SELECTOR).forEach(function (el) {
            requestAnimationFrame(function () { fit(el); });
        });
    }

    var mo = new MutationObserver(function () { fitAllVisible(); });
    mo.observe(document.body, {
        childList: true, subtree: true,
        attributes: true, attributeFilter: ['class', 'style'],
    });
    window.addEventListener('resize', fitAllVisible, { passive: true });
    window.addEventListener('scroll', fitAllVisible, { passive: true, capture: true });
})();
</script>

<script>
/* ─── Custom Header subclass that allows inline tools to survive save ──────
   Default @editorjs/header has `static get sanitize() { return { level: false, text: {} } }`
   which strips ALL inline HTML on save — so color spans / marker / underline
   work in paragraphs but get lost in headings. We subclass Header and provide
   a wider sanitize config. Use this in the editor's tools config instead of
   the bare Header. */
window.HeaderWithInlineTools = null;
(function buildHeaderSubclass() {
    const tryBuild = () => {
        if (!window.Header) return false;
        try {
            window.HeaderWithInlineTools = class HeaderWithInlineTools extends window.Header {
                static get sanitize() {
                    return {
                        level: false,
                        text: {
                            br: true,
                            b: true,
                            i: true,
                            u: true,
                            s: true,
                            mark: true,
                            code: true,
                            a: { href: true, target: true, rel: true },
                            span: { style: true, class: true },
                        },
                    };
                }
            };
            return true;
        } catch (e) {
            console.warn('[HeaderWithInlineTools] build failed:', e);
            return false;
        }
    };
    if (!tryBuild()) {
        let tries = 0;
        const id = setInterval(() => { if (tryBuild() || ++tries > 60) clearInterval(id); }, 50);
    }

})();

/* ─── ParagraphWithInlineTools — STANDALONE Paragraph block (no dependency
   on @editorjs/paragraph CDN, which isn't loaded on the visual editor page).
   EditorJS's INTERNAL Paragraph has sanitize `{text:{br:true}}` which strips
   <b>, <i>, <u>, <mark>, color spans — clicking Bold appeared to work but
   `editor.save()` quietly dropped the tag. This one allows the inline tools
   to survive the save round-trip. */
window.ParagraphWithInlineTools = (function () {
    return class ParagraphWithInlineTools {
        static get isReadOnlySupported() { return true; }
        static get enableLineBreaks()    { return false; }
        static get conversionConfig()    { return { export: 'text', import: 'text' }; }
        static get sanitize() {
            /* `text: true` tells EditorJS Sanitizer "allow ALL tags + attributes
               in this field". Critical: a strict object rule like
               `{ text: { br: true, b: true, ... } }` was being IGNORED in v2.30.8
               — apparently the internal default Paragraph's sanitize config wins
               when both are registered under the name "paragraph". Setting it to
               `true` bypasses the merge entirely and survives Bold/Italic/Color
               span markup through editor.save(). */
            return { text: true };
        }
        static get pasteConfig() {
            return { tags: ['P'] };
        }

        constructor({ data, config, api, readOnly }) {
            this.api = api;
            this.readOnly = !!readOnly;
            this.config = config || {};
            this._placeholder = (this.config && this.config.placeholder) || '';
            this._element = null;
            this._data = { text: (data && typeof data.text === 'string') ? data.text : '' };
        }

        render() {
            const div = document.createElement('div');
            div.classList.add('ce-paragraph', 'cdx-block');
            div.contentEditable = this.readOnly ? 'false' : 'true';
            div.dataset.placeholder = this._placeholder;
            div.innerHTML = this._data.text;
            this._element = div;
            return div;
        }

        merge(data) {
            if (!this._element) return;
            this._data.text = (this._element.innerHTML || '') + (data?.text || '');
            this._element.innerHTML = this._data.text;
        }

        validate(saved) {
            if (saved.text.trim() === '' && !this.config?.preserveBlank) return false;
            return true;
        }

        save(toolEl) {
            return { text: toolEl.innerHTML };
        }

        onPaste(event) {
            const data = { text: event.detail?.data?.innerHTML || '' };
            this._data = data;
            if (this._element) this._element.innerHTML = data.text;
        }
    };
})();

/* ─── Tailwind Class Picker: shared modal for block class tune ─── */
window.TAILWIND_CLASS_CATALOG = window.TAILWIND_CLASS_CATALOG || {
    'Brand tokens': {
        'Brand colors':    ['text-brand','text-on-surface','text-variant-1','text-variant-2','text-white','bg-brand','bg-brand-soft','bg-surface','bg-white','bg-on-surface'],
        'Borders / rings': ['border-outline','ring-outline','ring-brand','ring-brand/30','ring-brand/20','divide-outline'],
        'Shadows':         ['shadow-card','shadow-soft','shadow-strong'],
        'Brand button':    ['rounded-full','bg-brand','text-white','px-6','py-2.5','font-semibold','hover:bg-brand-dark','transition-colors'],
        'Section pad':     ['py-20','lg:py-24','py-16','lg:py-20'],
        'Heading style':   ['text-3xl','md:text-4xl','lg:text-[44px]','font-extrabold','capitalize','leading-tight','tracking-[0.2em]','text-on-surface'],
    },
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

    /** Live preview — applies the selected classes to options.target (DOM element) so
     *  the user sees changes immediately as they pick. Restores original on dismiss. */
    const liveTarget = options.liveTarget || null;
    const _origClasses = liveTarget ? liveTarget.className : null;
    const applyLivePreview = () => {
        if (!liveTarget) return;
        try {
            // Strip previously applied user classes (track via dataset.twPickerLive)
            const prev = (liveTarget.dataset.twPickerLive || '').split(/\s+/).filter(Boolean);
            prev.forEach(c => liveTarget.classList.remove(c));
            const next = Array.from(selected);
            next.forEach(c => liveTarget.classList.add(c));
            liveTarget.dataset.twPickerLive = next.join(' ');
        } catch (_) {}
    };
    const restoreLivePreview = () => {
        if (liveTarget && _origClasses !== null) {
            liveTarget.className = _origClasses;
            delete liveTarget.dataset.twPickerLive;
        }
    };

    const overlay = document.createElement('div');
    overlay.className = 'tw-picker-overlay';
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    // Stop mousedown bubbling so the visual-editor's global "click outside the
    // edit panel → closePanel" listener doesn't close the editor underneath
    // while the user is interacting with the class picker (the picker mounts
    // to <body>, not into the edit panel).
    overlay.addEventListener('mousedown', (e) => e.stopPropagation());

    const modal = document.createElement('div');
    modal.className = 'tw-picker-modal';
    modal.addEventListener('click', (e) => e.stopPropagation());
    modal.addEventListener('mousedown', (e) => e.stopPropagation());
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
    clearBtn.addEventListener('click', () => { selected.clear(); renderBody(); updatePreview(); applyLivePreview(); });
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'tw-picker-btn tw-picker-btn-cancel';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', () => { restoreLivePreview(); close(); });
    const applyBtn = document.createElement('button');
    applyBtn.className = 'tw-picker-btn tw-picker-btn-apply';
    applyBtn.textContent = 'Apply';
    applyBtn.addEventListener('click', () => {
        const result = Array.from(selected).join(' ').trim();
        // Persist selection (don't restore preview — let it stand)
        if (liveTarget) delete liveTarget.dataset.twPickerLive;
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
        if (selected.has(cls)) {
            selected.delete(cls);
        } else {
            selected.add(cls);
            pushRecent(cls);
        }
        renderBody();
        updatePreview();
        applyLivePreview();
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

    /** Recent / Most-used classes — kept in localStorage, max 24 entries. */
    const _recentKey = 'tw-picker:recent';
    const getRecent = () => {
        try { return JSON.parse(localStorage.getItem(_recentKey) || '[]'); } catch (_) { return []; }
    };
    const pushRecent = (cls) => {
        try {
            let list = getRecent().filter(c => c !== cls);
            list.unshift(cls);
            list = list.slice(0, 24);
            localStorage.setItem(_recentKey, JSON.stringify(list));
        } catch (_) {}
    };

    const renderBody = () => {
        body.innerHTML = '';
        body.appendChild(renderSelectedRow());
        body.appendChild(renderCustomInput());

        // Recent classes section (only when not searching)
        if (!searchQuery) {
            const recent = getRecent();
            if (recent.length) {
                const g = document.createElement('div');
                g.className = 'tw-picker-group';
                const t = document.createElement('div');
                t.className = 'tw-picker-group-title';
                t.textContent = '⭐ Recent';
                g.appendChild(t);
                const wrap = document.createElement('div');
                wrap.className = 'tw-picker-classes';
                recent.forEach(cls => {
                    const pill = document.createElement('button');
                    pill.type = 'button';
                    pill.className = 'tw-picker-pill' + (selected.has(cls) ? ' active' : '');
                    pill.textContent = cls;
                    pill.addEventListener('click', () => toggleClass(cls));
                    wrap.appendChild(pill);
                });
                g.appendChild(wrap);
                body.appendChild(g);
            }
        }

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
        return window.safeBlockRender(this, this._renderInner, 'Space');
    }
    _renderInner() {
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

        const swallow = (e) => e.stopPropagation();
        wrap.addEventListener('mousedown', swallow);
        wrap.addEventListener('click', swallow);
        wrap.addEventListener('pointerdown', swallow);

        // Wrap as TunesMenuConfig html so EditorJS doesn't render it as a
        // clickable popover-item (which would auto-close the popover on
        // every preset click).
        return { type: 'html', element: wrap };
    }
    save() { return { height: this.data.height || '2rem' }; }
    static get sanitize() { return { height: false }; }
};

/**
 * Media library picker — opens a modal with thumbnails of existing media,
 * lets user search + pick. onPick({ url, name }) receives the chosen item.
 */
window.editorjsMediaPicker = function (options) {
    options = options || {};
    const onPick = typeof options.onPick === 'function' ? options.onPick : () => {};
    const url = options.url || window._editorjsField_mediaUrl;
    if (!url) {
        alert('Media library not available');
        return;
    }

    document.querySelectorAll('.ejs-media-overlay').forEach(el => el.remove());
    const overlay = document.createElement('div');
    overlay.className = 'ejs-media-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:10001;display:flex;align-items:center;justify-content:center;padding:24px';
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

    const modal = document.createElement('div');
    modal.style.cssText = 'background:#fff;border-radius:14px;width:100%;max-width:840px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif';
    overlay.appendChild(modal);

    const header = document.createElement('div');
    header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid #e5e7eb;background:linear-gradient(to bottom,#fafbfc,#f3f4f6)';
    const title = document.createElement('div');
    title.style.cssText = 'font-weight:700;font-size:14px;color:#111827';
    title.textContent = '🖼 Media Library';
    const search = document.createElement('input');
    search.type = 'text';
    search.placeholder = 'Search…';
    search.style.cssText = 'flex:1;max-width:280px;padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px';
    const close = document.createElement('button');
    close.type = 'button';
    close.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
    close.style.cssText = 'background:none;border:0;cursor:pointer;color:#6b7280;padding:4px;border-radius:4px';
    close.addEventListener('click', () => overlay.remove());
    header.appendChild(title);
    header.appendChild(search);
    header.appendChild(close);
    modal.appendChild(header);

    const grid = document.createElement('div');
    grid.style.cssText = 'flex:1;overflow-y:auto;padding:14px;display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;background:#f8fafc';
    modal.appendChild(grid);

    let searchTimer = null;
    const load = (query = '') => {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;font-size:13px">Loading…</div>';
        const u = url + (url.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(query);
        fetch(u, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject(r.status))
            .then(data => {
                grid.innerHTML = '';
                if (!data.items || !data.items.length) {
                    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;font-size:13px">No media found</div>';
                    return;
                }
                data.items.forEach(item => {
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.title = item.name;
                    card.style.cssText = 'position:relative;padding:0;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer;overflow:hidden;aspect-ratio:1;transition:all .15s ease';
                    card.addEventListener('mouseenter', () => { card.style.borderColor = '#1563df'; card.style.boxShadow = '0 0 0 3px rgba(21,99,223,.15)'; });
                    card.addEventListener('mouseleave', () => { card.style.borderColor = '#e5e7eb'; card.style.boxShadow = 'none'; });
                    const img = document.createElement('img');
                    img.src = item.thumb || item.url;
                    img.alt = item.name;
                    img.loading = 'lazy';
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
                    card.appendChild(img);
                    const label = document.createElement('div');
                    label.style.cssText = 'position:absolute;left:0;right:0;bottom:0;padding:4px 8px;background:linear-gradient(to top,rgba(0,0,0,.7),transparent);color:#fff;font-size:10px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;text-align:left';
                    label.textContent = item.name;
                    card.appendChild(label);
                    card.addEventListener('click', () => {
                        onPick({ url: item.url, name: item.name });
                        overlay.remove();
                    });
                    grid.appendChild(card);
                });
            })
            .catch(() => {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#dc2626;font-size:13px">Could not load media library</div>';
            });
    };

    search.addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => load(e.target.value), 250);
    });

    document.body.appendChild(overlay);
    setTimeout(() => search.focus(), 50);
    load();
};

/**
 * Block templates registry — save/insert reusable block sequences.
 * Stored in localStorage under 'ejs:templates' as { id, name, blocks[], createdAt }.
 */
window.editorjsTemplates = {
    KEY: 'ejs:templates',
    list() {
        try { return JSON.parse(localStorage.getItem(this.KEY) || '[]'); } catch (_) { return []; }
    },
    save(name, blocks) {
        if (!name || !Array.isArray(blocks) || !blocks.length) return null;
        const all = this.list();
        const tpl = {
            id: 't' + Date.now() + Math.random().toString(36).slice(2, 6),
            name: name.trim().slice(0, 60),
            blocks,
            createdAt: Date.now(),
        };
        all.unshift(tpl);
        try { localStorage.setItem(this.KEY, JSON.stringify(all.slice(0, 50))); } catch (_) {}
        return tpl;
    },
    delete(id) {
        const all = this.list().filter(t => t.id !== id);
        try { localStorage.setItem(this.KEY, JSON.stringify(all)); } catch (_) {}
    },
    /** Open a modal to insert/save/delete templates. editor = EditorJS instance. */
    openModal(editor) {
        if (!editor) return;
        document.querySelectorAll('.ejs-tpl-overlay').forEach(el => el.remove());

        const overlay = document.createElement('div');
        overlay.className = 'ejs-tpl-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:10001;display:flex;align-items:center;justify-content:center;padding:24px';
        overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });

        const modal = document.createElement('div');
        modal.style.cssText = 'background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif';
        overlay.appendChild(modal);

        const header = document.createElement('div');
        header.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e5e7eb;background:linear-gradient(to bottom,#fafbfc,#f3f4f6);font-weight:700;font-size:14px;color:#111827';
        header.innerHTML = '<span>📦 Block templates</span>';
        const close = document.createElement('button');
        close.type = 'button';
        close.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
        close.style.cssText = 'background:none;border:0;cursor:pointer;color:#6b7280;padding:4px;border-radius:4px';
        close.addEventListener('click', () => overlay.remove());
        header.appendChild(close);
        modal.appendChild(header);

        // Save current selection as new template
        const saveRow = document.createElement('div');
        saveRow.style.cssText = 'padding:12px 18px;border-bottom:1px solid #f3f4f6;display:flex;gap:8px;align-items:center;background:#f8fafc';
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.placeholder = 'Template name…';
        nameInput.style.cssText = 'flex:1;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px';
        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.textContent = 'Save current as template';
        saveBtn.style.cssText = 'padding:8px 14px;background:#1563df;color:#fff;border:0;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer';
        saveRow.appendChild(nameInput);
        saveRow.appendChild(saveBtn);
        modal.appendChild(saveRow);

        const list = document.createElement('div');
        list.style.cssText = 'flex:1;overflow-y:auto;padding:8px';
        modal.appendChild(list);

        const renderList = () => {
            const all = this.list();
            list.innerHTML = '';
            if (!all.length) {
                list.innerHTML = '<div style="padding:32px;text-align:center;color:#6b7280;font-size:13px">No templates yet. Save the current editor content as a template above.</div>';
                return;
            }
            all.forEach(tpl => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;margin-bottom:6px;border:1px solid #e5e7eb;border-radius:8px;background:#fff';
                const meta = document.createElement('div');
                meta.style.cssText = 'flex:1;min-width:0';
                const date = new Date(tpl.createdAt);
                meta.innerHTML = `<div style="font-weight:600;font-size:13px;color:#111827;text-overflow:ellipsis;overflow:hidden;white-space:nowrap">${tpl.name}</div><div style="font-size:11px;color:#6b7280">${tpl.blocks.length} block${tpl.blocks.length === 1 ? '' : 's'} · ${date.toLocaleDateString()}</div>`;
                const insertBtn = document.createElement('button');
                insertBtn.type = 'button';
                insertBtn.textContent = 'Insert';
                insertBtn.style.cssText = 'padding:6px 12px;background:#1563df;color:#fff;border:0;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer';
                insertBtn.addEventListener('click', () => {
                    try {
                        const insertAt = (editor.blocks.getBlocksCount?.() || 0);
                        tpl.blocks.forEach((b, i) => {
                            editor.blocks.insert(b.type, b.data || {}, b.config || {}, insertAt + i, false);
                        });
                        overlay.remove();
                    } catch (e) {
                        alert('Insert failed: ' + e.message);
                    }
                });
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.title = 'Delete template';
                delBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>';
                delBtn.style.cssText = 'padding:6px;background:transparent;color:#94a3b8;border:0;cursor:pointer;border-radius:4px';
                delBtn.addEventListener('mouseenter', () => { delBtn.style.background = '#fee2e2'; delBtn.style.color = '#dc2626'; });
                delBtn.addEventListener('mouseleave', () => { delBtn.style.background = 'transparent'; delBtn.style.color = '#94a3b8'; });
                delBtn.addEventListener('click', () => {
                    if (!confirm(`Delete template "${tpl.name}"?`)) return;
                    this.delete(tpl.id);
                    renderList();
                });
                row.appendChild(meta);
                row.appendChild(insertBtn);
                row.appendChild(delBtn);
                list.appendChild(row);
            });
        };

        saveBtn.addEventListener('click', async () => {
            const name = nameInput.value.trim();
            if (!name) { nameInput.focus(); return; }
            try {
                const data = await editor.save();
                if (!data || !Array.isArray(data.blocks) || !data.blocks.length) {
                    alert('Editor is empty — nothing to save');
                    return;
                }
                this.save(name, data.blocks);
                nameInput.value = '';
                renderList();
            } catch (e) {
                alert('Could not read editor content: ' + e.message);
            }
        });

        document.body.appendChild(overlay);
        renderList();
        setTimeout(() => nameInput.focus(), 50);
    },
};

/**
 * Block error boundary helper.
 * Wraps a tool render() in try/catch — if it throws (e.g. corrupted data, missing
 * dependency at edit time, internal CDN script failure), returns a visible
 * placeholder that lets the user keep editing other blocks instead of crashing
 * the whole editor.
 */
window.safeBlockRender = function (toolInstance, renderFn, label) {
    try {
        return renderFn.call(toolInstance);
    } catch (err) {
        console.warn('[' + (label || 'Block') + '] render failed:', err);
        const fallback = document.createElement('div');
        fallback.style.cssText = 'padding:1rem;border:2px dashed #f87171;border-radius:8px;background:#fef2f2;color:#991b1b;font-size:13px;line-height:1.5';
        fallback.innerHTML =
            '<div style="font-weight:600;margin-bottom:4px">⚠ ' + (label || 'Block') + ' failed to render</div>' +
            '<div style="font-size:11px;color:#7f1d1d;margin-bottom:8px">' + (err.message || 'Unknown error') + '</div>' +
            '<div style="font-size:11px">The block data is preserved on save. Delete this block and re-add it, or contact support.</div>';
        return fallback;
    }
};

/* ─── Reorder arrows: adds ↑/↓ buttons next to every block for one-click reordering ─── */
window.attachReorderArrows = function (holderEl, editor) {
    if (!holderEl || !editor || holderEl._reorderArrowsAttached) return;
    holderEl._reorderArrowsAttached = true;

    const ARROW_UP = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>';
    const ARROW_DOWN = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>';

    const decorate = () => {
        const blocks = holderEl.querySelectorAll(':scope > .codex-editor > .codex-editor__redactor > .ce-block, :scope .codex-editor__redactor > .ce-block');
        blocks.forEach((blockEl, idx) => {
            // Only the FIRST level codex-editor (avoid nested container/columns inner editors here —
            // they get their own attachReorderArrows call from their own onReady).
            const root = blockEl.closest('.codex-editor');
            if (root && root.parentElement !== holderEl && root.parentElement !== holderEl.querySelector(':scope > .codex-editor')) return;

            if (blockEl._reorderArrowsAdded) return;
            blockEl._reorderArrowsAdded = true;

            const bar = document.createElement('div');
            bar.className = 'ej-reorder-bar';
            // Position INSIDE the block at top-right so it doesn't bleed into adjacent columns/blocks
            bar.style.cssText = 'position:absolute;right:4px;top:-14px;display:none;flex-direction:row;gap:2px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.08);padding:2px;z-index:5';

            const mkBtn = (html, label, dir) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.title = label;
                b.innerHTML = html;
                b.style.cssText = 'display:flex;align-items:center;justify-content:center;width:22px;height:22px;border:0;background:transparent;color:#475569;border-radius:4px;cursor:pointer';
                b.addEventListener('mouseenter', () => { b.style.background = '#f1f5f9'; b.style.color = '#1e293b'; });
                b.addEventListener('mouseleave', () => { b.style.background = 'transparent'; b.style.color = '#475569'; });
                b.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
                b.addEventListener('click', async (e) => {
                    e.preventDefault(); e.stopPropagation();
                    try {
                        const all = Array.from(holderEl.querySelectorAll(':scope > .codex-editor > .codex-editor__redactor > .ce-block, :scope .codex-editor__redactor > .ce-block'))
                            .filter(el => {
                                const r = el.closest('.codex-editor');
                                return r && (r.parentElement === holderEl || r.parentElement === holderEl.querySelector(':scope > .codex-editor'));
                            });
                        const fromIndex = all.indexOf(blockEl);
                        if (fromIndex < 0) return;
                        const toIndex = dir === 'up' ? fromIndex - 1 : fromIndex + 1;
                        if (toIndex < 0 || toIndex >= all.length) return;
                        editor.blocks.move(toIndex, fromIndex);
                    } catch (err) {
                        console.warn('[reorder] move failed:', err);
                    }
                });
                return b;
            };

            bar.appendChild(mkBtn(ARROW_UP, 'Move up', 'up'));
            bar.appendChild(mkBtn(ARROW_DOWN, 'Move down', 'down'));

            // Position relative to the block
            if (getComputedStyle(blockEl).position === 'static') {
                blockEl.style.position = 'relative';
            }
            blockEl.appendChild(bar);

            blockEl.addEventListener('mouseenter', () => { bar.style.display = 'flex'; });
            blockEl.addEventListener('mouseleave', () => { bar.style.display = 'none'; });
        });
    };

    decorate();
    // EditorJS adds/removes blocks dynamically — re-decorate on DOM changes
    const obs = new MutationObserver(() => {
        clearTimeout(holderEl._reorderDebounce);
        holderEl._reorderDebounce = setTimeout(decorate, 80);
    });
    obs.observe(holderEl, { childList: true, subtree: true });
    holderEl._reorderObserver = obs;
};

/* ─── LiveHtml block: renders pasted HTML live (styled) and edits it IN PLACE.
   Text is contentEditable; clicking an <img> opens the media picker to swap it;
   a "</> Source" toggle exposes the raw HTML for class/structure tweaks.
   This is what makes a pasted Tailwind component behave like WYSIWYG. ─── */
window.LiveHtmlTool = class LiveHtmlTool {
    static get toolbox() {
        return { title: 'HTML (live)', icon: '<svg width="17" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>' };
    }
    static get sanitize() { return { html: true }; }
    static get enableLineBreaks() { return true; }

    constructor({ data, api }) {
        this.api = api;
        this.data = { html: (data && typeof data.html === 'string') ? data.html : '' };
        this.wrapper = null;
        this.content = null;
        this.textarea = null;
        this.sourceMode = false;
        this.styleMode = false;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'ce-livehtml';

        // We removed the inline Style/Source bar — it was rendering INSIDE
        // the block content area in fullscreen, overlapping with the actual
        // content and unreachable to click. The buttons are now hosted in
        // the fullscreen editor's header (see _edit-panel.blade.php). They
        // call window._activeLiveHtmlTool methods which we track via
        // focusin/click below.

        // Rendered, editable content
        this.content = document.createElement('div');
        this.content.className = 'ce-livehtml__content';
        this.content.contentEditable = 'true';
        this.content.innerHTML = this.data.html;
        // Track this instance as the "active" liveHtml tool whenever the
        // user interacts with it. The fullscreen header buttons use this
        // reference to apply Style/Source on the focused block.
        const markActive = () => { window._activeLiveHtmlTool = this; };
        this.content.addEventListener('focusin', markActive);
        this.content.addEventListener('mousedown', markActive);
        // Keep edits in sync
        this.content.addEventListener('input', () => {
            clearTimeout(this._t);
            this._t = setTimeout(() => { this.data.html = this.content.innerHTML; }, 250);
        });
        // Don't let EditorJS hijack keys (Enter splitting the block etc.) — keep
        // all editing local to this block.
        this.content.addEventListener('keydown', (e) => { e.stopPropagation(); });
        // Click behaviour:
        //   • Style mode ON  → clicking ANY element opens its class editor.
        //   • Style mode OFF → clicking an image opens the media picker; text edits inline.
        //
        // Many AI-generated hero patterns wrap the image in a relative container
        // and stack `absolute inset-0` gradient overlays ON TOP of it. Clicks
        // then land on the overlay, not the image. We fall back to finding an
        // `<img>` adjacent to the clicked element so the user can still swap
        // the photo intuitively.
        this.content.addEventListener('click', (e) => {
            if (this.styleMode) {
                const el = e.target;
                if (el && el !== this.content) { e.preventDefault(); e.stopPropagation(); this.openStylePopup(el); }
                return;
            }
            const img = this._findImageNear(e.target);
            if (img) { e.preventDefault(); e.stopPropagation(); this.replaceImage(img); }
        });
        this.wrapper.appendChild(this.content);

        return this.wrapper;
    }

    toggleStyleMode() {
        this.styleMode = !this.styleMode;
        this.styleBtn.classList.toggle('ce-livehtml__btn--active', this.styleMode);
        this.wrapper.classList.toggle('ce-livehtml--style', this.styleMode);
        // In style mode disable text editing so clicks select elements instead.
        this.content.contentEditable = this.styleMode ? 'false' : 'true';
        if (!this.styleMode) { this._closeStylePopup(); }
    }

    _closeStylePopup() {
        // Remove the outside-click listener FIRST — otherwise stale listeners from
        // previous popups accumulate and close the next popup when you click inside it.
        if (this._styleOnDoc) { document.removeEventListener('mousedown', this._styleOnDoc, true); this._styleOnDoc = null; }
        // Persist the final class edits once, on close (we edit silently while open
        // to avoid re-renders that would detach the element being styled).
        if (this._styleDirty) {
            this._styleDirty = false;
            this.data.html = this.content.innerHTML;
            try { this.content.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
        }
        if (this._stylePop) { this._stylePop.remove(); this._stylePop = null; }
        if (this._styleSel) { try { this._styleSel.style.outline = ''; this._styleSel.style.outlineOffset = ''; } catch (e) {} this._styleSel = null; }
    }

    /** Floating class editor for a single element inside the live HTML.
     *  Uses INLINE styles (not a CSS class) so it always renders correctly even
     *  if the stylesheet isn't loaded in this context. */
    openStylePopup(el) {
        this._closeStylePopup();
        this._styleSel = el;
        el.style.outline = '2px solid #6366f1';
        el.style.outlineOffset = '1px';

        const pop = document.createElement('div');
        this._stylePop = pop;
        pop.className = 'ce-livehtml-style-pop';
        pop.style.cssText = 'position:fixed;z-index:100001;width:300px;background:#fff;border:1px solid #c7d2fe;border-radius:10px;box-shadow:0 12px 40px rgba(15,23,42,.28);font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;overflow:hidden';
        const tag = el.tagName.toLowerCase();

        const head = document.createElement('div');
        head.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f5f7ff;border-bottom:1px solid #e0e7ff;font-size:12px;color:#334155';
        head.innerHTML = '<span style="font-family:ui-monospace,monospace;color:#4f46e5;font-weight:700">&lt;' + tag + '&gt;</span> <span style="margin-right:auto;margin-left:6px;color:#64748b">classes</span>';
        const x = document.createElement('button'); x.type = 'button'; x.textContent = '✕';
        x.style.cssText = 'border:0;background:transparent;color:#94a3b8;cursor:pointer;font-size:13px;padding:2px 6px;border-radius:4px';
        x.addEventListener('click', () => this._closeStylePopup());
        head.appendChild(x);
        pop.appendChild(head);

        const input = document.createElement('textarea');
        input.value = el.getAttribute('class') || '';
        input.spellcheck = false;
        input.placeholder = 'π.χ. text-red-500 text-2xl font-bold';
        input.style.cssText = 'display:block;width:100%;box-sizing:border-box;min-height:90px;resize:vertical;border:0;outline:none;padding:10px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;line-height:1.5;color:#0f172a';
        input.addEventListener('input', () => {
            const v = input.value.trim();
            if (v) { el.setAttribute('class', v); } else { el.removeAttribute('class'); }
            // Update data silently (no dispatchEvent → no Livewire re-render that
            // would detach `el`). Final sync happens once in _closeStylePopup.
            this.data.html = this.content.innerHTML;
            this._styleDirty = true;
        });
        input.addEventListener('keydown', (e) => e.stopPropagation());
        pop.appendChild(input);

        // Image-specific: quick "Change image" button
        if (tag === 'img') {
            const ib = document.createElement('button'); ib.type = 'button'; ib.textContent = '🖼 Change image';
            ib.style.cssText = 'display:block;width:100%;box-sizing:border-box;border:0;border-top:1px solid #e0e7ff;background:#eef2ff;color:#4f46e5;font-size:12px;font-weight:600;padding:8px;cursor:pointer';
            ib.addEventListener('click', () => { this.replaceImage(el); });
            pop.appendChild(ib);
        }

        document.body.appendChild(pop);
        // Position near the element, clamped to viewport
        const r = el.getBoundingClientRect();
        const pw = 300, ph = pop.offsetHeight || 150;
        let top = r.bottom + 8, left = r.left;
        if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
        if (left < 8) left = 8;
        if (top + ph > window.innerHeight - 8) top = Math.max(8, r.top - ph - 8);
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
        setTimeout(() => input.focus(), 30);

        // Close when clicking outside the popup (but not on the selected element).
        // Stored on the instance so _closeStylePopup can remove it (prevents stale
        // listeners that would close the next popup when you click inside it).
        const onDoc = (ev) => {
            if (pop.contains(ev.target)) return;                                // click inside popup
            if (this.styleMode && this.content.contains(ev.target)) return;     // selecting another element
            this._closeStylePopup();
        };
        this._styleOnDoc = onDoc;
        setTimeout(() => document.addEventListener('mousedown', onDoc, true), 0);
    }

    /**
     * Edit the raw HTML in a WIDE centered modal (the inline editor panel is a
     * narrow sidebar, so an inline textarea was unusable). Opens full-width,
     * applies on "Εφαρμογή".
     */
    openSourceModal() {
        this.data.html = this.content.innerHTML; // start from latest rendered
        const overlay = document.createElement('div');
        overlay.className = 'ce-livehtml-source-modal';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:100000;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;padding:24px';

        const modal = document.createElement('div');
        modal.style.cssText = 'background:#0f172a;border-radius:12px;width:100%;max-width:1100px;height:82vh;display:flex;flex-direction:column;box-shadow:0 24px 70px rgba(0,0,0,.45);overflow:hidden';

        const hdr = document.createElement('div');
        hdr.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #1e293b;color:#e2e8f0;font:600 13px ui-sans-serif,system-ui,-apple-system,sans-serif';
        const title = document.createElement('span'); title.textContent = '</> HTML Source';
        const btns = document.createElement('div'); btns.style.cssText = 'display:flex;gap:8px';
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Άκυρο';
        cancel.style.cssText = 'padding:6px 14px;border-radius:6px;border:1px solid #334155;background:transparent;color:#cbd5e1;cursor:pointer;font-size:13px';
        const apply = document.createElement('button'); apply.type = 'button'; apply.textContent = 'Εφαρμογή';
        apply.style.cssText = 'padding:6px 16px;border-radius:6px;border:0;background:#4f46e5;color:#fff;cursor:pointer;font-size:13px;font-weight:600';
        btns.appendChild(cancel); btns.appendChild(apply);
        hdr.appendChild(title); hdr.appendChild(btns);
        modal.appendChild(hdr);

        const ta = document.createElement('textarea');
        ta.value = this.data.html;
        ta.spellcheck = false;
        ta.style.cssText = 'flex:1;width:100%;box-sizing:border-box;resize:none;border:0;outline:none;padding:16px;background:#0f172a;color:#e2e8f0;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;line-height:1.6;white-space:pre-wrap;word-break:break-word;overflow:auto;tab-size:2';
        ta.addEventListener('keydown', (e) => e.stopPropagation());
        modal.appendChild(ta);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        setTimeout(() => ta.focus(), 50);

        const close = () => overlay.remove();
        cancel.addEventListener('click', close);
        overlay.addEventListener('mousedown', (e) => { if (e.target === overlay) close(); });
        apply.addEventListener('click', () => {
            this.content.innerHTML = ta.value;
            this.data.html = ta.value;
            try { this.content.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
            close();
        });
    }

    /**
     * Locate the <img> the user "meant" to click. We're now strict — ONLY
     * fires when the target itself IS or directly contains an <img>. The
     * earlier "walk up looking for any sibling img" heuristic was hijacking
     * every click in pages that had a logo somewhere upstream (e.g. a nav).
     *
     * If users need to swap photos hidden under absolute overlays, the
     * Source mode lets them edit the URL directly.
     */
    _findImageNear(target) {
        if (!target || !target.closest) return null;
        return target.closest('img');
    }

    replaceImage(img) {
        const apply = (url) => {
            if (!url) return;
            img.setAttribute('src', url);
            this.data.html = this.content.innerHTML;
            // Trigger the editor's onChange (like typing) so the swap auto-saves.
            // Programmatic src changes don't fire 'input', so dispatch it manually.
            try { this.content.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
        };
        const mediaUrl = window._editorjsField_mediaUrl;
        if (typeof window.editorjsMediaPicker === 'function' && mediaUrl) {
            window.editorjsMediaPicker({ url: mediaUrl, onPick: ({ url }) => apply(url) });
        } else {
            const url = prompt('Νέα διεύθυνση εικόνας (URL):', img.getAttribute('src') || '');
            if (url !== null) { apply(url); }
        }
    }

    save() {
        return { html: this.content ? this.content.innerHTML : this.data.html };
    }
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

    /**
     * Popover-based settings for the Container. Returns a bare HTMLElement —
     * EditorJS v2.30.8 auto-wraps this as a `type:'html'` popover item (inert,
     * no auto-close on click). Uses <div> instead of <label> to dodge any
     * popover CSS that might hide label elements.
     */
    renderSettings() {
        const wrapper = document.createElement('div');
        wrapper.setAttribute('data-ctr-settings', '');
        wrapper.style.cssText = 'padding:8px;display:flex;flex-direction:column;gap:10px;width:100%;min-width:0;max-width:100%;box-sizing:border-box';

        const makeSelect = (label, key) => {
            const lab = document.createElement('div');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            lab.textContent = label;
            const sel = document.createElement('select');
            sel.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;background:#fff';
            Object.entries(ContainerTool.WIDTHS).forEach(([k, v]) => {
                const opt = document.createElement('option');
                opt.value = k; opt.textContent = v.label;
                if (this.data[key] === k) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.addEventListener('change', (e) => {
                this.data[key] = e.target.value;
                this.updateLabel();
                this.applyVisualWidth();
            });
            const wrap = document.createElement('div');
            wrap.appendChild(lab); wrap.appendChild(sel);
            return wrap;
        };

        wrapper.appendChild(makeSelect('Mobile', 'mobile'));
        wrapper.appendChild(makeSelect('Tablet', 'tablet'));
        wrapper.appendChild(makeSelect('Desktop', 'desktop'));

        const makeInput = (label, key, placeholder) => {
            const lab = document.createElement('div');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:2px';
            lab.textContent = label;
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;gap:4px';
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.placeholder = placeholder;
            inp.value = this.data[key] || '';
            inp.style.cssText = 'flex:1;min-width:0;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:ui-monospace,monospace;background:#fff';
            inp.addEventListener('input', (e) => {
                this.data[key] = e.target.value;
                this.applyLiveClasses();
            });
            const pick = document.createElement('button');
            pick.type = 'button';
            pick.textContent = '.tw';
            pick.title = 'Open Tailwind class picker';
            pick.style.cssText = 'flex-shrink:0;padding:6px 10px;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;font-size:11px;font-family:ui-monospace,monospace;font-weight:700;cursor:pointer;color:#4f46e5';
            pick.addEventListener('click', (e) => {
                e.stopPropagation();
                if (typeof window.openTailwindClassPicker === 'function') {
                    window.openTailwindClassPicker({
                        current: this.data[key] || '',
                        title: label,
                        onApply: (classes) => {
                            this.data[key] = (classes || '').trim();
                            inp.value = this.data[key];
                            this.applyLiveClasses();
                        },
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

    /**
     * Build the inline toolbar that lives at the top of the Container block.
     * Collapsible — starts collapsed showing just the current state summary;
     * click to expand the full Mobile/Tablet/Desktop + Wrapper/Inner editor.
     */
    _buildInlineToolbar() {
        const bar = document.createElement('div');
        bar.setAttribute('data-ctr-toolbar', '');
        bar.style.cssText = 'border:1px solid #e0e7ff;border-radius:6px;background:#f5f7ff;margin:-2px 0 8px;box-sizing:border-box;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif';

        // Header: clickable summary line that toggles expansion.
        const head = document.createElement('div');
        head.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;user-select:none';

        const chevron = document.createElement('span');
        chevron.style.cssText = 'font-size:11px;color:#4f46e5;font-weight:700;width:10px;display:inline-block;transition:transform .12s ease';
        chevron.textContent = '▸';

        const title = document.createElement('span');
        title.style.cssText = 'font-size:11px;color:#4338ca;font-weight:700;text-transform:uppercase;letter-spacing:0.06em';
        title.textContent = 'Container settings';

        const summary = document.createElement('span');
        summary.style.cssText = 'font-size:11px;color:#6b7280;margin-left:auto;font-family:ui-monospace,monospace';
        const renderSummary = () => {
            const wc = (this.data.wrapperClass || '').trim();
            const ic = (this.data.innerClass || '').trim();
            const parts = [
                `M:${this.data.mobile}  T:${this.data.tablet}  D:${this.data.desktop}`,
            ];
            if (wc) parts.push('wrap:' + wc.slice(0, 30) + (wc.length > 30 ? '…' : ''));
            if (ic) parts.push('inner:' + ic.slice(0, 30) + (ic.length > 30 ? '…' : ''));
            summary.textContent = parts.join(' · ');
        };
        renderSummary();

        head.appendChild(chevron);
        head.appendChild(title);
        head.appendChild(summary);
        bar.appendChild(head);

        // Body: full controls. Hidden until user expands.
        const body = document.createElement('div');
        body.style.cssText = 'display:none;flex-direction:column;gap:8px;padding:0 10px 10px;border-top:1px solid #e0e7ff';

        const makeSelectRow = (label, key) => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12px';
            const lab = document.createElement('label');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;flex-shrink:0;width:62px';
            lab.textContent = label;
            const sel = document.createElement('select');
            sel.style.cssText = 'flex:1;padding:5px 8px;border:1px solid #c7d2fe;border-radius:4px;font-size:12px;background:#fff;min-width:0';
            Object.entries(ContainerTool.WIDTHS).forEach(([k, v]) => {
                const opt = document.createElement('option');
                opt.value = k; opt.textContent = v.label;
                if (this.data[key] === k) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.addEventListener('change', (e) => {
                this.data[key] = e.target.value;
                this.updateLabel();
                this.applyVisualWidth();
                renderSummary();
            });
            row.appendChild(lab);
            row.appendChild(sel);
            return row;
        };

        const widthRow = document.createElement('div');
        widthRow.style.cssText = 'display:flex;flex-direction:column;gap:4px';
        widthRow.appendChild(makeSelectRow('Mobile', 'mobile'));
        widthRow.appendChild(makeSelectRow('Tablet', 'tablet'));
        widthRow.appendChild(makeSelectRow('Desktop', 'desktop'));
        body.appendChild(widthRow);

        const makeInputRow = (label, key, placeholder) => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:6px;font-size:12px';
            const lab = document.createElement('label');
            lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;flex-shrink:0;width:62px';
            lab.textContent = label;
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.placeholder = placeholder;
            inp.value = this.data[key] || '';
            inp.style.cssText = 'flex:1;padding:5px 8px;border:1px solid #c7d2fe;border-radius:4px;font-size:12px;font-family:ui-monospace,monospace;min-width:0;background:#fff';
            inp.addEventListener('input', (e) => {
                this.data[key] = e.target.value;
                this.applyLiveClasses();
                renderSummary();
            });
            const pick = document.createElement('button');
            pick.type = 'button';
            pick.textContent = '.tw';
            pick.title = 'Open Tailwind class picker';
            pick.style.cssText = 'flex-shrink:0;padding:5px 10px;border:1px solid #c7d2fe;border-radius:4px;background:#eef2ff;font-size:11px;font-family:ui-monospace,monospace;font-weight:700;cursor:pointer;color:#4f46e5';
            pick.addEventListener('click', (e) => {
                e.stopPropagation();
                if (typeof window.openTailwindClassPicker === 'function') {
                    window.openTailwindClassPicker({
                        current: this.data[key] || '',
                        title: label + ' classes',
                        onApply: (classes) => {
                            this.data[key] = (classes || '').trim();
                            inp.value = this.data[key];
                            this.applyLiveClasses();
                            renderSummary();
                        },
                    });
                }
            });
            row.appendChild(lab);
            row.appendChild(inp);
            row.appendChild(pick);
            return row;
        };

        body.appendChild(makeInputRow('Wrapper', 'wrapperClass', 'py-12 bg-slate-50'));
        body.appendChild(makeInputRow('Inner', 'innerClass', 'mx-auto px-4 sm:px-6 lg:px-8'));

        bar.appendChild(body);

        // Toggle expand/collapse on header click.
        let open = false;
        head.addEventListener('click', (e) => {
            // Don't toggle if the click landed on the summary text — let the
            // user click through to inputs without inadvertently collapsing.
            if (e.target === summary) return;
            open = !open;
            body.style.display = open ? 'flex' : 'none';
            chevron.style.transform = open ? 'rotate(90deg)' : 'rotate(0deg)';
        });

        // Stop bubble inside the toolbar so EditorJS doesn't pick up its own
        // block-selection / outside-click handlers when the user clicks an
        // input or select. (This is INSIDE the editor's block, so EditorJS's
        // block-content click handlers would otherwise see typing as
        // "clicked the block content".)
        const swallow = (e) => e.stopPropagation();
        bar.addEventListener('mousedown', swallow);
        bar.addEventListener('click', swallow);
        bar.addEventListener('pointerdown', swallow);
        // Keep keystrokes (like Enter in inputs) from triggering EditorJS
        // block-level keydown handlers (Enter would create a new paragraph).
        bar.addEventListener('keydown', (e) => e.stopPropagation());

        this._toolbarSummary = renderSummary;
        return bar;
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
        return window.safeBlockRender(this, this._renderInner, 'Container');
    }

    _deleteSelf() {
        try {
            if (!window.confirm('Delete this container and everything in it?')) {
                return;
            }
            const blockEl = this.wrap && this.wrap.closest ? this.wrap.closest('.ce-block') : null;
            const redactor = blockEl ? blockEl.parentElement : null;
            if (!blockEl || !redactor || !this.api || !this.api.blocks) {
                return;
            }
            const blocks = Array.from(redactor.querySelectorAll(':scope > .ce-block'));
            const idx = blocks.indexOf(blockEl);
            if (idx >= 0) {
                this.api.blocks.delete(idx);
            }
        } catch (e) {
            console.warn('[Container] delete failed:', e);
        }
    }

    _renderInner() {
        this.wrap = document.createElement('div');
        this.wrap.className = ('ctr-tool-wrap ' + (this.data.wrapperClass || '')).trim();
        this.wrap.style.cssText = 'position:relative;padding:18px 10px 10px;border:1px dashed #d1d5db;border-radius:8px;background:transparent;box-sizing:border-box;transition:max-width .18s ease';

        this.labelEl = document.createElement('div');
        this.labelEl.style.cssText = 'position:absolute;top:-9px;left:10px;background:#fff;padding:0 6px;font-size:10px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;border-radius:2px';
        this.updateLabel();
        this.wrap.appendChild(this.labelEl);

        // Explicit Delete button. The native EditorJS "Click to tune" popover is
        // unreliable for nested blocks (it can open the toolbox instead of the
        // tunes, leaving no Delete option), so we give the Container its own
        // always-available delete control in the header.
        this.deleteBtn = document.createElement('button');
        this.deleteBtn.type = 'button';
        this.deleteBtn.setAttribute('contenteditable', 'false');
        this.deleteBtn.textContent = '✕ Delete';
        this.deleteBtn.title = 'Delete this container';
        this.deleteBtn.style.cssText = 'position:absolute;top:-11px;right:8px;background:#fff;padding:1px 8px;font-size:10px;font-weight:600;color:#dc2626;border:1px solid #fecaca;border-radius:4px;cursor:pointer;z-index:4;line-height:1.7';
        this.deleteBtn.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
        this.deleteBtn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); this._deleteSelf(); });
        this.wrap.appendChild(this.deleteBtn);

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
            // Defensive: every tool checked via `window.X || X || null` so a failed CDN
            // load never throws ReferenceError when a Container is added much later.
            const _Header     = window.Header     || (typeof Header     !== 'undefined' ? Header     : null);
            const _NestedList = window.NestedList || (typeof NestedList !== 'undefined' ? NestedList : null);
            const _Quote      = window.Quote      || (typeof Quote      !== 'undefined' ? Quote      : null);
            const _Marker     = window.Marker     || (typeof Marker     !== 'undefined' ? Marker     : null);
            const _InlineCode = window.InlineCode || (typeof InlineCode !== 'undefined' ? InlineCode : null);
            const _Underline  = window.Underline  || (typeof Underline  !== 'undefined' ? Underline  : null);

            const subTools = {
                ...(window.ParagraphWithInlineTools ? { paragraph: { class: window.ParagraphWithInlineTools, inlineToolbar: true } } : {}),
                ...(window.HeaderWithInlineTools || _Header ? { header: { class: (window.HeaderWithInlineTools || _Header), inlineToolbar: true, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } } } : {}),
                ...(_NestedList ? { list: { class: _NestedList, inlineToolbar: true } } : {}),
                ...(_Quote ? { quote: { class: _Quote, inlineToolbar: true } } : {}),
                ...(_Marker ? { marker: _Marker } : {}),
                ...(_InlineCode ? { inlineCode: _InlineCode } : {}),
                ...(_Underline ? { underline: _Underline } : {}),
                // Match the outer editor's block set so pasted HTML parsed into a
                // container (raw / code / delimiter / table / nested container)
                // renders editable inside the container too.
                ...(typeof CodeTool   !== 'undefined' ? { code: CodeTool } : {}),
                ...(typeof Delimiter  !== 'undefined' ? { delimiter: Delimiter } : {}),
                ...(typeof RawTool    !== 'undefined' ? { raw: RawTool } : {}),
                ...(typeof Table      !== 'undefined' ? { table: { class: Table, inlineToolbar: true } } : {}),
                ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
                ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),
                ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
                ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),
                ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),
                ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),
                ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
                ...(window.LiveHtmlTool ? { liveHtml: { class: window.LiveHtmlTool } } : {}),
                ...(window.SpaceTool   ? { space:   { class: window.SpaceTool   } } : {}),
                // Match outer editor: register LinkTool inside the container too if loaded
                ...(typeof LinkTool !== 'undefined' ? { linkTool: { class: LinkTool, config: { endpoint: (window._editorjsField_fetchUrl || '') } } } : {}),
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
                // Top-level inline toolbar enables color/alignment/marker on every block.
                // Listing tool names here makes EditorJS show them in the popover for ALL block tools.
                inlineToolbar: ['bold', 'italic', 'underline', 'marker', 'inlineCode',
                    ...(window.ColorTool ? ['color'] : []),
                    ...(window.InlineAlignmentTool ? ['inlineAlignment'] : []),
                    'link'],
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
                    // Drag & drop reorder inside the container (same as outer editor)
                    try {
                        if (window.DragDrop) {
                            this._dragDrop = new window.DragDrop(this.subEditor);
                        }
                    } catch (e) {
                        console.warn('[Container] DragDrop init failed (non-fatal):', e);
                    }
                    // One-click ↑/↓ reorder arrows on each block
                    setTimeout(() => {
                        if (typeof window.attachReorderArrows === 'function') {
                            window.attachReorderArrows(holder, this.subEditor);
                        }
                    }, 100);
                    // Smart paste inside the container: same "raw HTML → ONE
                    // liveHtml block" behavior as the outer editor, so pasting
                    // a Tailwind component into a Container stays styled (and
                    // editable in place) instead of decomposing into one
                    // paragraph per line.
                    const sub = this.subEditor;
                    if (holder && !holder._vePasteHooked) {
                        holder._vePasteHooked = true;
                        holder.addEventListener('paste', async (ev) => {
                            try {
                                // Only this container — ignore pastes that bubbled
                                // from a deeper nested editor (rare but possible).
                                const closest = ev.target && ev.target.closest
                                    ? ev.target.closest('.codex-editor')
                                    : null;
                                const myEditor = holder.querySelector(':scope > .codex-editor')
                                    || holder.querySelector('.codex-editor');
                                if (closest && myEditor && closest !== myEditor) {
                                    return; // a deeper nested editor will handle it
                                }
                                const cd = ev.clipboardData || window.clipboardData;
                                if (!cd) return;
                                const plain = cd.getData('text/plain') || '';
                                if (typeof window._veLooksLikeHtml !== 'function' || !window._veLooksLikeHtml(plain)) return;
                                ev.preventDefault();
                                ev.stopPropagation();
                                // Styled markup → one liveHtml block (renders the
                                // design intact, editable in place). Plain markup
                                // → parsed into clean native blocks.
                                let parsed;
                                if (window.LiveHtmlTool && /\b(class|style)\s*=/.test(plain)) {
                                    parsed = { blocks: [{ type: 'liveHtml', data: { html: plain.trim() } }] };
                                } else {
                                    parsed = (typeof window._veHtmlToBlocks === 'function')
                                        ? window._veHtmlToBlocks(plain.trim())
                                        : null;
                                }
                                if (!parsed || !parsed.blocks || !parsed.blocks.length) return;
                                let idx = 0;
                                try {
                                    idx = sub.blocks.getCurrentBlockIndex();
                                    if (typeof idx !== 'number' || idx < 0) {
                                        idx = sub.blocks.getBlocksCount();
                                    }
                                } catch (_) { idx = 0; }
                                // If the current block is empty, replace it with the first paste block.
                                let replace = false;
                                try {
                                    const cur = sub.blocks.getBlockByIndex(idx);
                                    const data = await Promise.race([
                                        Promise.resolve(cur?.save?.()),
                                        new Promise(r => setTimeout(() => r(null), 400)),
                                    ]);
                                    const empty = !data || !data.data || (
                                        (typeof data.data.text === 'string' && data.data.text.trim() === '')
                                        && !data.data.items?.length && !data.data.code
                                    );
                                    if (empty) replace = true;
                                } catch (_) { /* keep replace=false */ }
                                for (let i = 0; i < parsed.blocks.length; i++) {
                                    const b = parsed.blocks[i];
                                    const at = idx + (replace ? 0 : 1) + i;
                                    try { sub.blocks.insert(b.type, b.data, {}, at, replace && i === 0); }
                                    catch (e) { console.warn('[Container paste] insert failed for', b.type, e); }
                                }
                            } catch (e) {
                                console.warn('[Container paste] interceptor failed (non-fatal):', e);
                            }
                        }, true);
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

    /**
     * Build the inline-input wrapper used by render(). Cached on `this` so
     * openEditor()'s sync callback can update the input value later.
     */
    _buildWrap() {
        const wrap = document.createElement('div');
        wrap.setAttribute('data-btc-settings', '');
        wrap.style.cssText = 'padding:6px 8px;display:flex;flex-direction:column;gap:4px;width:100%;min-width:0;max-width:100%;box-sizing:border-box';

        const lab = document.createElement('label');
        lab.style.cssText = 'font-size:11px;font-weight:600;color:#374151;display:flex;align-items:center;gap:6px;margin:0';
        lab.innerHTML = '<span style="font-family:monospace;font-weight:700;color:#4f46e5">.tw</span><span>Classes</span>';

        const row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:4px';

        const inp = document.createElement('input');
        inp.type = 'text';
        inp.placeholder = 'e.g. text-2xl font-bold text-blue-700';
        inp.value = this.data.classes || '';
        inp.style.cssText = 'flex:1;min-width:0;padding:6px 8px;border:1px solid #e5e7eb;border-radius:4px;font-size:12px;font-family:monospace';
        // EditorJS attaches click handlers on popover items that close the
        // popover. Stop propagation in CAPTURE phase so ancestors never see
        // these events — input/button still receive their own events because
        // they're the target.
        const swallow = (e) => e.stopPropagation();
        inp.addEventListener('mousedown', swallow);
        inp.addEventListener('click', swallow);
        inp.addEventListener('pointerdown', swallow);
        inp.addEventListener('input', (e) => {
            this.data.classes = e.target.value;
            this.applyToBlock();
        });

        const pick = document.createElement('button');
        pick.type = 'button';
        pick.textContent = '.tw';
        pick.title = 'Open Tailwind class picker';
        pick.style.cssText = 'padding:6px 10px;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;font-size:11px;font-family:monospace;font-weight:700;cursor:pointer;color:#4f46e5;flex-shrink:0';
        pick.addEventListener('mousedown', swallow);
        pick.addEventListener('pointerdown', swallow);
        pick.addEventListener('click', (e) => {
            e.stopPropagation();
            this.openEditor((classes) => { inp.value = classes; });
        });

        row.appendChild(inp);
        row.appendChild(pick);
        wrap.appendChild(lab);
        wrap.appendChild(row);

        // Swallow at the wrapper too — belt + braces against any ancestor
        // listener that fires before reaching the input/button.
        wrap.addEventListener('mousedown', swallow);
        wrap.addEventListener('click', swallow);
        wrap.addEventListener('pointerdown', swallow);

        this._classInput = inp;
        return wrap;
    }

    /**
     * Return a TunesMenuConfig with `type: 'html'`. Critical: when render()
     * returns a bare HTMLElement, EditorJS wraps it as a default popover-item
     * (clickable, auto-closes the popover on click). The 'html' type tells
     * EditorJS to render the element inert — no auto-close, no click handler.
     */
    render() {
        return {
            type: 'html',
            element: this._buildWrap(),
        };
    }

    openEditor(syncCb) {
        const current = this.data.classes || '';
        // Find the primary content element so the picker can show LIVE preview
        let liveTarget = null;
        try {
            const blockEl = (this.block && this.block.holder) || null;
            if (blockEl) {
                liveTarget = blockEl.querySelector(
                    '.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, img, pre, figure'
                );
            }
        } catch (_) {}

        if (typeof window.openTailwindClassPicker === 'function') {
            window.openTailwindClassPicker({
                current,
                liveTarget,
                title: 'Tailwind / CSS classes for this block',
                onApply: (classes) => {
                    const trimmed = (classes || '').trim();
                    this.data.classes = trimmed;
                    this.applyToBlock();
                    if (typeof syncCb === 'function') {
                        syncCb(trimmed);
                    } else if (this._classInput) {
                        this._classInput.value = trimmed;
                    }
                },
            });
        } else {
            const input = prompt('Tailwind / CSS classes for this block:\n(applied to the block element itself — h1, p, img, etc.)', current);
            if (input === null) return;
            this.data.classes = input.trim();
            this.applyToBlock();
            if (typeof syncCb === 'function') {
                syncCb(this.data.classes);
            } else if (this._classInput) {
                this._classInput.value = this.data.classes;
            }
        }
    }

    applyToBlock() {
        try {
            // Always prefer this.block.holder — it's the actual DOM node owned by THIS
            // block. Falling back to a document-wide querySelectorAll('.ce-block')[idx]
            // is unsafe when multiple editors (outer + Container sub-editor + Columns)
            // exist on the same page — indices don't align.
            let blockEl = (this.block && this.block.holder) || null;

            // Last-resort fallback: scope the lookup to the editor that owns this tune.
            if (!blockEl) {
                const blockIndex = this.api.blocks.getCurrentBlockIndex?.() ?? -1;
                const editorRoot = this.api?.ui?.nodes?.wrapper || this.api?.ui?.nodes?.holder;
                if (blockIndex >= 0 && editorRoot) {
                    const nodes = editorRoot.querySelectorAll(':scope > .codex-editor__redactor > .ce-block');
                    blockEl = nodes[blockIndex] || null;
                }
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
/**
 * Walk an EditorJS save() output and restore the raw innerHTML for every
 * paragraph / header block from the corresponding contenteditable DOM node.
 * EditorJS's Sanitizer strips <b>/<i>/<u>/<mark>/<span style> from these
 * fields on save() — even when the tool's static get sanitize() explicitly
 * allows them, because the internal default Paragraph sanitize config seems
 * to win. Reading directly from the DOM bypasses the sanitizer entirely.
 * Recurses into container.data.content.blocks for the nested sub-editor.
 */
window.rescueInlineFormatting = window.rescueInlineFormatting || function(outputData, containerEl) {
    if (!outputData || !Array.isArray(outputData.blocks) || !containerEl) return outputData;
    const blockSelector = (id) => `.ce-block[data-id="${CSS.escape(id)}"]`;
    const walk = (blocks, scope) => {
        blocks.forEach((block) => {
            if (!block || !block.id) return;
            const blockEl = scope.querySelector(blockSelector(block.id));
            if (block.type === 'paragraph' && blockEl) {
                const p = blockEl.querySelector('.ce-paragraph');
                if (p && typeof block.data === 'object') {
                    block.data.text = p.innerHTML;
                }
            } else if (block.type === 'header' && blockEl) {
                const h = blockEl.querySelector('.ce-header, h1, h2, h3, h4, h5, h6');
                if (h && typeof block.data === 'object') {
                    block.data.text = h.innerHTML;
                }
            } else if (block.type === 'container' && blockEl) {
                /* Recurse into the Container's sub-editor */
                const sub = blockEl.querySelector('.codex-editor');
                if (sub && block.data?.content?.blocks) {
                    walk(block.data.content.blocks, sub);
                }
            }
        });
    };
    walk(outputData.blocks, containerEl);
    return outputData;
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
        // Link button — wraps the selected text in an <a href> (or removes it).
        const linkBtn = document.createElement('button');
        linkBtn.type = 'button'; linkBtn.title = 'Add / edit link';
        linkBtn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:34px;height:30px;border:none;border-radius:5px;cursor:pointer;background:transparent;color:#fff;transition:background .12s;margin-left:2px';
        linkBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
        linkBtn.addEventListener('mouseenter', () => linkBtn.style.background = 'rgba(99,102,241,0.5)');
        linkBtn.addEventListener('mouseleave', () => linkBtn.style.background = 'transparent');
        linkBtn.addEventListener('click', (ev) => { ev.preventDefault(); ev.stopPropagation(); applyLinkToSelection(); });
        el.appendChild(linkBtn);

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

    /** Wrap the current text selection in an <a href>. Uses an in-page input popup
     *  (NOT window.prompt — prompt blurs the window, which collapsed the selection
     *  so the link never applied AND closed the fullscreen editor). */
    function applyLinkToSelection() {
        const sel = window.getSelection();
        if (!sel || !sel.rangeCount || sel.isCollapsed) { return; }
        const range = sel.getRangeAt(0).cloneRange();
        const host = (range.commonAncestorContainer.nodeType === 1 ? range.commonAncestorContainer : range.commonAncestorContainer.parentElement);
        const ce = host && host.closest ? host.closest('[contenteditable=""],[contenteditable=true]') : null;
        const anchorEl = (sel.anchorNode && (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement));
        const existing = anchorEl && anchorEl.closest ? anchorEl.closest('a') : null;

        // Remove any prior link popup
        document.querySelectorAll('.mb-link-pop').forEach(n => n.remove());
        const parent = (document.querySelector('.editorjs-fullscreen-mode') || document.body);
        const pop = document.createElement('div');
        pop.className = 'mb-link-pop';
        pop.style.cssText = 'position:fixed;z-index:2147483647;background:#111827;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.4);padding:6px;display:flex;gap:6px;align-items:center';
        pop.addEventListener('mousedown', (e) => e.stopPropagation());
        const input = document.createElement('input');
        input.type = 'text';
        input.value = existing ? (existing.getAttribute('href') || '') : '';
        input.placeholder = 'https://… (κενό = αφαίρεση)';
        input.style.cssText = 'width:280px;padding:6px 9px;border:1px solid #374151;border-radius:5px;background:#1f2937;color:#fff;font-size:13px;outline:none';
        const ok = document.createElement('button');
        ok.type = 'button'; ok.textContent = 'OK';
        ok.style.cssText = 'padding:6px 14px;border:0;border-radius:5px;background:#4f46e5;color:#fff;font-size:13px;font-weight:600;cursor:pointer';
        pop.appendChild(input); pop.appendChild(ok);

        // Position under the alignment bar (or selection rect)
        const r = range.getBoundingClientRect();
        let top = (bar && bar.getBoundingClientRect().height ? bar.getBoundingClientRect().bottom + 6 : r.bottom + 8);
        let left = r.left;
        if (left + 360 > window.innerWidth - 8) left = window.innerWidth - 360 - 8;
        if (left < 8) left = 8;
        pop.style.top = Math.max(8, top) + 'px';
        pop.style.left = left + 'px';
        parent.appendChild(pop);
        // Hide the alignment bar while the link input is open
        if (bar) bar.style.display = 'none';
        setTimeout(() => input.focus(), 30);

        const apply = () => {
            const url = input.value.trim();
            try { if (ce) ce.focus(); sel.removeAllRanges(); sel.addRange(range); } catch (e) {}
            if (url === '') { document.execCommand('unlink'); }
            else { document.execCommand('createLink', false, url); }
            if (ce) { try { ce.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {} }
            pop.remove();
        };
        ok.addEventListener('click', apply);
        input.addEventListener('keydown', (e) => {
            e.stopPropagation();
            if (e.key === 'Enter') { e.preventDefault(); apply(); }
            if (e.key === 'Escape') { e.preventDefault(); pop.remove(); }
        });
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
            // Unwrap empty <span> elements (no attributes left → just dead wrappers
            // that accumulate after several apply→clear cycles).
            wrapper.querySelectorAll('span').forEach(span => {
                if (!span.hasAttributes()) {
                    while (span.firstChild) span.parentNode.insertBefore(span.firstChild, span);
                    span.remove();
                }
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

/* ─── Custom ColumnsTool for EditorJS (2 / 3 / 4 columns) ───
   Each column hosts its OWN nested EditorJS instance (same block set as the
   Container tool) so images, headings, lists, etc. can live inside a column —
   added via that column's own "+" toolbox. NOTE: editorjs-drag-drop only
   reorders blocks WITHIN one editor; you cannot drag a block from the outer
   editor across into a column — add it with the column's "+" instead. */
window.ColumnsTool = class ColumnsTool {
    static get toolbox() {
        return { title: 'Columns', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="6" height="16" rx="1"/><rect x="10" y="4" width="4" height="16"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>' };
    }
    static get isReadOnlySupported() { return true; }
    /** Same reason as ContainerTool — Enter in a nested column shouldn't kick the user out. */
    static get enableLineBreaks() { return true; }
    constructor({ data, api, config }) {
        this.api = api;
        const d = data && typeof data === 'object' ? data : {};
        const cols = d.cols || 2;
        let columns = Array.isArray(d.columns) ? d.columns : [];
        // Normalize each column to an EditorJS data object { blocks: [] }.
        // Legacy columns were stored as plain HTML strings — upgrade them to
        // real blocks (via the shared HTML→blocks parser when available) so
        // old pages keep their text and gain block capabilities.
        columns = columns.map(c => {
            if (c && typeof c === 'object' && Array.isArray(c.blocks)) return c;
            if (typeof c === 'string' && c.trim()) {
                if (typeof window._veHtmlToBlocks === 'function') {
                    try {
                        const parsed = window._veHtmlToBlocks(c.trim());
                        if (parsed && Array.isArray(parsed.blocks) && parsed.blocks.length) return parsed;
                    } catch (_) {}
                }
                return { blocks: [{ type: 'paragraph', data: { text: c } }] };
            }
            return { blocks: [] };
        });
        while (columns.length < cols) columns.push({ blocks: [] });
        this.data = { cols, columns };
        this.subEditors = [];
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
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.setCols(n);
            });
            wrapper.appendChild(btn);
        });
        const swallow = (e) => e.stopPropagation();
        wrapper.addEventListener('mousedown', swallow);
        wrapper.addEventListener('click', swallow);
        wrapper.addEventListener('pointerdown', swallow);
        return { type: 'html', element: wrapper };
    }
    async setCols(n) {
        // Persist what's typed in each column BEFORE rebuilding so nothing is lost.
        await this.syncFromDom();
        this.data.cols = n;
        const curr = this.data.columns.length;
        if (n > curr) { for (let i = curr; i < n; i++) this.data.columns.push({ blocks: [] }); }
        else if (n < curr) { this.data.columns = this.data.columns.slice(0, n); }
        this.rebuild();
    }
    /** Pull the latest blocks out of every nested editor into this.data.columns. */
    async syncFromDom() {
        for (let i = 0; i < this.subEditors.length; i++) {
            const ed = this.subEditors[i];
            if (ed && typeof ed.save === 'function') {
                try { this.data.columns[i] = await ed.save(); } catch (e) {}
            }
        }
    }
    /** Build the nested block set — mirrors ContainerTool so columns are just as capable. */
    _buildSubTools() {
        const _Header     = window.Header     || (typeof Header     !== 'undefined' ? Header     : null);
        const _NestedList = window.NestedList || (typeof NestedList !== 'undefined' ? NestedList : null);
        const _Quote      = window.Quote      || (typeof Quote      !== 'undefined' ? Quote      : null);
        const _Marker     = window.Marker     || (typeof Marker     !== 'undefined' ? Marker     : null);
        const _InlineCode = window.InlineCode || (typeof InlineCode !== 'undefined' ? InlineCode : null);
        const _Underline  = window.Underline  || (typeof Underline  !== 'undefined' ? Underline  : null);

        const subTools = {
            ...(window.ParagraphWithInlineTools ? { paragraph: { class: window.ParagraphWithInlineTools, inlineToolbar: true } } : {}),
            ...(window.HeaderWithInlineTools || _Header ? { header: { class: (window.HeaderWithInlineTools || _Header), inlineToolbar: true, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } } } : {}),
            ...(_NestedList ? { list: { class: _NestedList, inlineToolbar: true } } : {}),
            ...(_Quote ? { quote: { class: _Quote, inlineToolbar: true } } : {}),
            ...(_Marker ? { marker: _Marker } : {}),
            ...(_InlineCode ? { inlineCode: _InlineCode } : {}),
            ...(_Underline ? { underline: _Underline } : {}),
            ...(typeof CodeTool   !== 'undefined' ? { code: CodeTool } : {}),
            ...(typeof Delimiter  !== 'undefined' ? { delimiter: Delimiter } : {}),
            ...(typeof RawTool    !== 'undefined' ? { raw: RawTool } : {}),
            ...(typeof Table      !== 'undefined' ? { table: { class: Table, inlineToolbar: true } } : {}),
            ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),
            ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),
            ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),
            ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),
            ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),
            ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),
            ...(window.LiveHtmlTool ? { liveHtml: { class: window.LiveHtmlTool } } : {}),
            ...(window.SpaceTool   ? { space:   { class: window.SpaceTool   } } : {}),
            ...(typeof LinkTool !== 'undefined' ? { linkTool: { class: LinkTool, config: { endpoint: (window._editorjsField_fetchUrl || '') } } } : {}),
        };
        if (window.__editorImageTool) {
            subTools.image = {
                ...window.__editorImageTool,
                tunes: window.ImageSizeTune ? ['imageSize'] : [],
            };
        }
        return subTools;
    }
    render() {
        return window.safeBlockRender(this, this._renderInner, 'Columns');
    }
    _renderInner() {
        this.wrap = document.createElement('div');
        this.wrap.style.cssText = 'display:grid;gap:12px;padding:8px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;';
        this.rebuild();
        return this.wrap;
    }
    rebuild() {
        if (!this.wrap) return;
        // Tear down any existing nested editors before re-creating.
        (this.subEditors || []).forEach(ed => { try { ed && ed.destroy && ed.destroy(); } catch (e) {} });
        this.subEditors = [];

        this.wrap.style.gridTemplateColumns = `repeat(${this.data.cols || 2}, 1fr)`;
        this.wrap.innerHTML = '';

        this.data.columns.forEach((colData, idx) => {
            const col = document.createElement('div');
            col.dataset.col = idx;
            col.style.cssText = 'min-height:90px;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:4px;';
            const holder = document.createElement('div');
            holder.id = `ej-col-${Math.random().toString(36).slice(2, 9)}`;
            col.appendChild(holder);
            this.wrap.appendChild(col);

            try {
                const subEditor = new EditorJS({
                    holder: holder,
                    placeholder: `Column ${idx + 1} — press + to add`,
                    data: (colData && colData.blocks) ? colData : { blocks: [] },
                    minHeight: 60,
                    inlineToolbar: ['bold', 'italic', 'underline', 'marker', 'inlineCode',
                        ...(window.ColorTool ? ['color'] : []),
                        ...(window.InlineAlignmentTool ? ['inlineAlignment'] : []),
                        'link'],
                    tools: this._buildSubTools(),
                    tunes: [
                        ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                        ...(window.BlockClassesTune ? ['blockClasses'] : []),
                    ],
                    onChange: async () => {
                        try { this.data.columns[idx] = await subEditor.save(); } catch (e) {}
                    },
                    onReady: () => {
                        if (typeof window.initMultiBlockAlignmentBar === 'function') {
                            window.initMultiBlockAlignmentBar(holder);
                        }
                        try {
                            if (window.DragDrop) { subEditor._dragDrop = new window.DragDrop(subEditor); }
                        } catch (e) {}
                        setTimeout(() => {
                            if (typeof window.attachReorderArrows === 'function') {
                                window.attachReorderArrows(holder, subEditor);
                            }
                        }, 100);
                    },
                });
                this.subEditors.push(subEditor);
            } catch (e) {
                console.warn('[Columns] sub-editor init failed (non-fatal):', e);
                this.subEditors.push(null);
            }
        });
    }
    async save() {
        // Always pull the latest from every nested editor at save time.
        await this.syncFromDom();
        return { cols: this.data.cols, columns: this.data.columns };
    }
    destroy() {
        (this.subEditors || []).forEach(ed => { try { ed && ed.destroy && ed.destroy(); } catch (e) {} });
        this.subEditors = [];
    }
}

// ----------------------------------------------------------------------
// Reusable HTML → EditorJS blocks parser (shared with visual-page-editor).
// Defined UNCONDITIONALLY (no `typeof ... === undefined` guard) so the latest
// parser always wins, even across Livewire wire:navigate transitions that keep
// `window` alive — no full page reload needed after editing this file.
// ----------------------------------------------------------------------
window._veHtmlToBlocks = function (html) {
        if (typeof html !== 'string' || html.length === 0) return { blocks: [] };

        // CLEAN-NATIVE mode: every element becomes a real EditorJS primitive block
        // (image→image, h1-6→header, p→paragraph, ul/ol→list, …). Tailwind/CSS
        // classes are intentionally DROPPED so blocks are natively editable.
        // Layout wrappers (div/section/…) are flattened. Decorative svg/script/
        // style are stripped.
        const STRUCTURAL = ['section', 'div', 'article', 'main', 'header', 'footer', 'aside', 'nav', 'figure'];

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
                    // Standalone link / button → paragraph that keeps the link (editable)
                    const t = node.innerHTML.trim();
                    const href = node.getAttribute('href') || '#';
                    if (t) out.push({ type: 'paragraph', data: { text: '<a href="' + href + '">' + t + '</a>' } });
                    return;
                }

                // Layout wrappers + generic containers: if they hold element children,
                // flatten (drop the wrapper + its classes). Otherwise treat inline
                // text content as a paragraph.
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

// Defined unconditionally (see note above) so it refreshes across wire:navigate.
window._veLooksLikeHtml = function (str) {
        if (typeof str !== 'string') return false;
        const trimmed = str.trim();
        if (trimmed.length < 4) return false;
        if (trimmed[0] !== '<') return false;
        return /<\/?[a-z][\s\S]*?>/i.test(trimmed);
};

/* ──────────────────────────────────────────────────────────────────────────
   SectionEmbedTool — embed any Section/Template as a looping block.

   The block placeholder is a compact summary card with an Edit button.
   Clicking Edit opens a MODAL DIALOG that is built with plain DOM and
   appended directly to <body>. This is deliberate — no Alpine wrapper,
   no Livewire-rendered children, no risk of any morph algorithm walking
   into our modal and disturbing the parent EditorJS / fullscreen state.

   After Save: the modal applies the new config to the tool's data, calls
   `api.blocks.update()` so EditorJS notices the change (critical for the
   wire:model.live debounced flush to pick it up), and tears the modal
   down. The next call to `editor.save()` — automatic on fullscreen close
   via flushSave — captures the updated data.

   Server data (templates, cards, tokens) is sourced from `window.SE_DATA`,
   injected once per page by the Configurator Livewire component mounted in
   the admin layout.
   ────────────────────────────────────────────────────────────────────────── */
window.SectionEmbedTool = class SectionEmbedTool {
    static get toolbox() {
        return {
            title: 'Section / Loop',
            icon: '<svg width="17" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        };
    }
    /**
     * We deliberately do NOT declare a static sanitize() config. EditorJS's
     * sanitizer was written for string-typed inline-format fields and gets
     * weird with non-strings (numbers, booleans). The data we save is plain
     * config (no user-typed HTML except `card_html`, which is intentional
     * markup the editor owns), so passing it through verbatim is correct.
     */
    static get enableLineBreaks() { return false; }
    static get isReadOnlySupported() { return true; }

    constructor({ data, api, block }) {
        this.api = api;
        this.block = block;
        this.data = Object.assign({
            source_template: '', limit: 12, columns: 3, gap: 'normal',
            order_by: 'created_at', order_dir: 'desc',
            heading: '', subheading: '', section_class: 'py-12',
            card_template_slug: 'classic', card_html: '',
        }, data || {});
        this.wrapper = null;
        // Auto-open ONLY for genuinely fresh blocks (toolbox creation,
        // where data is empty/{}/undefined). When EditorJS restores a
        // saved block from the editor's initial JSON, `data` always
        // carries our saved keys — auto-popping the modal there would
        // confuse the user every time they reopen fullscreen.
        const isFreshBlock = !data || Object.keys(data).length === 0;
        this._autoOpen = isFreshBlock;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'ce-section-embed';
        this.wrapper.addEventListener('mousedown', (e) => { e.stopPropagation(); });
        this.wrapper.addEventListener('keydown', (e) => { e.stopPropagation(); });
        this._renderSummary();
        if (this._autoOpen) {
            this._autoOpen = false;
            // Defer so EditorJS finishes mounting before the modal asks
            // for window.SE_DATA / focus management.
            setTimeout(() => this._openModal(), 30);
        }
        return this.wrapper;
    }

    // ── COLLAPSED SUMMARY ───────────────────────────────────────────────────
    _renderSummary() {
        this.wrapper.style.cssText = 'border:2px dashed #c7d2fe;border-radius:12px;padding:18px;background:#f8fafc;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;transition:border-color .2s';
        const src = this.data.source_template || '<em style="color:#94a3b8">no source picked</em>';
        const cardLabel = this.data.card_html
            ? '<em style="color:#0f766e">custom design</em>'
            : (this.data.card_template_slug || 'classic');
        const cols = this.data.columns || 3;
        const limit = this.data.limit || 12;
        const headingPart = this.data.heading ? `<div style="font-weight:700;font-size:16px;color:#0f172a;margin-bottom:6px">${this._esc(this.data.heading)}</div>` : '';
        const subheadingPart = this.data.subheading ? `<div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">${this._esc(this.data.subheading)}</div>` : '';
        this.wrapper.innerHTML = `
            <div style="display:flex;align-items:flex-start;gap:14px">
                <div style="flex-shrink:0;width:48px;height:48px;border-radius:10px;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#4f46e5">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </div>
                <div style="flex:1;min-width:0">
                    ${subheadingPart}${headingPart}
                    <div style="font-weight:600;color:#1e293b;font-size:14px">Section: ${this._esc(src)}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px">${cols} cols · up to ${limit} entries · card: ${cardLabel}</div>
                </div>
                <button type="button" data-se-edit style="border:1px solid #c7d2fe;background:#fff;color:#4f46e5;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer">Edit</button>
            </div>`;
        this.wrapper.querySelector('[data-se-edit]').addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            this._openModal();
        });
        // Clicking anywhere on the summary card (not just the Edit button)
        // also opens the modal — discoverable affordance.
        this.wrapper.addEventListener('click', (e) => {
            if (e.target.closest('[data-se-edit]')) { return; }
            e.preventDefault(); e.stopPropagation();
            this._openModal();
        });
    }

    // ── MODAL DIALOG (appended directly to <body>) ─────────────────────────
    _openModal() {
        // Snapshot the current data so Cancel can restore it
        const original = JSON.parse(JSON.stringify(this.data));
        const D = window.SE_DATA || { templates: [], cards: [], tokensByTemplate: {} };
        console.log('[SE-MODAL] opening, SE_DATA has', D.templates.length, 'templates,', D.cards.length, 'cards');
        console.log('[SE-MODAL] tool data:', original);

        // Remove any leftover modal from a previous mount
        const stale = document.querySelector('.ce-se-modal-root');
        if (stale) { stale.remove(); }

        const root = document.createElement('div');
        root.className = 'ce-se-modal-root';
        // z-index sits above the fullscreen wysiwyg (z:1000) but the dialog
        // itself is offset top-right so the user can still SEE the editor
        // behind it. Cancel/Save are clear; backdrop is translucent.
        root.style.cssText = 'position:fixed;inset:0;z-index:2147483000;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif';

        const backdrop = document.createElement('div');
        backdrop.style.cssText = 'position:absolute;inset:0;background:rgba(15,23,42,0.35);backdrop-filter:blur(2px)';
        backdrop.addEventListener('click', () => closeWith(false));

        const dialog = document.createElement('div');
        dialog.style.cssText = 'position:absolute;top:40px;left:50%;transform:translateX(-50%);width:min(880px,calc(100vw - 40px));max-height:calc(100vh - 80px);background:#fff;border-radius:14px;box-shadow:0 30px 60px rgba(15,23,42,.35);display:flex;flex-direction:column;overflow:hidden';
        dialog.addEventListener('click', (e) => e.stopPropagation());

        root.appendChild(backdrop);
        root.appendChild(dialog);
        document.body.appendChild(root);

        const tool = this;
        const onEsc = (e) => { if (e.key === 'Escape') { e.stopPropagation(); closeWith(false); } };
        document.addEventListener('keydown', onEsc, true);

        function closeWith(commit) {
            document.removeEventListener('keydown', onEsc, true);
            root.remove();
            if (!commit) {
                // Restore the snapshot — user cancelled
                tool.data = original;
                tool._renderSummary();
                return;
            }
            // Persist the new data into EditorJS's internal store. Without
            // this the next editor.save() *would* still return the new data
            // (tool.save() uses tool.data), but blocks.update guarantees
            // the editor's onChange fires which propagates through the
            // editorjs-field's silent autosave too.
            console.log('[SE-MODAL] committing, new data:', tool.data);
            try {
                if (tool.api && tool.api.blocks && tool.block && tool.block.id) {
                    tool.api.blocks.update(tool.block.id, { ...tool.data });
                    console.log('[SE-MODAL] api.blocks.update called for', tool.block.id);
                }
            } catch (err) { console.warn('[SE-MODAL] api.blocks.update failed (non-fatal):', err); }
            tool._renderSummary();
        }

        // Build the form
        dialog.innerHTML = this._formMarkup(D);
        this._bindModalHandlers(dialog, D, closeWith);
    }

    _formMarkup(D) {
        const templatesOpts = ['<option value="">— pick a template —</option>']
            .concat(D.templates.map(t => `<option value="${this._esc(t.slug)}"${t.slug === this.data.source_template ? ' selected' : ''}>${this._esc(t.name)}</option>`))
            .join('');

        const availableCards = D.cards.filter(c => !c.source_template_slug || c.source_template_slug === this.data.source_template);
        const cardsGrid = availableCards.length === 0
            ? '<div style="padding:14px;text-align:center;font-size:12px;color:#64748b;border:1px dashed #cbd5e1;border-radius:8px">No library cards. Pick custom HTML below.</div>'
            : availableCards.map(c => `
                <button type="button" data-se-card="${this._esc(c.slug)}" style="text-align:left;padding:10px;border-radius:10px;border:2px solid ${c.slug === this.data.card_template_slug ? '#4f46e5' : '#e5e7eb'};background:${c.slug === this.data.card_template_slug ? '#eef2ff' : '#fff'};cursor:pointer">
                    <div style="font-size:13px;font-weight:600;color:#0f172a">${this._esc(c.name)}</div>
                    ${c.description ? `<div style="font-size:11px;color:#64748b;margin-top:3px">${this._esc(c.description)}</div>` : ''}
                </button>`).join('');

        const tokenKey = this.data.source_template || '__generic__';
        const tokens = D.tokensByTemplate[tokenKey] || D.tokensByTemplate.__generic__ || [];
        const grouped = {};
        for (const t of tokens) {
            const g = t.group || 'Other';
            if (!grouped[g]) grouped[g] = [];
            grouped[g].push(t);
        }
        const tokensHtml = Object.entries(grouped).map(([g, items]) => `
            <div style="margin-bottom:10px">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">${this._esc(g)}</div>
                ${items.map(t => `<button type="button" data-se-token="${this._esc(t.token)}" style="display:flex;width:100%;justify-content:space-between;align-items:center;padding:5px 8px;margin-bottom:2px;font-size:11px;border:1px solid #e2e8f0;background:#fff;border-radius:6px;cursor:pointer;text-align:left"><code style="color:#4f46e5;font-family:ui-monospace,monospace">${this._esc(t.token)}</code><span style="color:#64748b;font-size:10px;margin-left:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100px">${this._esc(t.label)}</span></button>`).join('')}
            </div>`).join('');

        const isLibraryMode = !this.data.card_html;
        return `
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#f8fafc">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:32px;height:32px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:#0f172a">Configure section</div>
                        <div style="font-size:11px;color:#64748b">Embed a template's entries as a card grid</div>
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <button type="button" data-se-cancel style="padding:6px 12px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;font-size:12px;color:#475569;cursor:pointer">Cancel</button>
                    <button type="button" data-se-save style="padding:6px 18px;border-radius:7px;border:0;background:#4f46e5;font-size:12px;font-weight:600;color:#fff;cursor:pointer">Save</button>
                </div>
            </div>
            <div data-se-body style="flex:1;overflow-y:auto;padding:18px 20px">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Source template *</label>
                    <select data-se-field="source_template" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">${templatesOpts}</select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Limit</label>
                        <input type="number" min="0" max="200" data-se-field="limit" value="${this.data.limit}" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Cols</label>
                        <select data-se-field="columns" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                            ${[1,2,3,4,5,6].map(c => `<option value="${c}"${c === Number(this.data.columns) ? ' selected' : ''}>${c}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Gap</label>
                    <select data-se-field="gap" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                        ${['tight','normal','large'].map(g => `<option value="${g}"${g === this.data.gap ? ' selected' : ''}>${g}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Order by</label>
                    <input type="text" data-se-field="order_by" value="${this._esc(this.data.order_by)}" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:ui-monospace,monospace">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Direction</label>
                    <select data-se-field="order_dir" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                        <option value="desc"${this.data.order_dir === 'desc' ? ' selected' : ''}>desc</option>
                        <option value="asc"${this.data.order_dir === 'asc' ? ' selected' : ''}>asc</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Subheading</label>
                    <input type="text" data-se-field="subheading" value="${this._esc(this.data.subheading)}" placeholder="Latest from us" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Heading</label>
                    <input type="text" data-se-field="heading" value="${this._esc(this.data.heading)}" placeholder="Featured properties" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
                </div>
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;font-size:11px;font-weight:600;color:#475569;margin-bottom:4px">Section wrapper classes (Tailwind)</label>
                <input type="text" data-se-field="section_class" value="${this._esc(this.data.section_class)}" placeholder="py-12 bg-white" style="width:100%;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:12px;font-family:ui-monospace,monospace">
            </div>

            <div style="border-top:1px solid #e5e7eb;padding-top:14px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                    <span style="font-size:13px;font-weight:600;color:#0f172a">Card design</span>
                    <div style="display:flex;gap:2px;padding:2px;background:#f1f5f9;border-radius:6px;margin-left:auto">
                        <button type="button" data-se-mode="library" style="padding:3px 10px;border-radius:4px;border:0;background:${isLibraryMode ? '#fff' : 'transparent'};font-size:11px;font-weight:600;color:${isLibraryMode ? '#4f46e5' : '#64748b'};cursor:pointer">Library</button>
                        <button type="button" data-se-mode="custom" style="padding:3px 10px;border-radius:4px;border:0;background:${!isLibraryMode ? '#fff' : 'transparent'};font-size:11px;font-weight:600;color:${!isLibraryMode ? '#4f46e5' : '#64748b'};cursor:pointer">Custom HTML</button>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px">
                    <div data-se-card-area>
                        ${isLibraryMode
                            ? `<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">${cardsGrid}</div>`
                            : `<textarea data-se-field="card_html" rows="10" spellcheck="false" placeholder="<article>...</article>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;font-size:11px;font-family:ui-monospace,monospace;line-height:1.5">${this._esc(this.data.card_html)}</textarea>`
                        }
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:600;color:#475569;margin-bottom:6px">Tokens (click to copy)</div>
                        <div style="max-height:280px;overflow-y:auto;padding-right:4px">${tokensHtml || '<div style="font-size:11px;color:#94a3b8">Pick a source template first.</div>'}</div>
                    </div>
                </div>
            </div>
        </div>`;
    }

    _bindModalHandlers(dialog, D, closeWith) {
        const tool = this;
        // Generic field bindings: <input/select/textarea data-se-field="key">
        // Source-template handler is overridden further down so it can
        // redraw the body; everything else just mutates tool.data.
        dialog.querySelectorAll('[data-se-field]:not([data-se-field="source_template"])').forEach(input => {
            const key = input.getAttribute('data-se-field');
            input.addEventListener('input', (e) => {
                e.stopPropagation();
                let v = input.value;
                if (input.type === 'number') { v = Number(v) || 0; }
                if (key === 'columns') { v = Number(v) || 3; }
                tool.data[key] = v;
            });
            input.addEventListener('change', (e) => { e.stopPropagation(); });
        });

        // Helper to redraw the form body in place when a sub-choice changes
        // (source template, card preset, mode toggle) without tearing the
        // whole modal down.
        const redrawBody = () => {
            const body = dialog.querySelector('[data-se-body]');
            if (!body) { return; }
            // Re-render the whole dialog innerHTML — buttons retain their
            // closeWith closures because we rebind them every call.
            dialog.innerHTML = tool._formMarkup(D);
            tool._bindModalHandlers(dialog, D, closeWith);
        };

        // Card preset selection
        dialog.querySelectorAll('[data-se-card]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                tool.data.card_template_slug = btn.getAttribute('data-se-card');
                tool.data.card_html = '';
                redrawBody();
            });
        });

        // Library / Custom toggle
        dialog.querySelectorAll('[data-se-mode]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const mode = btn.getAttribute('data-se-mode');
                if (mode === 'custom' && !tool.data.card_html) {
                    const card = D.cards.find(c => c.slug === tool.data.card_template_slug);
                    if (card) { tool.data.card_html = card.html; }
                }
                if (mode === 'library') {
                    tool.data.card_html = '';
                }
                redrawBody();
            });
        });

        // Token click → copy to clipboard
        dialog.querySelectorAll('[data-se-token]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                const tok = btn.getAttribute('data-se-token');
                try {
                    navigator.clipboard.writeText(tok);
                    const orig = btn.style.borderColor;
                    btn.style.borderColor = '#10b981';
                    setTimeout(() => { btn.style.borderColor = orig; }, 800);
                } catch (err) { /* ignore */ }
            });
        });

        // Source template change in the bindings above sets the value AND
        // calls redraw. Override that branch here to use the modal redraw
        // rather than the (now non-existent) inline _render path.
        const srcSelect = dialog.querySelector('[data-se-field="source_template"]');
        if (srcSelect) {
            srcSelect.addEventListener('input', (e) => {
                e.stopPropagation();
                tool.data.source_template = srcSelect.value;
                redrawBody();
            });
        }

        // Footer Save / Cancel
        const saveBtn = dialog.querySelector('[data-se-save]');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                if (!tool.data.source_template) {
                    alert('Pick a source template first.');
                    return;
                }
                closeWith(true);
            });
        }
        const cancelBtn = dialog.querySelector('[data-se-cancel]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', (e) => {
                e.preventDefault(); e.stopPropagation();
                closeWith(false);
            });
        }
    }

    _esc(s) {
        const t = document.createElement('div');
        t.textContent = String(s ?? '');
        return t.innerHTML;
    }

    save() { return { ...this.data }; }
};

// Click-to-replace for native image blocks: clicking a rendered image opens the
// media picker; on pick we swap the image by replacing the block in place
// (editor.blocks.update doesn't re-render the ImageTool, so delete+insert).
// Defined unconditionally so it refreshes across wire:navigate.
window._veAttachImageReplace = function (holderEl, editor) {
    if (!holderEl || !editor || holderEl._veImgReplaceHooked) return;
    holderEl._veImgReplaceHooked = true;
    holderEl.addEventListener('click', function (ev) {
        const img = ev.target && ev.target.closest && ev.target.closest('.image-tool img, .image-tool__image-picture, .image-tool__image img');
        if (!img) return;
        // Locate the top-level image block whose DOM contains this img
        let index = -1;
        try {
            const cnt = editor.blocks.getBlocksCount();
            for (let i = 0; i < cnt; i++) {
                const b = editor.blocks.getBlockByIndex(i);
                if (b && b.name === 'image' && b.holder && b.holder.contains(img)) { index = i; break; }
            }
        } catch (e) { return; }
        if (index < 0) return; // image is inside a nested editor — let that editor handle it
        ev.preventDefault();
        ev.stopPropagation();
        const apply = (url) => {
            if (!url) return;
            try {
                const blk = editor.blocks.getBlockByIndex(index);
                // Visual swap directly in the DOM (the ImageTool's blocks.update does
                // not re-render the <img> by itself). Then persist the new URL via
                // blocks.update — a NON-structural change, so it behaves like normal
                // typing and does NOT trigger the heavy re-render that was closing
                // the fullscreen editor (the old delete+insert did).
                const imgEl = (blk && blk.holder) ? blk.holder.querySelector('img') : img;
                if (imgEl) imgEl.setAttribute('src', url);
                if (blk) editor.blocks.update(blk.id, { file: { url: url }, caption: '', withBorder: false, withBackground: false, stretched: false });
            } catch (e) { console.warn('[image-replace] failed:', e); }
        };
        const mediaUrl = window._editorjsField_mediaUrl;
        if (typeof window.editorjsMediaPicker === 'function' && mediaUrl) {
            window.editorjsMediaPicker({ url: mediaUrl, onPick: function (sel) { apply(sel && sel.url); } });
        } else {
            const u = prompt('Νέα διεύθυνση εικόνας (URL):', img.getAttribute('src') || '');
            if (u !== null) apply(u);
        }
    }, true);
};


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

    // Close edit/add panel when clicking OUTSIDE both the panel and the
    // sidebar. Iframe clicks don't bubble to the parent document, so we also
    // listen for window.blur which fires when the iframe gains focus.
    function panelIsOpen() {
        return !!document.querySelector('[data-edit-panel]');
    }
    document.addEventListener('mousedown', function (e) {
        if (!panelIsOpen()) return;
        if (e.target.closest('[data-edit-panel]')) return; // click inside the panel
        if (e.target.closest('[data-ve-sidebar]')) return; // click inside the sidebar (don't fight wire:click on items)
        // Editor UI that floats to <body> (media picker, EditorJS popovers, tunes,
        // toolbars, link tool, etc.) is logically part of the editor — clicking it
        // must NOT close the panel. Without this, picking an image from the media
        // library overlay was closing the whole editor.
        if (e.target.closest('.ejs-media-overlay, .ce-livehtml-source-modal, .ce-livehtml-style-pop, .tw-picker-overlay, .tw-picker-modal, #mb-align-bar, .mb-link-pop, .ce-popover, .ce-popover--opened, .ce-popover__container, .ce-popover__items, .ce-popover-item-html, .ce-inline-toolbar, .ce-conversion-toolbar, .ce-settings, .ce-toolbar, .codex-editor, .image-tool, [data-ctr-settings], [data-btc-settings], .tippy-box, .cdx-search-field')) return;
        wireCall('closePanel');
    });
    window.addEventListener('blur', function () {
        // Fires when the iframe receives focus → user clicked into the preview.
        if (!panelIsOpen()) return;
        if (document.activeElement && document.activeElement.tagName === 'IFRAME') {
            wireCall('closePanel');
        }
    });

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

    // Scroll the sidebar tree to a section and expand any collapsed ancestors
    // so the just-clicked element is actually visible & highlighted.
    let _revealPending = null;
    function revealInTree(sectionId) {
        const tree = document.getElementById('ve-sections-list');
        if (!tree || !sectionId) return false;
        const target = tree.querySelector('[data-id="' + sectionId + '"]');
        if (!target) return false;

        // Expand collapsed ancestors (each .ve-section-item carries its own
        // Alpine collapse state + localStorage key).
        let node = target.parentElement;
        while (node && node !== tree) {
            if (node.classList && node.classList.contains('ve-section-item') && node.dataset.id) {
                try {
                    const data = window.Alpine && Alpine.$data(node);
                    if (data && typeof data.setCollapsed === 'function' && data.collapsed) {
                        data.setCollapsed(false);
                    }
                } catch (e) {}
            }
            node = node.parentElement;
        }

        setTimeout(() => {
            try { target.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (e) {
                target.scrollIntoView();
            }
        }, 90);
        return true;
    }

    // Listen for section clicks from the preview iframe
    window.addEventListener('message', function (e) {
        if (e.data && e.data.type === 've-section-click') {
            const id = e.data.sectionId;
            wireCall('selectSection', id);
            // The tree re-renders via Livewire; reveal once it has morphed.
            _revealPending = id;
            // Also try immediately (in case it's already in the DOM/visible).
            setTimeout(() => revealInTree(id), 60);
        }
    });

    // Highlight active section in iframe when selected from sidebar
    document.addEventListener('livewire:navigated', syncActiveSection);
    Livewire.hook('morph.updated', () => {
        syncActiveSection();
        if (_revealPending) {
            const id = _revealPending;
            _revealPending = null;
            setTimeout(() => revealInTree(id), 40);
        }
    });

    function syncActiveSection() {
        const frame = document.getElementById('preview-frame');
        if (!frame || !frame.contentDocument) return;
        const activeSectionId = {{ $selectedSectionId ?? 'null' }};
        frame.contentDocument.querySelectorAll('[data-ve-section-id]').forEach(el => {
            el.classList.toggle('ve-active', parseInt(el.dataset.veSectionId) === activeSectionId);
        });
    }
});
</script>

{{-- Floating multi-block alignment toolbar (always loaded for the visual page editor,
     even when <x-editorjs-field> hasn't rendered yet). Self-contained — depends only
     on findBlockPrimary / applyAlignmentToBlockElement helpers from the canonical
     component, and re-defines them lazily if missing. --}}
<script>
(function setupMBAlignmentBar() {
    if (window._mbAlignVPE) return;
    window._mbAlignVPE = true;
    console.log('[mb-align/VPE] script LOADED — visual-page-editor variant');

    // Ensure helpers exist (idempotent — safe if also defined elsewhere)
    if (typeof window.findBlockPrimary !== 'function') {
        window.findBlockPrimary = function(blockEl) {
            if (!blockEl) return null;
            return blockEl.querySelector('.ce-paragraph, .ce-header, .cdx-quote__text, h1, h2, h3, h4, h5, h6, p, blockquote, ul, ol, figure, pre, [contenteditable="true"]');
        };
    }
    if (typeof window.applyAlignmentToBlockElement !== 'function') {
        window.applyAlignmentToBlockElement = function(blockEl, alignment) {
            if (!blockEl) return;
            const primary = window.findBlockPrimary(blockEl);
            if (primary) primary.style.textAlign = alignment || '';
            if (alignment) blockEl.dataset.textAlignment = alignment;
            else delete blockEl.dataset.textAlignment;
        };
    }
    if (typeof window.patchAlignmentTunes !== 'function') {
        window.patchAlignmentTunes = function(outputData, containerEl) {
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
    }

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
        const fullscreenHost = document.querySelector('.editorjs-fullscreen-mode');
        const targetParent = fullscreenHost || document.body;
        if (!targetParent) return null;
        if (bar && (!document.contains(bar) || bar.parentElement !== targetParent)) {
            try { bar.remove(); } catch (e) {}
            bar = null;
        }
        if (!bar) {
            bar = buildBar();
            targetParent.appendChild(bar);
            console.log('[mb-align/VPE] bar attached to', targetParent === document.body ? '<body>' : '.editorjs-fullscreen-mode');
        }
        return bar;
    }

    function findBlocksForSelection() {
        const sel = window.getSelection();
        if (sel && sel.rangeCount && !sel.isCollapsed) {
            const range = sel.getRangeAt(0);
            const sEl = (range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement);
            const eEl = (range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement);
            const sBlock = sEl?.closest?.('.ce-block');
            const eBlock = eEl?.closest?.('.ce-block');
            if (sBlock) {
                const editorRoot = sBlock.closest('.codex-editor');
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
        const allEditors = document.querySelectorAll('.codex-editor');
        for (const ed of allEditors) {
            const flagged = Array.from(ed.querySelectorAll('.ce-block.ce-block--selected'));
            if (flagged.length >= 1) return { blocks: flagged, root: ed };
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
        const inFullscreen = el.parentElement?.classList?.contains('editorjs-fullscreen-mode');
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
        const blocks = lastBlocks.length ? lastBlocks : findBlocksForSelection().blocks;
        const root = lastEditor || findBlocksForSelection().root;
        if (!blocks.length) return;
        blocks.forEach(b => window.applyAlignmentToBlockElement(b, alignment));
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

    let checkTimer = null;
    function check(reason) {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(() => {
            const { blocks, root } = findBlocksForSelection();
            console.log('[mb-align/VPE] check from', reason, '→', blocks.length, 'block(s)');
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
    try {
        const mo = new MutationObserver((muts) => {
            for (const m of muts) {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    const t = m.target;
                    if (t.classList?.contains?.('ce-block')) { check('mutation'); return; }
                }
            }
        });
        const startObserver = () => mo.observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class'] });
        if (document.body) startObserver();
        else document.addEventListener('DOMContentLoaded', startObserver);
    } catch (e) {}

    window.addEventListener('scroll', () => { if (lastBlocks.length) showBarForBlocks(lastBlocks); }, true);
    document.addEventListener('mousedown', (e) => {
        if (bar && !bar.contains(e.target)) {
            setTimeout(() => {
                const { blocks } = findBlocksForSelection();
                if (!blocks.length) hideBar();
            }, 100);
        }
    });
})();
</script>

<script>
/* ─── Float the Container/Columns settings popover above everything ───────
   ONLY the popover that contains [data-ctr-settings] (the width panel) is
   touched — the EditorJS toolbox / block-add menu is never affected, so the
   container's central tool stays intact.

   When that popover opens it is RE-PARENTED to <body> with position:fixed and
   a maximal z-index. This escapes every ancestor's overflow clipping AND any
   trapping stacking context in one move (a fixed element re-parented to body
   can't be clipped or out-stacked by editor internals). On close it is moved
   back to its original spot so EditorJS keeps working normally. */
(function floatCtrSettingsPopover() {
    if (window._veCtrPopoverFix) return;
    window._veCtrPopoverFix = true;

    var MARGIN = 8;
    var Z = '2147483000';
    var openState = new WeakMap();

    function isTarget(el) {
        return el && el.classList && el.classList.contains('ce-popover')
            && !!el.querySelector('[data-ctr-settings]');
    }

    function floatOut(p) {
        if (p._veFloated) return;

        // STRATEGY: teleport to <body> + position:fixed (escapes ancestor
        // overflow:hidden + transform traps + 288px-wide edit panel),
        // AND patch contains() on every ancestor up to body so EditorJS's
        // `documentClicked` (which uses holder.contains / wrapper.contains)
        // still sees clicks inside the popover as "inside".

        p._veHome = { parent: p.parentNode, next: p.nextSibling };

        // Measure WHILE still in place — anchor to THIS toolbar's ⋮ button.
        var r = p.getBoundingClientRect();
        var toolbar = p.closest('.ce-toolbar');
        var btn = toolbar
            ? (toolbar.querySelector('.ce-toolbar__settings-btn') || toolbar.querySelector('.ce-toolbar__plus'))
            : null;
        var b = btn ? btn.getBoundingClientRect() : null;

        // Patch contains() on every original ancestor. EditorJS's
        // documentClicked checks holder.contains, wrapper.contains,
        // settingsToggler.contains. The wrapper (.ce-settings) and holder
        // (editor root) are both in this ancestor chain.
        //
        // The patch does NOT closure-capture `p` (the current popover) — it
        // looks up ALL floated popovers via querySelector each time. That's
        // critical because EditorJS destroys the popover element on close
        // WITHOUT giving our MutationObserver a chance to call floatBack;
        // a closure-captured reference would become stale and the second
        // open would close on first click (the bug we just hit).
        var patched = p._vePatchedAncestors = [];
        var anc = p._veHome.parent;
        while (anc && anc !== document.body && anc.nodeType === 1) {
            if (!anc._veContainsPatched) {
                anc._veOriginalContains = Node.prototype.contains.bind(anc);
                (function (node) {
                    node.contains = function (n) {
                        if (node._veOriginalContains(n)) return true;
                        // Live lookup — works for whatever popover is open
                        // right now, even if a previous one was destroyed
                        // without our floatBack ever firing.
                        var floats = document.querySelectorAll('.ce-popover[data-ve-floated]');
                        for (var i = 0; i < floats.length; i++) {
                            if (Node.prototype.contains.call(floats[i], n)) return true;
                        }
                        return false;
                    };
                })(anc);
                anc._veContainsPatched = true;
            }
            // Track which ancestors WE last patched so floatBack can restore
            // them (idempotent — a re-patched ancestor just gets re-listed).
            patched.push(anc);
            anc = anc.parentNode;
        }

        // Mark the popover so the live `contains` lookup finds it.
        p.setAttribute('data-ve-floated', '');
        document.body.appendChild(p);
        p.style.setProperty('position', 'fixed', 'important');
        p.style.setProperty('z-index', Z, 'important');
        p.style.setProperty('margin', '0', 'important');
        p.style.setProperty('transform', 'none', 'important');
        p.style.setProperty('width', '320px', 'important');
        p.style.setProperty('background', '#fff', 'important');
        p.style.setProperty('box-shadow', '0 20px 50px rgba(0,0,0,0.3)', 'important');
        p.style.setProperty('border', '1px solid #e5e7eb', 'important');
        p.style.setProperty('border-radius', '8px', 'important');
        // Also force background + width on the inner container so EditorJS's
        // own popover children render with proper background (the bare popover
        // sometimes has transparent body before user interaction).
        var inner = p.querySelector('.ce-popover__container');
        if (inner) {
            inner.style.setProperty('background', '#fff', 'important');
            inner.style.setProperty('width', '100%', 'important');
            inner.style.setProperty('max-width', '100%', 'important');
        }

        var w = 320;
        var h = p.offsetHeight || r.height || 300;
        var vw = window.innerWidth, vh = window.innerHeight;

        var left, top;
        if (b && b.width) {
            left = b.right - w;
            top = b.bottom + 4;
        } else if (r.width) {
            left = r.left;
            top = r.top;
        } else {
            left = vw - w - MARGIN;
            top = MARGIN;
        }
        if (left + w > vw - MARGIN) left = vw - w - MARGIN;
        if (left < MARGIN) left = MARGIN;
        if (top + h > vh - MARGIN) top = Math.max(MARGIN, vh - h - MARGIN);
        if (top < MARGIN) top = MARGIN;

        p.style.setProperty('left', Math.round(left) + 'px', 'important');
        p.style.setProperty('top', Math.round(top) + 'px', 'important');
        p._veFloated = true;
    }

    function floatBack(p) {
        if (!p._veFloated) return;
        ['position', 'z-index', 'margin', 'left', 'top', 'transform', 'width', 'background', 'box-shadow', 'border', 'border-radius'].forEach(function (k) {
            p.style.removeProperty(k);
        });
        p.removeAttribute('data-ve-floated');
        // Leave the ancestor `contains` patches in place — they're harmless
        // when no floated popover exists (the live querySelector lookup just
        // returns nothing) and would otherwise need to be re-applied next
        // time the popover opens, with a window where EditorJS could close
        // it before re-patching completes.
        p._vePatchedAncestors = [];
        var home = p._veHome;
        if (home && home.parent) {
            if (home.next && home.next.parentNode === home.parent) {
                home.parent.insertBefore(p, home.next);
            } else {
                home.parent.appendChild(p);
            }
        }
        p._veHome = null;
        p._veFloated = false;
    }

    function update(p) {
        var isOpen = p.offsetParent !== null || p.classList.contains('ce-popover--opened');
        var wasOpen = openState.get(p) === true;
        openState.set(p, isOpen);
        if (isOpen && !wasOpen) {
            // measure in its native spot first, then float out next frame
            requestAnimationFrame(function () { floatOut(p); });
        } else if (!isOpen && wasOpen) {
            floatBack(p);
        }
    }

    function scan() {
        document.querySelectorAll('.ce-popover').forEach(function (el) {
            if (isTarget(el) || el._veFloated) update(el);
        });
    }
    var mo = new MutationObserver(scan);
    mo.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
    window.addEventListener('resize', scan, { passive: true });
})();

/* ── Fullscreen scroll: explicit wheel handler ─────────────────────────────
   On some macOS Chrome setups, the natural wheel behavior on the fullscreen
   scrollable wrapper doesn't fire — likely because the body has `overflow:
   hidden` (a fullscreen requirement) and the wheel event gets canceled before
   reaching the inner scrollable. Attach a non-passive wheel listener that
   manually scrolls the wrapper, guaranteeing scroll regardless of browser-
   specific event chains. */
(function attachFullscreenKeyboardScroll() {
    // We REMOVED the wheel handler entirely — it was the cause of the
    // dead-scroll bug. With overflow-y:scroll on the container, native
    // wheel scroll just works. Manually intercepting wheel events was
    // racing with native scroll and silently canceling it.
    //
    // Keyboard shortcuts are still nice-to-have, so we keep them but
    // only when the user isn't typing inside the editor.
    document.addEventListener('keydown', function (e) {
        var fs = document.getElementById('ejs-fullscreen-scrollable');
        if (!fs || !fs.classList.contains('is-fullscreen')) return;
        if (e.target && e.target.isContentEditable) return;
        var step = fs.clientHeight * 0.9;
        if (e.key === 'PageDown')  { fs.scrollTop += step; e.preventDefault(); }
        if (e.key === 'PageUp')    { fs.scrollTop -= step; e.preventDefault(); }
        if (e.key === 'End')       { fs.scrollTop = fs.scrollHeight; e.preventDefault(); }
        if (e.key === 'Home')      { fs.scrollTop = 0; e.preventDefault(); }
    });
})();

/* ── Edit-panel scroll preservation across Livewire morphs ────────────────
   When the user scrolls the edit panel and then clicks anywhere inside it,
   the click can trigger a Livewire commit (autosave-on-focus, x-data state
   updates, etc.) which morphs the DOM and resets the scrollable container's
   scrollTop back to 0. Save the scrollTop before each commit and restore it
   after the response is applied. Same trick for the section-list sidebar
   and the preview-iframe wrapper. */
(function preserveScrollAcrossLivewireMorphs() {
    if (typeof Livewire === 'undefined' || !Livewire.hook) return;
    var saved = new WeakMap();
    function scrollables() {
        return document.querySelectorAll(
            '[data-edit-panel] .overflow-y-auto, ' +
            '[data-ve-sidebar] .overflow-y-auto, ' +
            '#ejs-fullscreen-scrollable'
        );
    }
    Livewire.hook('commit', function (payload) {
        scrollables().forEach(function (el) { saved.set(el, el.scrollTop); });
        if (payload && typeof payload.succeed === 'function') {
            payload.succeed(function () {
                scrollables().forEach(function (el) {
                    var v = saved.get(el);
                    if (typeof v === 'number' && el.scrollTop !== v) el.scrollTop = v;
                });
                requestAnimationFrame(function () {
                    scrollables().forEach(function (el) {
                        var v = saved.get(el);
                        if (typeof v === 'number' && el.scrollTop !== v) el.scrollTop = v;
                    });
                });
            });
        }
    });
})();
</script>

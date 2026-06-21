/**
 * new-builder inspector: edits the selected node.
 *
 * Fields: Name (_name editor-only), Tag (type), Classes input + class-picker
 * chips (grouped common Tailwind utilities; clicking toggles the class and
 * highlights active chips), Content (direct text), Attributes (key/value rows).
 *
 * NB.createInspector(state, controller) -> { render }
 * controller provides: applyInspector(), renderAfterInspector().
 */
(function () {
    'use strict';

    var NB = window.NB;

    /**
     * Smart style controls. Each row is a "family" of mutually-exclusive Tailwind
     * utilities: picking one option removes every other class in `family` and
     * applies the chosen one (clicking the active option again clears the family).
     * `family` lists ALL classes that belong to the group (for clean removal);
     * `options` are the buttons shown ([class, short label]).
     */
    var CONTROL_GROUPS = [
        {
            label: 'Text align',
            family: ['text-left', 'text-center', 'text-right', 'text-justify'],
            options: [['text-left', 'Left'], ['text-center', 'Center'], ['text-right', 'Right'], ['text-justify', 'Justify']],
        },
        {
            label: 'Font weight',
            family: ['font-thin', 'font-light', 'font-normal', 'font-medium', 'font-semibold', 'font-bold', 'font-extrabold'],
            options: [['font-normal', 'Normal'], ['font-medium', 'Medium'], ['font-semibold', 'Semibold'], ['font-bold', 'Bold']],
        },
        {
            label: 'Font size',
            family: ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl', 'text-4xl', 'text-5xl'],
            options: [['text-sm', 'SM'], ['text-base', 'Base'], ['text-lg', 'LG'], ['text-xl', 'XL'], ['text-2xl', '2XL'], ['text-3xl', '3XL']],
        },
        {
            label: 'Transform',
            family: ['uppercase', 'lowercase', 'capitalize', 'normal-case'],
            options: [['uppercase', 'AB'], ['lowercase', 'ab'], ['capitalize', 'Ab'], ['normal-case', '—']],
        },
        {
            label: 'Display',
            family: ['block', 'inline-block', 'inline', 'flex', 'inline-flex', 'grid', 'hidden'],
            options: [['block', 'Block'], ['flex', 'Flex'], ['grid', 'Grid'], ['inline-block', 'Inline'], ['hidden', 'Hide']],
        },
        {
            label: 'Flex dir',
            family: ['flex-row', 'flex-col', 'flex-row-reverse', 'flex-col-reverse'],
            options: [['flex-row', 'Row'], ['flex-col', 'Column']],
        },
        {
            label: 'Justify',
            family: ['justify-start', 'justify-center', 'justify-end', 'justify-between', 'justify-around', 'justify-evenly'],
            options: [['justify-start', 'Start'], ['justify-center', 'Center'], ['justify-end', 'End'], ['justify-between', 'Between']],
        },
        {
            label: 'Align items',
            family: ['items-start', 'items-center', 'items-end', 'items-stretch', 'items-baseline'],
            options: [['items-start', 'Start'], ['items-center', 'Center'], ['items-end', 'End'], ['items-stretch', 'Stretch']],
        },
        {
            label: 'Gap',
            family: ['gap-0', 'gap-1', 'gap-2', 'gap-3', 'gap-4', 'gap-6', 'gap-8'],
            options: [['gap-2', 'S'], ['gap-4', 'M'], ['gap-6', 'L'], ['gap-8', 'XL']],
        },
        {
            label: 'Radius',
            family: ['rounded-none', 'rounded-sm', 'rounded', 'rounded-md', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-full'],
            options: [['rounded', 'SM'], ['rounded-lg', 'LG'], ['rounded-xl', 'XL'], ['rounded-full', 'Full']],
        },
        {
            label: 'Shadow',
            family: ['shadow-none', 'shadow-sm', 'shadow', 'shadow-md', 'shadow-lg', 'shadow-xl', 'shadow-2xl'],
            options: [['shadow', 'SM'], ['shadow-md', 'MD'], ['shadow-lg', 'LG'], ['shadow-xl', 'XL']],
        },
    ];

    NB.createInspector = function createInspector(state, controller) {
        function currentClasses(node) {
            return node.classes ? String(node.classes).trim().split(/\s+/).filter(Boolean) : [];
        }

        /** style attribute string -> one declaration per line (readable). */
        function styleToText(s) {
            if (!s) { return ''; }
            var decls = String(s).split(';').map(function (p) { return p.trim(); }).filter(Boolean);
            return decls.length ? decls.join(';\n') + ';' : '';
        }

        /** textarea value (newlines or ;) -> single-line style attribute string. */
        function textToStyle(t) {
            return String(t || '').split(/[\n;]+/).map(function (p) { return p.trim(); })
                .filter(Boolean).join('; ');
        }

        /** Applied classes shown as removable chips. */
        function classChipsMarkup(node) {
            var list = currentClasses(node);
            if (!list.length) {
                return '<p class="nb-hint" style="margin:.2rem 0 0">No classes yet — use the quick styles below, or type in the box.</p>';
            }
            return '<div class="nb-cls-chips">' + list.map(function (c) {
                return '<span class="nb-cls-chip">' + NB.escapeHtml(c) +
                    '<button type="button" class="nb-cls-x" data-cls-remove="' + NB.escapeHtml(c) + '" title="Remove">&times;</button></span>';
            }).join('') + '</div>';
        }

        /** Smart segmented controls (one exclusive family per row). */
        function controlsMarkup(node) {
            var active = currentClasses(node);
            var html = '';
            for (var g = 0; g < CONTROL_GROUPS.length; g++) {
                var grp = CONTROL_GROUPS[g];
                var fam = grp.family.join(' ');
                html += '<div class="nb-ctl-row"><span class="nb-ctl-label">' + NB.escapeHtml(grp.label) + '</span><div class="nb-ctl-opts">';
                for (var o = 0; o < grp.options.length; o++) {
                    var cls = grp.options[o][0], lbl = grp.options[o][1];
                    var on = active.indexOf(cls) !== -1;
                    html += '<button type="button" class="nb-ctl-btn' + (on ? ' nb-ctl-on' : '') +
                        '" data-ctl="' + NB.escapeHtml(cls) + '" data-family="' + NB.escapeHtml(fam) +
                        '" title="' + NB.escapeHtml(cls) + '">' + NB.escapeHtml(lbl) + '</button>';
                }
                html += '</div></div>';
            }
            return html;
        }

        var RICH_COLORS = ['#111827', '#dc2626', '#ea580c', '#d97706', '#16a34a', '#2563eb', '#7c3aed', '#db2777', '#6b7280', '#ffffff'];

        /** Mini WYSIWYG toolbar for the content box (bold / italic / underline / colour). */
        function richToolbar() {
            var swatches = RICH_COLORS.map(function (c) {
                return '<button type="button" class="nb-rich-color" data-rich-color="' + c + '" style="background:' + c + '" title="' + c + '"></button>';
            }).join('');
            return '<div class="nb-rich-toolbar">' +
                '<button type="button" class="nb-rich-btn" data-rich="bold" title="Bold"><b>B</b></button>' +
                '<button type="button" class="nb-rich-btn" data-rich="italic" title="Italic"><i>I</i></button>' +
                '<button type="button" class="nb-rich-btn" data-rich="underline" title="Underline"><u>U</u></button>' +
                '<button type="button" class="nb-rich-btn" data-rich-link title="Insert / edit link">&#128279;</button>' +
                '<span class="nb-rich-sep"></span>' + swatches +
                '<button type="button" class="nb-rich-btn nb-rich-clear" data-rich="removeFormat" title="Clear formatting">&times;</button>' +
                '</div>';
        }

        /** Read the content WYSIWYG box into the selected node (html if formatted, else plain). */
        function syncRich() {
            var box = state.els.inspector.querySelector('[data-field="rich"]');
            if (!box) { return; }
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return; }
            var n = located.node;
            if (box.children.length > 0) {
                n.html = box.innerHTML;
                n.content = box.textContent;
            } else {
                delete n.html;
                var t = box.textContent;
                if (t && t.trim() !== '') { n.content = t; } else { delete n.content; }
            }
        }

        /** Build <option>s for the source select from the (server-rendered) token sources. */
        function sourceOptions(selected) {
            var src = state.rootEl.querySelector('[data-tokens-source]');
            var html = '<option value="">— pick a collection —</option>';
            if (src) {
                Array.prototype.forEach.call(src.options, function (o) {
                    if (!o.value) { return; }
                    html += '<option value="' + NB.escapeHtml(o.value) + '"' +
                        (o.value === selected ? ' selected' : '') + '>' + NB.escapeHtml(o.textContent.trim()) + '</option>';
                });
            }
            return html;
        }

        /** Query Builder panel for a repeater node. */
        function loopPanel(cfg) {
            var orderVal = (cfg.order_by || 'created_at') + '|' + (cfg.order_dir || 'desc');
            function orderOpt(v, label) {
                return '<option value="' + v + '"' + (v === orderVal ? ' selected' : '') + '>' + label + '</option>';
            }
            return '<div class="nb-field"><label>Source (collection)</label>' +
                '<select data-loopq="source">' + sourceOptions(cfg.source || '') + '</select></div>' +
                '<div class="nb-field"><label>Number of items</label>' +
                '<input type="number" min="1" data-loopq="limit" value="' + (parseInt(cfg.limit, 10) || 6) + '"></div>' +
                '<div class="nb-field"><label>Order</label><select data-loopq="order">' +
                orderOpt('created_at|desc', 'Newest first') + orderOpt('created_at|asc', 'Oldest first') +
                orderOpt('title|asc', 'Title A → Z') + orderOpt('title|desc', 'Title Z → A') +
                '</select></div>' +
                '<div class="nb-field"><label>Offset (skip)</label>' +
                '<input type="number" min="0" data-loopq="offset" value="' + (parseInt(cfg.offset, 10) || 0) + '"></div>' +
                '<div class="nb-field"><label>Filter (optional)</label>' +
                '<div class="nb-loop-filter">' +
                '<input data-loopq="filter_field" placeholder="field e.g. category_slug" value="' + NB.escapeHtml(cfg.filter_field || '') + '">' +
                '<input data-loopq="filter_value" placeholder="value" value="' + NB.escapeHtml(cfg.filter_value || '') + '">' +
                '</div></div>' +
                '<p class="nb-hint">The repeater renders its <strong>first child</strong> once per item. Select inner elements and bind text/images to <code>{tokens}</code> from the Tokens panel.</p>' +
                '<button type="button" data-act="unmake-loop" class="nb-unmake-loop">✕ Remove repeater (keep element)</button>';
        }

        function render() {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            var inspector = state.els.inspector;
            if (!located) {
                inspector.innerHTML = '<p class="nb-hint">Select a node in the tree to edit it.</p>';
                return;
            }
            var n = located.node;
            var isImg = (n.type || '').toLowerCase() === 'img';
            var imgSrc = (n.attributes && n.attributes.src) || '';
            var loopRaw = n.attributes && n.attributes['data-vb-loop'];
            var isLoop = loopRaw != null;
            var loopCfg = {};
            if (isLoop) { try { loopCfg = JSON.parse(loopRaw) || {}; } catch (e) { loopCfg = {}; } }
            var tabs = isLoop ? ['loop', 'props', 'css'] : ['props', 'css'];
            var tab = (state.inspectorTab && tabs.indexOf(state.inspectorTab) !== -1) ? state.inspectorTab : tabs[0];
            var attrRows = Object.entries(n.attributes || {})
                .filter(function (pair) { return pair[0] !== 'style' && pair[0] !== 'data-vb-loop' && !(isImg && pair[0] === 'src'); })
                .map(function (pair) {
                    return '<div class="nb-attr-row">' +
                        '<input class="nb-attr-key" value="' + NB.escapeHtml(pair[0]) + '" placeholder="name">' +
                        '<input class="nb-attr-val" value="' + NB.escapeHtml(pair[1]) + '" placeholder="value">' +
                        '<button class="nb-attr-del" title="Remove">✕</button>' +
                        '</div>';
                }).join('');
            var styleText = styleToText(n.attributes && n.attributes.style);

            inspector.innerHTML =
                '<div class="nb-insp-tabs">' +
                (isLoop ? '<button type="button" class="nb-insp-tab' + (tab === 'loop' ? ' nb-insp-tab-active' : '') + '" data-insp-tab="loop">Loop</button>' : '') +
                '<button type="button" class="nb-insp-tab' + (tab === 'props' ? ' nb-insp-tab-active' : '') + '" data-insp-tab="props">Properties</button>' +
                '<button type="button" class="nb-insp-tab' + (tab === 'css' ? ' nb-insp-tab-active' : '') + '" data-insp-tab="css">Inline CSS</button>' +
                '</div>' +
                '<div class="nb-insp-body" data-insp-active="' + tab + '">' +
                (isLoop ? '<div class="nb-panel nb-panel-loop">' + loopPanel(loopCfg) + '</div>' : '') +
                '<div class="nb-panel nb-panel-props">' +
                    '<div class="nb-field"><label>Name (label)</label>' +
                    '<input data-field="name" value="' + NB.escapeHtml(n._name || '') + '" placeholder="Custom name (optional)"></div>' +
                    '<div class="nb-field"><label>Tag (type)</label>' +
                    '<input data-field="type" value="' + NB.escapeHtml(n.type || '') + '"></div>' +
                    '<div class="nb-field"><label>Classes</label>' +
                    '<div data-cls-chips>' + classChipsMarkup(n) + '</div>' +
                    '<input data-field="classes" value="' + NB.escapeHtml(n.classes || '') + '" placeholder="add classes, space-separated"></div>' +
                    (isLoop ? '' : '<button type="button" data-act="make-loop" class="nb-make-loop">↻ Make this a Repeater (loop)</button>') +
                    '<div class="nb-field"><label>Quick styles</label>' +
                    '<div data-controls class="nb-controls">' + controlsMarkup(n) + '</div></div>' +
                    (isImg
                        ? '<div class="nb-field"><label>Image source</label>' +
                          '<div class="nb-img-src-row">' +
                          '<input data-field="img-src" value="' + NB.escapeHtml(imgSrc) + '" placeholder="https://… or pick from library">' +
                          '<button type="button" data-act="pick-image" class="nb-pick-btn">&#128247; Pick</button>' +
                          '</div></div>'
                        : '') +
                    '<div class="nb-field"><label>Content</label>' +
                    richToolbar() +
                    '<div class="nb-rich" data-field="rich" contenteditable="true" spellcheck="false">' +
                    (n.html != null && String(n.html).trim() !== '' ? n.html : NB.escapeHtml(n.content || '')) +
                    '</div></div>' +
                    '<div class="nb-field"><label>Attributes</label>' +
                    '<div data-attrs>' + attrRows + '</div>' +
                    '<button data-act="add-attr" class="nb-add-attr">+ Add attribute</button></div>' +
                '</div>' +
                '<div class="nb-panel nb-panel-css">' +
                    '<div class="nb-field"><label>Inline CSS (style attribute)</label>' +
                    '<textarea data-field="style" class="nb-css-area" rows="10" spellcheck="false" ' +
                    'placeholder="color: #111827;&#10;padding: 1rem;&#10;display: flex;">' + NB.escapeHtml(styleText) + '</textarea>' +
                    '<p class="nb-hint" style="margin-top:.4rem">One declaration per line (or separated by “;”). Saved to the element’s <code>style</code> attribute and applied live in the preview.</p>' +
                    '</div>' +
                '</div>' +
                '</div>';
        }

        function collectAttrs() {
            var rows = state.els.inspector.querySelectorAll('.nb-attr-row');
            var attrs = {};
            rows.forEach(function (row) {
                var key = row.querySelector('.nb-attr-key').value.trim();
                var val = row.querySelector('.nb-attr-val').value;
                if (key !== '') {
                    attrs[key] = val;
                }
            });
            return attrs;
        }

        /** Read inspector DOM into the selected node. Returns false if no node. */
        function applyToNode() {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return false; }
            var n = located.node;
            var ins = state.els.inspector;
            var nameEl = ins.querySelector('[data-field="name"]');
            var typeEl = ins.querySelector('[data-field="type"]');
            var classesEl = ins.querySelector('[data-field="classes"]');
            var richEl = ins.querySelector('[data-field="rich"]');
            if (nameEl) {
                var nm = nameEl.value.trim();
                if (nm) { n._name = nm; } else { delete n._name; }
            }
            if (typeEl) { n.type = typeEl.value.trim() || 'div'; }
            if (classesEl) {
                n.classes = classesEl.value.trim();
                if (n.classes === '') { delete n.classes; }
            }
            if (richEl) {
                if (richEl.children.length > 0) {
                    n.html = richEl.innerHTML;
                    n.content = richEl.textContent;
                } else {
                    delete n.html;
                    var rt = richEl.textContent;
                    if (rt && rt.trim() !== '') { n.content = rt; } else { delete n.content; }
                }
            }
            var attrs = collectAttrs();
            var styleEl = ins.querySelector('[data-field="style"]');
            if (styleEl) {
                var styleStr = textToStyle(styleEl.value);
                if (styleStr) { attrs.style = styleStr; }
            }
            var imgSrcEl = ins.querySelector('[data-field="img-src"]');
            if (imgSrcEl) {
                var src = imgSrcEl.value.trim();
                if (src) { attrs.src = src; }
            }
            var loopSrcEl = ins.querySelector('[data-loopq="source"]');
            if (loopSrcEl) {
                var order = (ins.querySelector('[data-loopq="order"]').value || 'created_at|desc').split('|');
                var cfg = {
                    source: loopSrcEl.value,
                    limit: parseInt(ins.querySelector('[data-loopq="limit"]').value, 10) || 6,
                    order_by: order[0] || 'created_at',
                    order_dir: order[1] || 'desc',
                    offset: parseInt(ins.querySelector('[data-loopq="offset"]').value, 10) || 0,
                };
                var ff = ins.querySelector('[data-loopq="filter_field"]').value.trim();
                if (ff) {
                    cfg.filter_field = ff;
                    cfg.filter_value = ins.querySelector('[data-loopq="filter_value"]').value;
                }
                attrs['data-vb-loop'] = JSON.stringify(cfg);
            }
            n.attributes = attrs;
            return true;
        }

        /** Set the selected node's image src (from the media picker) and refresh. */
        function setImageSrc(url) {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return; }
            located.node.attributes = located.node.attributes || {};
            located.node.attributes.src = url;
            controller.applyInspector();
            render();
        }

        /**
         * Apply an exclusive control: drop every class in the family, then add the
         * chosen one — unless it was already active, in which case clear the family.
         */
        function applyControl(cls, familyStr) {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return; }
            var n = located.node;
            var family = familyStr.split(/\s+/).filter(Boolean);
            var list = currentClasses(n);
            var wasOn = list.indexOf(cls) !== -1;
            list = list.filter(function (c) { return family.indexOf(c) === -1; });
            if (!wasOn) { list.push(cls); }
            if (list.length) { n.classes = list.join(' '); } else { delete n.classes; }
            controller.afterChipToggle();
        }

        function removeClass(cls) {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return; }
            var n = located.node;
            var list = currentClasses(n).filter(function (c) { return c !== cls; });
            if (list.length) { n.classes = list.join(' '); } else { delete n.classes; }
            controller.afterChipToggle();
        }

        function onClick(e) {
            var tabBtn = e.target.closest('[data-insp-tab]');
            if (tabBtn) {
                e.preventDefault();
                state.inspectorTab = tabBtn.dataset.inspTab;
                state.els.inspector.querySelectorAll('[data-insp-tab]').forEach(function (b) {
                    b.classList.toggle('nb-insp-tab-active', b === tabBtn);
                });
                var body = state.els.inspector.querySelector('.nb-insp-body');
                if (body) { body.setAttribute('data-insp-active', state.inspectorTab); }
                return;
            }
            var richBtn = e.target.closest('[data-rich]');
            if (richBtn) {
                e.preventDefault();
                try { document.execCommand(richBtn.dataset.rich, false, null); } catch (err) { /* noop */ }
                syncRich();
                controller.applyInspector();
                return;
            }
            var richLink = e.target.closest('[data-rich-link]');
            if (richLink) {
                e.preventDefault();
                var sel = window.getSelection();
                var savedRange = (sel && sel.rangeCount) ? sel.getRangeAt(0).cloneRange() : null;
                var url = window.prompt('Link URL (leave empty to remove the link):', 'https://');
                if (url !== null) {
                    if (savedRange) { sel.removeAllRanges(); sel.addRange(savedRange); }
                    try {
                        if (url.trim() === '') {
                            document.execCommand('unlink', false, null);
                        } else {
                            document.execCommand('createLink', false, url.trim());
                        }
                    } catch (err) { /* noop */ }
                    syncRich();
                    controller.applyInspector();
                }
                return;
            }
            var richColor = e.target.closest('[data-rich-color]');
            if (richColor) {
                e.preventDefault();
                try {
                    document.execCommand('styleWithCSS', false, true);
                    document.execCommand('foreColor', false, richColor.dataset.richColor);
                } catch (err) { /* noop */ }
                syncRich();
                controller.applyInspector();
                return;
            }
            if (e.target.closest('[data-act="pick-image"]')) {
                e.preventDefault();
                var picker = state.rootEl && state.rootEl.__nbMedia;
                if (picker && typeof picker.open === 'function') {
                    picker.open(setImageSrc);
                }
                return;
            }
            if (e.target.closest('[data-act="make-loop"]')) {
                e.preventDefault();
                var locM = state.selectedId ? state.findNode(state.selectedId) : null;
                if (locM) {
                    locM.node.attributes = locM.node.attributes || {};
                    locM.node.attributes['data-vb-loop'] = '{"source":"","limit":6,"order_by":"created_at","order_dir":"desc"}';
                    state.inspectorTab = 'loop';
                    controller.applyInspector();
                    render();
                }
                return;
            }
            if (e.target.closest('[data-act="unmake-loop"]')) {
                e.preventDefault();
                var locU = state.selectedId ? state.findNode(state.selectedId) : null;
                if (locU && locU.node.attributes) {
                    delete locU.node.attributes['data-vb-loop'];
                    state.inspectorTab = 'props';
                    controller.applyInspector();
                    render();
                }
                return;
            }
            var ctl = e.target.closest('[data-ctl]');
            if (ctl) {
                e.preventDefault();
                applyControl(ctl.dataset.ctl, ctl.dataset.family || '');
                return;
            }
            var clsX = e.target.closest('[data-cls-remove]');
            if (clsX) {
                e.preventDefault();
                removeClass(clsX.dataset.clsRemove);
                return;
            }
            if (e.target.closest('.nb-attr-del')) {
                e.target.closest('.nb-attr-row').remove();
                applyToNode();
                controller.applyInspector();
                render();
                return;
            }
            if (e.target.closest('[data-act="add-attr"]')) {
                e.preventDefault();
                var container = state.els.inspector.querySelector('[data-attrs]');
                var row = document.createElement('div');
                row.className = 'nb-attr-row';
                row.innerHTML =
                    '<input class="nb-attr-key" placeholder="name">' +
                    '<input class="nb-attr-val" placeholder="value">' +
                    '<button class="nb-attr-del" title="Remove">✕</button>';
                container.appendChild(row);
                return;
            }
        }

        function onInput() {
            applyToNode();
            controller.applyInspector();
        }

        // Keep the text selection inside the content box when a toolbar button is
        // pressed (otherwise the click would blur it and execCommand would no-op).
        state.els.inspector.addEventListener('mousedown', function (e) {
            if (e.target.closest('[data-rich], [data-rich-color], [data-rich-link]')) {
                e.preventDefault();
            }
        });

        state.els.inspector.addEventListener('click', onClick);
        state.els.inspector.addEventListener('input', onInput);

        return { render: render, applyToNode: applyToNode };
    };
}());

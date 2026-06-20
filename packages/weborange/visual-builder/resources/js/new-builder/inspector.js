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

    var CHIP_GROUPS = [
        {
            label: 'Display / Flex',
            classes: ['block', 'inline-block', 'flex', 'inline-flex', 'grid', 'hidden',
                'flex-row', 'flex-col', 'items-center', 'justify-center', 'justify-between',
                'flex-wrap', 'gap-2', 'gap-4'],
        },
        {
            label: 'Spacing',
            classes: ['p-2', 'p-4', 'p-6', 'px-4', 'py-2', 'm-2', 'm-4', 'mx-auto', 'mt-4', 'mb-4'],
        },
        {
            label: 'Text',
            classes: ['text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'font-medium',
                'font-semibold', 'font-bold', 'text-center', 'uppercase', 'leading-tight'],
        },
        {
            label: 'Colors',
            classes: ['text-white', 'text-gray-700', 'text-blue-600', 'bg-white', 'bg-gray-100',
                'bg-blue-600', 'bg-blue-50', 'bg-gray-900'],
        },
        {
            label: 'Border / Radius',
            classes: ['border', 'border-gray-300', 'rounded', 'rounded-lg', 'rounded-full',
                'shadow', 'shadow-md', 'ring-1'],
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

        function chipMarkup(node) {
            var active = currentClasses(node);
            var html = '';
            for (var g = 0; g < CHIP_GROUPS.length; g++) {
                var group = CHIP_GROUPS[g];
                html += '<div class="nb-chip-group"><div class="nb-chip-group-label">' +
                    NB.escapeHtml(group.label) + '</div><div class="nb-chips">';
                for (var c = 0; c < group.classes.length; c++) {
                    var cls = group.classes[c];
                    var on = active.indexOf(cls) !== -1;
                    html += '<button type="button" class="nb-chip' + (on ? ' nb-chip-on' : '') +
                        '" data-chip="' + NB.escapeHtml(cls) + '">' + NB.escapeHtml(cls) + '</button>';
                }
                html += '</div></div>';
            }
            return html;
        }

        function render() {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            var inspector = state.els.inspector;
            if (!located) {
                inspector.innerHTML = '<p class="nb-hint">Select a node in the tree to edit it.</p>';
                return;
            }
            var n = located.node;
            var tab = state.inspectorTab || 'props';
            var isImg = (n.type || '').toLowerCase() === 'img';
            var imgSrc = (n.attributes && n.attributes.src) || '';
            var attrRows = Object.entries(n.attributes || {})
                .filter(function (pair) { return pair[0] !== 'style' && !(isImg && pair[0] === 'src'); })
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
                '<button type="button" class="nb-insp-tab' + (tab === 'props' ? ' nb-insp-tab-active' : '') + '" data-insp-tab="props">Properties</button>' +
                '<button type="button" class="nb-insp-tab' + (tab === 'css' ? ' nb-insp-tab-active' : '') + '" data-insp-tab="css">Inline CSS</button>' +
                '</div>' +
                '<div class="nb-insp-body" data-insp-active="' + tab + '">' +
                '<div class="nb-panel nb-panel-props">' +
                    '<div class="nb-field"><label>Name (label)</label>' +
                    '<input data-field="name" value="' + NB.escapeHtml(n._name || '') + '" placeholder="Custom name (optional)"></div>' +
                    '<div class="nb-field"><label>Tag (type)</label>' +
                    '<input data-field="type" value="' + NB.escapeHtml(n.type || '') + '"></div>' +
                    '<div class="nb-field"><label>Classes</label>' +
                    '<input data-field="classes" value="' + NB.escapeHtml(n.classes || '') + '"></div>' +
                    '<div class="nb-field"><label>Class picker</label>' +
                    '<div data-chips class="nb-chip-picker">' + chipMarkup(n) + '</div></div>' +
                    (isImg
                        ? '<div class="nb-field"><label>Image source</label>' +
                          '<div class="nb-img-src-row">' +
                          '<input data-field="img-src" value="' + NB.escapeHtml(imgSrc) + '" placeholder="https://… or pick from library">' +
                          '<button type="button" data-act="pick-image" class="nb-pick-btn">&#128247; Pick</button>' +
                          '</div></div>'
                        : '') +
                    '<div class="nb-field"><label>Content (direct text)</label>' +
                    '<textarea data-field="content" rows="2">' + NB.escapeHtml(n.content || '') + '</textarea></div>' +
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
            var contentEl = ins.querySelector('[data-field="content"]');
            if (nameEl) {
                var nm = nameEl.value.trim();
                if (nm) { n._name = nm; } else { delete n._name; }
            }
            if (typeEl) { n.type = typeEl.value.trim() || 'div'; }
            if (classesEl) {
                n.classes = classesEl.value.trim();
                if (n.classes === '') { delete n.classes; }
            }
            if (contentEl) { n.content = contentEl.value; }
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

        function toggleChip(cls) {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return; }
            var n = located.node;
            var list = currentClasses(n);
            var idx = list.indexOf(cls);
            if (idx === -1) { list.push(cls); } else { list.splice(idx, 1); }
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
            if (e.target.closest('[data-act="pick-image"]')) {
                e.preventDefault();
                var picker = state.rootEl && state.rootEl.__nbMedia;
                if (picker && typeof picker.open === 'function') {
                    picker.open(setImageSrc);
                }
                return;
            }
            var chip = e.target.closest('[data-chip]');
            if (chip) {
                e.preventDefault();
                toggleChip(chip.dataset.chip);
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

        state.els.inspector.addEventListener('click', onClick);
        state.els.inspector.addEventListener('input', onInput);

        return { render: render, applyToNode: applyToNode };
    };
}());

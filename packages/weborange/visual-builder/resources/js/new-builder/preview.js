/**
 * new-builder preview: live iframe render of the model.
 *
 * - Builds a full HTML doc with the chosen CSS framework loaded.
 * - Injects data-nb-id="<node._id>" on EVERY element — ONLY in the preview doc
 *   (never in the HTML/JSON panes). Used by hover-sync to map DOM <-> tree.
 * - Debounced srcdoc updates so the framework CSS isn't refetched every keystroke.
 * - Responsive width buttons (mobile/tablet/desktop) + open-in-new-tab (Blob).
 *
 * NB.createPreview(state, controller) -> { render, openInTab, setWidth, buildDoc, onLoad(cb) }
 */
(function () {
    'use strict';

    var NB = window.NB;
    var Core = window.NewBuilderCore;

    var WIDTHS = { mobile: 375, tablet: 768, full: null };

    NB.createPreview = function createPreview(state, controller) {
        var loadCallbacks = [];

        function frameworkHead(fw) {
            switch (fw) {
                case 'bootstrap':
                    return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">' +
                        '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer><\/script>';
                case 'bulma':
                    return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">';
                case 'none':
                    return '';
                case 'tailwind':
                default:
                    return '<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"><\/script>';
            }
        }

        /**
         * Serialize roots to HTML WITH data-nb-id injected on every element.
         * We clean() first (contract-pure) but walk the original roots in lockstep
         * to recover each node's _id, since clean() strips it.
         */
        function htmlWithIds() {
            return state.roots.map(function walk(node) {
                return serialize(node, 0);
            }).join('\n');

            function serialize(node, depth) {
                var indent = '    '.repeat(depth);
                var type = (node.type || 'div').toLowerCase();
                var attrs = buildAttrs(node);
                var children = Array.isArray(node.children) ? node.children : [];
                var content = (node.content != null) ? String(node.content).trim() : '';

                if (Core.VOID_ELEMENTS.has(type)) {
                    return indent + '<' + type + attrs + '>';
                }
                var hasContent = content !== '';
                var hasChildren = children.length > 0;
                if (!hasContent && !hasChildren) {
                    return indent + '<' + type + attrs + '></' + type + '>';
                }
                if (hasContent && !hasChildren) {
                    return indent + '<' + type + attrs + '>' + escapeText(content) + '</' + type + '>';
                }
                var lines = [indent + '<' + type + attrs + '>'];
                if (hasContent) {
                    lines.push('    '.repeat(depth + 1) + escapeText(content));
                }
                for (var i = 0; i < children.length; i++) {
                    lines.push(serialize(children[i], depth + 1));
                }
                lines.push(indent + '</' + type + '>');
                return lines.join('\n');
            }

            function buildAttrs(node) {
                var parts = [];
                if (node.classes && String(node.classes).trim() !== '') {
                    parts.push('class="' + escapeAttr(node.classes) + '"');
                }
                if (node.attributes && typeof node.attributes === 'object') {
                    Object.keys(node.attributes).sort().forEach(function (key) {
                        var val = node.attributes[key];
                        if (val === '' || val == null) {
                            parts.push(escapeAttr(key));
                        } else {
                            parts.push(escapeAttr(key) + '="' + escapeAttr(val) + '"');
                        }
                    });
                }
                parts.push('data-nb-id="' + escapeAttr(node._id || '') + '"');
                return ' ' + parts.join(' ');
            }

            function escapeAttr(v) {
                return String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
            function escapeText(v) {
                return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }
        }

        function buildDoc(withIds) {
            var html = withIds
                ? htmlWithIds()
                : Core.jsonToHtml(NB.cleanRoots(state.roots));
            var fw = state.els.framework ? state.els.framework.value : 'tailwind';
            var hoverStyle = withIds
                ? '<style>[data-nb-hover]{outline:2px solid #2563eb !important;outline-offset:-2px;}' +
                  '[data-nb-selected]{outline:2px solid #7c3aed !important;outline-offset:-2px;' +
                  'box-shadow:0 0 0 4px rgba(124,58,237,.15) !important;}</style>'
                : '';
            return '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                '<meta name="viewport" content="width=device-width, initial-scale=1">' +
                frameworkHead(fw) +
                '<style>body{margin:0;padding:16px;background:#fff;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,sans-serif}</style>' +
                hoverStyle +
                '</head><body>' + (html || '') + '</body></html>';
        }

        /**
         * Attach a one-shot load handler so the load callbacks (hover/click
         * binding in hover-sync) always fire for the srcdoc we are about to set
         * — including the very first paint. A single persistent listener can
         * miss the initial srcdoc load depending on iframe load timing, which
         * left the preview unbound (no hover, no click-to-select) until the
         * first edit forced a re-render.
         */
        function bindOnNextLoad(iframe) {
            function once() {
                iframe.removeEventListener('load', once);
                loadCallbacks.forEach(function (cb) { cb(); });
            }
            iframe.addEventListener('load', once);
        }

        var timer = null;
        function render() {
            var iframe = state.els.preview;
            if (!iframe) { return; }
            clearTimeout(timer);
            timer = setTimeout(function () {
                bindOnNextLoad(iframe);
                iframe.srcdoc = buildDoc(true);
            }, 350);
        }

        function openInTab() {
            var blob = new Blob([buildDoc(false)], { type: 'text/html' });
            window.open(URL.createObjectURL(blob), '_blank', 'noopener');
        }

        function setWidth(key) {
            state.previewWidth = key;
            var iframe = state.els.preview;
            if (!iframe) { return; }
            var px = WIDTHS[key];
            iframe.style.width = px ? (px + 'px') : '100%';
            var buttons = state.rootEl.querySelectorAll('[data-width]');
            buttons.forEach(function (b) {
                b.classList.toggle('nb-width-on', b.dataset.width === key);
            });
        }

        function onLoad(cb) {
            loadCallbacks.push(cb);
        }

        return {
            render: render,
            openInTab: openInTab,
            setWidth: setWidth,
            buildDoc: buildDoc,
            onLoad: onLoad,
        };
    };
}());

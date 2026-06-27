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
    var DESKTOP_W = 1280; // real width the "Desktop" preview renders at (then scaled to fit)

    NB.createPreview = function createPreview(state, controller) {
        var loadCallbacks = [];
        var loopCache = {};
        var sliderCache = {};

        function sampleConfig() {
            var cfg = state.rootEl.querySelector('[data-save-config]');
            return cfg
                ? { url: cfg.getAttribute('data-sample-url'), csrf: cfg.getAttribute('data-csrf') }
                : { url: null, csrf: null };
        }

        function collectLoopNodes(nodes, out) {
            nodes = nodes || state.roots;
            out = out || [];
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                if (n.attributes && n.attributes['data-vb-loop'] != null) { out.push(n); }
                if (n.children && n.children.length) { collectLoopNodes(n.children, out); }
            }
            return out;
        }

        /**
         * Replace each repeater's item template with real rendered items from
         * the host (sample endpoint), so the preview shows actual data. Runs on
         * every iframe load; results are cached per source+query+template.
         */
        function hydrateLoops() {
            var cfg = sampleConfig();
            if (!cfg.url) { return; }
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }

            collectLoopNodes().forEach(function (node) {
                var query;
                try { query = JSON.parse(node.attributes['data-vb-loop']); } catch (e) { return; }
                if (!query || !query.source) { return; }
                var itemHtml = Core.jsonToHtml(NB.cleanRoots(node.children || []));
                if (!itemHtml.trim()) { return; }
                var key = node._id + '|' + node.attributes['data-vb-loop'] + '|' + itemHtml;
                var el = doc.querySelector('[data-nb-id="' + node._id + '"]');
                if (!el) { return; }
                if (loopCache[key] != null) { el.innerHTML = loopCache[key]; return; }

                fetch(cfg.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json' },
                    body: JSON.stringify({
                        source: query.source, item_html: itemHtml,
                        limit: query.limit, order_by: query.order_by, order_dir: query.order_dir,
                        offset: query.offset, filter_field: query.filter_field, filter_value: query.filter_value,
                        ids: query.ids, parent: query.parent,
                    }),
                }).then(function (r) { return r.json(); }).then(function (j) {
                    var items = (j && j.items) || [];
                    var out = items.length
                        ? items.join('\n')
                        : '<div style="padding:1rem;color:#9ca3af;font-size:.85rem">No items match this query (check the source is published/active).</div>';
                    loopCache[key] = out;
                    var doc2 = iframe.contentDocument;
                    var el2 = doc2 && doc2.querySelector('[data-nb-id="' + node._id + '"]');
                    if (el2) { el2.innerHTML = out; }
                }).catch(function () { /* leave template as-is on failure */ });
            });
        }

        function collectSliderNodes(nodes, out) {
            nodes = nodes || state.roots;
            out = out || [];
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                if (n.attributes && n.attributes['data-vb-slider'] != null) { out.push(n); }
                if (n.children && n.children.length) { collectSliderNodes(n.children, out); }
            }
            return out;
        }

        /**
         * Replace each [data-vb-slider] block with the real rendered slider HTML
         * from the host, so the preview shows the slider instead of a placeholder.
         */
        function hydrateSliders() {
            var cfgEl = state.rootEl.querySelector('[data-save-config]');
            var url = cfgEl ? cfgEl.getAttribute('data-slider-url') : null;
            if (!url) { return; }
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }

            collectSliderNodes().forEach(function (node) {
                var id = node.attributes['data-vb-slider'];
                if (!id) { return; }
                var el = doc.querySelector('[data-nb-id="' + node._id + '"]');
                if (!el) { return; }
                var key = node._id + '|' + id;
                if (sliderCache[key] != null) { el.innerHTML = sliderCache[key]; return; }

                fetch(url + '?id=' + encodeURIComponent(id), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); }).then(function (j) {
                        var out = (j && j.html)
                            ? j.html
                            : '<div style="padding:1rem;color:#9ca3af;font-size:.85rem">Slider not found (check it is active and has slides).</div>';
                        sliderCache[key] = out;
                        var doc2 = iframe.contentDocument;
                        var el2 = doc2 && doc2.querySelector('[data-nb-id="' + node._id + '"]');
                        if (el2) { el2.innerHTML = out; }
                    }).catch(function () { /* leave placeholder on failure */ });
            });
        }

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
                // Repeater: leave the wrapper empty here — hydrateLoops fills it
                // with real items so we never flash the literal-token template.
                if (node.attributes && node.attributes['data-vb-loop'] != null) {
                    return indent + '<' + type + attrs + '>' +
                        '<div style="padding:1rem;color:#9ca3af;font-size:.85rem">Loading items…</div>' +
                        '</' + type + '>';
                }
                // Slider embed: leave the wrapper empty here — hydrateSliders fills
                // it with the real rendered slider.
                if (node.attributes && node.attributes['data-vb-slider'] != null) {
                    return indent + '<' + type + attrs + '>' +
                        '<div style="padding:1rem;color:#9ca3af;font-size:.85rem">Loading slider…</div>' +
                        '</' + type + '>';
                }
                // Rich inline content (content WYSIWYG): emit verbatim.
                var rawHtml = (node.html != null) ? String(node.html).trim() : '';
                if (rawHtml !== '') {
                    return indent + '<' + type + attrs + '>' + rawHtml + '</' + type + '>';
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

        /** Host theme stylesheets to mirror the live site in the preview. */
        function extraCssLinks() {
            var el = state.rootEl.querySelector('[data-preview-css]');
            if (!el) { return ''; }
            var urls;
            try { urls = JSON.parse(el.textContent || '[]'); } catch (e) { urls = []; }
            return (urls || []).map(function (u) {
                return '<link rel="stylesheet" href="' + String(u).replace(/"/g, '&quot;') + '">';
            }).join('');
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
                extraCssLinks() +
                (state.siteCss ? '<style data-vb-site-preview>' + state.siteCss + '</style>' : '') +
                (state.globalCss ? '<style data-vb-global-preview>' + state.globalCss + '</style>' : '') +
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
        function previewStage() {
            return state.rootEl.querySelector('.nb-preview-stage');
        }
        var savedScroll = 0;
        function restoreScroll() {
            var stage = previewStage();
            if (stage) { stage.scrollTop = savedScroll; }
        }

        function bindOnNextLoad(iframe) {
            function once() {
                iframe.removeEventListener('load', once);
                loadCallbacks.forEach(function (cb) { cb(); });
                applyFrame();
                restoreScroll(); // re-render reset the preview scroll — put it back
                // Re-measure + re-restore after async loop hydration / images settle.
                setTimeout(function () { applyFrame(); restoreScroll(); }, 700);
            }
            iframe.addEventListener('load', once);
        }

        var timer = null;
        function render() {
            var iframe = state.els.preview;
            if (!iframe) { return; }
            clearTimeout(timer);
            timer = setTimeout(function () {
                // Remember the scroll position so the re-render doesn't jump to top.
                var stage = previewStage();
                if (stage) { savedScroll = stage.scrollTop; }
                bindOnNextLoad(iframe);
                iframe.srcdoc = buildDoc(true);
            }, 350);
        }

        function openInTab() {
            var blob = new Blob([buildDoc(false)], { type: 'text/html' });
            window.open(URL.createObjectURL(blob), '_blank', 'noopener');
        }

        /** Wrap the iframe once so we can scale a desktop-width frame to fit the pane. */
        function ensureWrap(iframe) {
            var wrap = iframe.parentElement;
            if (wrap && wrap.hasAttribute('data-preview-wrap')) { return wrap; }
            wrap = document.createElement('div');
            wrap.setAttribute('data-preview-wrap', '');
            wrap.style.cssText = 'position:relative;overflow:hidden;flex:0 0 auto;box-shadow:0 1px 4px rgba(0,0,0,.08);background:#fff;';
            iframe.parentNode.insertBefore(wrap, iframe);
            wrap.appendChild(iframe);
            return wrap;
        }

        /**
         * Render the chosen device width at its REAL pixel width (so Tailwind's
         * responsive breakpoints fire) and scale the whole frame down to fit the
         * (often narrow) preview pane. Height follows the rendered content so the
         * pane scrolls vertically.
         */
        function applyFrame() {
            var iframe = state.els.preview;
            if (!iframe) { return; }
            var wrap = ensureWrap(iframe);
            var stage = wrap.parentElement;
            var key = state.previewWidth || 'full';
            var fixed = WIDTHS[key]; // 375 / 768 for mobile|tablet, null for desktop
            var availW = stage ? Math.max(240, stage.clientWidth - 24) : DESKTOP_W;
            // Desktop: fill ALL available width (so it isn't capped at 1280 on wide
            // screens), but never below DESKTOP_W so md:/lg: breakpoints still fire.
            var target = fixed || Math.max(DESKTOP_W, availW);
            var scale = availW < target ? (availW / target) : 1;
            var minH = stage ? Math.max(320, stage.clientHeight - 24) : 640;
            var docH = 0;
            try {
                docH = (iframe.contentDocument && iframe.contentDocument.body)
                    ? iframe.contentDocument.body.scrollHeight : 0;
            } catch (e) { docH = 0; }
            var frameH = Math.max(Math.round(minH / scale), docH || 0) || minH;
            iframe.style.maxWidth = 'none';
            iframe.style.width = target + 'px';
            iframe.style.height = frameH + 'px';
            iframe.style.transformOrigin = 'top left';
            iframe.style.transform = scale < 1 ? 'scale(' + scale + ')' : 'none';
            wrap.style.width = Math.round(target * scale) + 'px';
            wrap.style.height = Math.round(frameH * scale) + 'px';
        }

        function setWidth(key) {
            state.previewWidth = key;
            applyFrame();
            var buttons = state.rootEl.querySelectorAll('[data-width]');
            buttons.forEach(function (b) {
                b.classList.toggle('nb-width-on', b.dataset.width === key);
            });
        }

        function onLoad(cb) {
            loadCallbacks.push(cb);
        }

        // Hydrate repeaters with real data after every (re)render.
        loadCallbacks.push(hydrateLoops);
        loadCallbacks.push(hydrateSliders);

        // Wrap the (still empty) iframe now so re-parenting never reloads content later.
        if (state.els.preview) { ensureWrap(state.els.preview); }

        // Re-fit the scaled preview when the window/pane resizes.
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(applyFrame, 120);
        });

        return {
            render: render,
            openInTab: openInTab,
            setWidth: setWidth,
            buildDoc: buildDoc,
            onLoad: onLoad,
        };
    };
}());

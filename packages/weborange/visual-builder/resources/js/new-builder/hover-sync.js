/**
 * new-builder hover-sync: highlight links between the preview iframe and tree.
 *
 * The srcdoc iframe is same-origin (about:srcdoc), so after each srcdoc set we
 * re-bind mousemove/mouseout listeners on iframe.contentDocument (via the
 * iframe `load` event). Hovering an element in the preview outlines it and
 * highlights + scrolls the matching tree row; hovering a tree row outlines the
 * matching preview element.
 *
 * NB.createHoverSync(state, preview) -> { bindIframe, bindTree }
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createHoverSync = function createHoverSync(state, preview, onSelect) {
        function clearPreviewOutline() {
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }
            var prev = doc.querySelectorAll('[data-nb-hover]');
            prev.forEach(function (el) { el.removeAttribute('data-nb-hover'); });
        }

        function clearTreeHighlight() {
            state.els.tree.querySelectorAll('.nb-row.nb-hover').forEach(function (r) {
                r.classList.remove('nb-hover');
            });
        }

        function highlightTreeRow(id, scroll) {
            clearTreeHighlight();
            if (!id) { return; }
            var row = state.els.tree.querySelector('.nb-row[data-id="' + id + '"]');
            if (row) {
                row.classList.add('nb-hover');
                if (scroll) {
                    row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            }
        }

        function highlightPreviewEl(id) {
            clearPreviewOutline();
            if (!id) { return; }
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }
            var el = doc.querySelector('[data-nb-id="' + id + '"]');
            if (el) { el.setAttribute('data-nb-hover', '1'); }
        }

        /**
         * Persistent selection outline in the preview: mark the element matching
         * state.selectedId with data-nb-selected (distinct color from hover).
         * Called on every select AND after each iframe (re)load so the outline
         * survives preview re-renders.
         */
        function syncSelection() {
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }
            doc.querySelectorAll('[data-nb-selected]').forEach(function (el) {
                el.removeAttribute('data-nb-selected');
            });
            if (!state.selectedId) { return; }
            var el = doc.querySelector('[data-nb-id="' + state.selectedId + '"]');
            if (el) { el.setAttribute('data-nb-selected', '1'); }
        }

        /** Re-bind listeners on the iframe document (call on each load). */
        function bindIframe() {
            var iframe = state.els.preview;
            var doc = iframe && iframe.contentDocument;
            if (!doc) { return; }
            doc.addEventListener('mousemove', function (e) {
                var el = e.target && e.target.closest ? e.target.closest('[data-nb-id]') : null;
                var id = el ? el.getAttribute('data-nb-id') : null;
                if (id === state.hoverId) { return; }
                state.hoverId = id;
                clearPreviewOutline();
                if (el) { el.setAttribute('data-nb-hover', '1'); }
                // Tree is intentionally NOT touched on preview hover — it only
                // highlights/selects the node when the element is clicked.
            });
            doc.addEventListener('mouseleave', function () {
                state.hoverId = null;
                clearPreviewOutline();
                clearTreeHighlight();
            });
            // Click an element in the preview → select it (open in the Inspector).
            // Capture phase + preventDefault so clicking a link/button inside the
            // preview selects the node instead of navigating/submitting.
            doc.addEventListener('click', function (e) {
                var el = e.target && e.target.closest ? e.target.closest('[data-nb-id]') : null;
                var id = el ? el.getAttribute('data-nb-id') : null;
                if (id) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof onSelect === 'function') { onSelect(id); }
                }
            }, true);
            // Re-apply the selection outline for the freshly loaded document.
            syncSelection();
        }

        /** Bind hover on the tree (once). */
        function bindTree() {
            state.els.tree.addEventListener('mousemove', function (e) {
                var row = e.target && e.target.closest ? e.target.closest('.nb-row') : null;
                var id = row ? row.dataset.id : null;
                if (id === state.hoverId) { return; }
                state.hoverId = id;
                highlightPreviewEl(id);
            });
            state.els.tree.addEventListener('mouseleave', function () {
                state.hoverId = null;
                highlightPreviewEl(null);
            });
        }

        return { bindIframe: bindIframe, bindTree: bindTree, syncSelection: syncSelection };
    };
}());

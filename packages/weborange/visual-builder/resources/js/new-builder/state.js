/**
 * new-builder state: single source of truth (roots) + node lookup helpers.
 *
 * NB.createState(rootEl) returns a state object shared by every module via
 * the controller (NB.main). It holds the model and DOM element references but
 * no rendering logic.
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createState = function createState(rootEl) {
        var state = {
            roots: [],
            selectedId: null,
            syncing: false,
            previewWidth: 'full',
            hoverId: null,
            globalCss: '',
            els: {
                tree: rootEl.querySelector('[data-pane="tree"]'),
                html: rootEl.querySelector('[data-pane="html"]'),
                json: rootEl.querySelector('[data-pane="json"]'),
                globalCss: rootEl.querySelector('[data-global-css]'),
                preview: rootEl.querySelector('[data-pane="preview"]'),
                framework: rootEl.querySelector('[data-framework]'),
                inspector: rootEl.querySelector('[data-inspector]'),
                error: rootEl.querySelector('[data-error]'),
                undo: rootEl.querySelector('[data-tool="undo"]'),
                redo: rootEl.querySelector('[data-tool="redo"]'),
            },
            rootEl: rootEl,
        };

        /** Locate a node by _id; returns {node, parent, index, siblings} or null. */
        state.findNode = function findNode(id, nodes, parent, index) {
            nodes = nodes || state.roots;
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                if (n._id === id) {
                    return { node: n, parent: parent || null, index: i, siblings: nodes };
                }
                var found = findNode(id, n.children, n, i);
                if (found) {
                    return found;
                }
            }
            return null;
        };

        state.showError = function showError(msg) {
            var el = state.els.error;
            if (!el) { return; }
            if (msg) {
                el.textContent = msg;
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        };

        return state;
    };
}());

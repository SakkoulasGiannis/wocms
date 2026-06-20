/**
 * new-builder main: the controller. Wires state + modules together and owns the
 * sync flow (tree / HTML / JSON / preview) and the history pushes.
 *
 * Loaded LAST. Depends on (in load order):
 *   utils.js, state.js, history.js, tree.js, inspector.js, preview.js, dnd.js,
 *   hover-sync.js, plus NewBuilderCore (builder-core.js) and Sortable.
 */
(function () {
    'use strict';

    var NB = window.NB;
    var Core = window.NewBuilderCore;

    function NewBuilder(rootEl) {
        var state = NB.createState(rootEl);

        var controller = {
            select: select,
            toggleCollapse: toggleCollapse,
            nodeAction: nodeAction,
            applyInspector: applyInspector,
            afterChipToggle: afterChipToggle,
            onDrop: onDrop,
        };

        var tree = NB.createTree(state, controller);
        var inspector = NB.createInspector(state, controller);
        var preview = NB.createPreview(state, controller);
        var dnd = NB.createDnd(state, controller);
        var hover = NB.createHoverSync(state, preview, select);
        var history = NB.createHistory(state, function onRestore(roots) {
            state.roots = NB.decorate(roots);
            if (state.selectedId && !state.findNode(state.selectedId)) {
                state.selectedId = null;
            }
            renderAll(true);
        });

        preview.onLoad(hover.bindIframe);
        hover.bindTree();

        /* ---- pane rendering ---- */
        function renderHtmlPane() {
            state.els.html.value = Core.jsonToHtml(NB.cleanRoots(state.roots));
        }

        function renderJsonPane() {
            var out = NB.cleanRoots(state.roots);
            state.els.json.value = JSON.stringify(out.length === 1 ? out[0] : out, null, 2);
        }

        function renderTreeArea() {
            tree.render();
            dnd.init();
            inspector.render();
            preview.render();
        }

        /**
         * Full render from the model.
         * @param {boolean} skipHistory - true when the change came from a history
         *   restore (must NOT push a new entry).
         */
        function renderAll(skipHistory) {
            NB.decorate(state.roots);
            renderTreeArea();
            renderHtmlPane();
            renderJsonPane();
            state.showError(null);
            if (!skipHistory) {
                history.push();
            }
        }

        /* ---- controller actions ---- */
        function select(id) {
            state.selectedId = id;
            tree.render();
            dnd.init();
            inspector.render();
            hover.syncSelection();
        }

        /**
         * Insert a block (contract JSON) into the model. Appends as a child of
         * the selected node when one is selected, otherwise as a new root. The
         * new node becomes the selection. Used by the component palette.
         */
        function insertBlock(json) {
            var node = NB.decorate([json])[0];
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (located) {
                located.node.children = located.node.children || [];
                located.node.children.push(node);
                located.node._collapsed = false;
            } else {
                state.roots.push(node);
            }
            state.selectedId = node._id;
            renderAll();
        }

        /**
         * Append a dynamic {token} to the selected node — into its content, or
         * into an <img> src. Returns false if nothing is selected.
         */
        function insertToken(text) {
            var located = state.selectedId ? state.findNode(state.selectedId) : null;
            if (!located) { return false; }
            var n = located.node;
            if ((n.type || '').toLowerCase() === 'img') {
                n.attributes = n.attributes || {};
                n.attributes.src = (n.attributes.src || '') + text;
            } else {
                n.content = (n.content || '') + text;
            }
            renderAll();
            return true;
        }

        /**
         * Replace the whole document with the given roots (array of contract
         * JSON). Used by "edit existing section" to load a saved section back.
         */
        function loadRoots(roots) {
            var list = Array.isArray(roots) ? roots : [roots];
            state.roots = NB.decorate(list.map(NB.clean));
            state.selectedId = null;
            renderAll();
        }

        function toggleCollapse(id) {
            var located = state.findNode(id);
            if (!located) { return; }
            located.node._collapsed = !located.node._collapsed;
            tree.render();
            dnd.init();
        }

        function nodeAction(id, act) {
            var located = state.findNode(id);
            if (!located) { return; }
            if (act === 'add-child') {
                located.node.children.push(NB.decorate([{ type: 'div', children: [] }])[0]);
                located.node._collapsed = false;
            } else if (act === 'add-sibling') {
                located.siblings.splice(located.index + 1, 0, NB.decorate([{ type: 'div', children: [] }])[0]);
            } else if (act === 'duplicate') {
                var copy = NB.decorate([NB.clean(located.node)])[0];
                located.siblings.splice(located.index + 1, 0, copy);
                state.selectedId = copy._id;
            } else if (act === 'delete') {
                located.siblings.splice(located.index, 1);
                if (state.selectedId === id) {
                    state.selectedId = null;
                }
            }
            renderAll();
        }

        /** Inspector edited a field: update tree + panes + preview, keep inspector focus. */
        function applyInspector() {
            renderTreeAreaKeepInspector();
            renderHtmlPane();
            renderJsonPane();
            history.push();
        }

        function renderTreeAreaKeepInspector() {
            tree.render();
            dnd.init();
            preview.render();
        }

        /** Chip toggled: full inspector re-render needed (active state) + panes. */
        function afterChipToggle() {
            tree.render();
            dnd.init();
            inspector.render();
            renderHtmlPane();
            renderJsonPane();
            preview.render();
            history.push();
        }

        function onDrop() {
            renderAll();
        }

        /* ---- pane input handlers ---- */
        function onHtmlInput() {
            if (state.syncing) { return; }
            try {
                var roots = Core.htmlToJsonRoots(state.els.html.value);
                state.roots = NB.decorate(roots);
                state.selectedId = null;
                state.syncing = true;
                renderTreeArea();
                renderJsonPane();
                state.syncing = false;
                state.showError(null);
                history.push();
            } catch (e) {
                state.syncing = false;
                state.showError('HTML parse error: ' + e.message);
            }
        }

        function onJsonInput() {
            if (state.syncing) { return; }
            try {
                var parsed = JSON.parse(state.els.json.value);
                var roots = Array.isArray(parsed) ? parsed : [parsed];
                state.roots = NB.decorate(roots.map(NB.clean));
                state.selectedId = null;
                state.syncing = true;
                renderTreeArea();
                renderHtmlPane();
                state.syncing = false;
                state.showError(null);
                history.push();
            } catch (e) {
                state.showError('JSON error: ' + e.message);
            }
        }

        /* ---- toolbar ---- */
        function onToolbarClick(e) {
            var btn = e.target.closest('button[data-tool]');
            if (!btn) { return; }
            var tool = btn.dataset.tool;
            if (tool === 'add-root') {
                state.roots.push(NB.decorate([{ type: 'section', children: [] }])[0]);
                renderAll();
            } else if (tool === 'clear') {
                state.roots = [];
                state.selectedId = null;
                renderAll();
            } else if (tool === 'format-json') {
                onJsonInput();
            } else if (tool === 'undo') {
                history.undo();
            } else if (tool === 'redo') {
                history.redo();
            }
        }

        /* ---- keyboard undo/redo ---- */
        function isTextField(el) {
            if (!el) { return false; }
            var tag = el.tagName;
            return tag === 'TEXTAREA' || tag === 'INPUT' || el.isContentEditable;
        }

        function onKeydown(e) {
            var key = e.key.toLowerCase();
            var meta = e.metaKey || e.ctrlKey;
            if (!meta) { return; }
            // Let native undo work inside text fields (HTML/JSON panes, inputs).
            if (isTextField(e.target)) { return; }
            if (key === 'z' && !e.shiftKey) {
                e.preventDefault();
                history.undo();
            } else if ((key === 'z' && e.shiftKey) || key === 'y') {
                e.preventDefault();
                history.redo();
            }
        }

        /* ---- preview toolbar ---- */
        function onPreviewToolbarClick(e) {
            var widthBtn = e.target.closest('[data-width]');
            if (widthBtn) {
                preview.setWidth(widthBtn.dataset.width);
                return;
            }
            if (e.target.closest('[data-tool="open-tab"]')) {
                preview.openInTab();
            }
        }

        /* ---- wire up ---- */
        state.els.html.addEventListener('input', onHtmlInput);
        state.els.json.addEventListener('input', onJsonInput);
        if (state.els.framework) {
            state.els.framework.addEventListener('change', preview.render);
        }
        var toolbar = rootEl.querySelector('[data-toolbar]');
        if (toolbar) { toolbar.addEventListener('click', onToolbarClick); }
        var previewBar = rootEl.querySelector('[data-preview-toolbar]');
        if (previewBar) { previewBar.addEventListener('click', onPreviewToolbarClick); }
        document.addEventListener('keydown', onKeydown);

        /* ---- seed ---- */
        var seed = rootEl.querySelector('[data-seed-html]');
        if (seed && seed.value.trim() !== '') {
            try {
                state.roots = NB.decorate(Core.htmlToJsonRoots(seed.value));
            } catch (e) {
                state.roots = NB.decorate([{ type: 'div', children: [] }]);
            }
        } else {
            state.roots = NB.decorate([{ type: 'div', children: [] }]);
        }
        renderTreeArea();
        renderHtmlPane();
        renderJsonPane();
        preview.setWidth('full');
        history.reset();
        history.updateButtons();

        var api = { state: state, insertBlock: insertBlock, loadRoots: loadRoots, insertToken: insertToken };
        rootEl.__nb = api;
        return api;
    }

    NB.init = NewBuilder;

    function boot() {
        var root = document.getElementById('new-builder-app');
        if (root && !root.dataset.nbBooted) {
            root.dataset.nbBooted = '1';
            NewBuilder(root);
            if (typeof NB.createPalette === 'function') { NB.createPalette(root); }
            if (typeof NB.createMediaPicker === 'function') { NB.createMediaPicker(root); }
            if (typeof NB.createTokens === 'function') { NB.createTokens(root); }
            if (typeof NB.createSave === 'function') { NB.createSave(root); }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());

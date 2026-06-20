/**
 * new-builder tree: renders the node tree.
 *
 * Label policy:
 *   - Primary label = custom _name (editor-only) if set, else the tag name.
 *   - When _name is set, the tag is shown muted beside it.
 *   - A tiny muted hint shows the child/class count (never id#.class noise).
 * Collapse:
 *   - Nodes with children get a ▸/▾ toggle. State stored as _collapsed on the
 *     node (editor-only, stripped by clean(), survives re-render).
 *
 * NB.createTree(state, controller) -> { render }
 * controller provides: select(id), toggleCollapse(id), nodeAction(id, act).
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createTree = function createTree(state, controller) {
        function classCount(node) {
            if (!node.classes) { return 0; }
            return String(node.classes).trim().split(/\s+/).filter(Boolean).length;
        }

        function renderNode(node) {
            var li = document.createElement('li');
            li.className = 'nb-node';
            li.dataset.id = node._id;

            var row = document.createElement('div');
            row.className = 'nb-row' + (node._id === state.selectedId ? ' nb-selected' : '');
            row.dataset.id = node._id;

            var hasChildren = node.children && node.children.length > 0;

            var toggle = document.createElement('span');
            toggle.className = 'nb-toggle';
            if (hasChildren) {
                toggle.textContent = node._collapsed ? '▸' : '▾';
                toggle.dataset.toggle = node._id;
                toggle.title = node._collapsed ? 'Expand' : 'Collapse';
            } else {
                toggle.classList.add('nb-toggle-empty');
                toggle.textContent = '•';
            }
            row.appendChild(toggle);

            var handle = document.createElement('span');
            handle.className = 'nb-handle';
            handle.title = 'Drag to move';
            handle.textContent = '⠿';
            row.appendChild(handle);

            var label = document.createElement('span');
            label.className = 'nb-label';
            if (node._name) {
                label.textContent = node._name;
                var tagMuted = document.createElement('span');
                tagMuted.className = 'nb-tag-muted';
                tagMuted.textContent = node.type || 'div';
                label.appendChild(tagMuted);
            } else {
                label.textContent = node.type || 'div';
            }
            row.appendChild(label);

            var cc = classCount(node);
            if (cc > 0) {
                var hint = document.createElement('span');
                hint.className = 'nb-hint-count';
                hint.textContent = '.' + cc;
                hint.title = cc + ' class' + (cc === 1 ? '' : 'es');
                row.appendChild(hint);
            }

            if (node.content) {
                var prev = document.createElement('span');
                prev.className = 'nb-preview';
                prev.textContent = '“' + String(node.content).slice(0, 22) + '”';
                row.appendChild(prev);
            }

            var actions = document.createElement('span');
            actions.className = 'nb-actions';
            actions.innerHTML =
                '<button data-act="add-child" title="Add child">＋child</button>' +
                '<button data-act="add-sibling" title="Add sibling">＋sib</button>' +
                '<button data-act="delete" title="Delete" class="nb-del">✕</button>';
            row.appendChild(actions);

            li.appendChild(row);

            var childrenUl = document.createElement('ul');
            childrenUl.className = 'nb-children';
            childrenUl.dataset.parentId = node._id;
            if (node._collapsed) {
                childrenUl.classList.add('nb-collapsed');
            }
            for (var i = 0; i < node.children.length; i++) {
                childrenUl.appendChild(renderNode(node.children[i]));
            }
            li.appendChild(childrenUl);
            return li;
        }

        function render() {
            var tree = state.els.tree;
            tree.innerHTML = '';
            var ul = document.createElement('ul');
            ul.className = 'nb-roots';
            for (var i = 0; i < state.roots.length; i++) {
                ul.appendChild(renderNode(state.roots[i]));
            }
            tree.appendChild(ul);
        }

        function onClick(e) {
            var toggle = e.target.closest('[data-toggle]');
            if (toggle) {
                e.stopPropagation();
                controller.toggleCollapse(toggle.dataset.toggle);
                return;
            }
            var actBtn = e.target.closest('button[data-act]');
            var row = e.target.closest('.nb-row');
            if (actBtn && row) {
                e.stopPropagation();
                controller.nodeAction(row.dataset.id, actBtn.dataset.act);
                return;
            }
            if (row) {
                controller.select(row.dataset.id);
            }
        }

        state.els.tree.addEventListener('click', onClick);

        return { render: render };
    };
}());

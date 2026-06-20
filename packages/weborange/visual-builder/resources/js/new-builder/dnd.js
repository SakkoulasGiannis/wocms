/**
 * new-builder dnd: SortableJS drag & drop of tree nodes (any depth).
 *
 * Rebuilt after every tree render. On drop, mutates state.roots (move node to
 * the target sibling array at the dropped index) then asks the controller to
 * re-render everything from the authoritative model.
 *
 * NB.createDnd(state, controller) -> { init, destroy }
 * controller provides: onDrop() (re-render + history push).
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createDnd = function createDnd(state, controller) {
        var sortables = [];

        function destroy() {
            for (var i = 0; i < sortables.length; i++) {
                try { sortables[i].destroy(); } catch (e) { /* noop */ }
            }
            sortables = [];
        }

        function handleDragEnd(evt) {
            var movedId = evt.item.dataset.id;
            var toList = evt.to;
            var newIndex = evt.newIndex;

            var located = state.findNode(movedId);
            if (!located) { return; }
            located.siblings.splice(located.index, 1);

            var targetArray;
            if (toList.classList.contains('nb-roots')) {
                targetArray = state.roots;
            } else {
                var parentId = toList.dataset.parentId;
                var targetParent = state.findNode(parentId);
                targetArray = targetParent ? targetParent.node.children : state.roots;
            }
            targetArray.splice(newIndex, 0, located.node);

            controller.onDrop();
        }

        function init() {
            destroy();
            if (typeof window.Sortable === 'undefined') { return; }
            var lists = state.els.tree.querySelectorAll('ul.nb-children, ul.nb-roots');
            lists.forEach(function (ul) {
                sortables.push(window.Sortable.create(ul, {
                    group: 'nb-nodes',
                    handle: '.nb-handle',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: handleDragEnd,
                }));
            });
        }

        return { init: init, destroy: destroy };
    };
}());

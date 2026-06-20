/**
 * new-builder history: debounced undo/redo of model snapshots.
 *
 * - Snapshots are deep clones of state.roots (editor fields preserved).
 * - Identical consecutive states are de-duped (by snapshotKey).
 * - Stack capped at CAP entries.
 * - Restoring a snapshot sets a `restoring` guard so it does NOT push a new entry.
 *
 * NB.createHistory(state, onRestore) -> { push, undo, redo, canUndo, canRedo, reset }
 * onRestore(roots) is called with the restored roots; the controller re-renders.
 */
(function () {
    'use strict';

    var NB = window.NB;
    var CAP = 60;
    var DEBOUNCE_MS = 350;

    NB.createHistory = function createHistory(state, onRestore) {
        var stack = [];
        var index = -1;
        var restoring = false;
        var timer = null;

        function current() {
            return index >= 0 ? stack[index] : null;
        }

        function commit() {
            var snapshot = NB.cloneRoots(state.roots);
            var key = NB.snapshotKey(NB.cleanRoots(snapshot));
            var cur = current();
            if (cur && NB.snapshotKey(NB.cleanRoots(cur)) === key) {
                return; // identical -> skip
            }
            // Drop any redo branch.
            stack = stack.slice(0, index + 1);
            stack.push(snapshot);
            if (stack.length > CAP) {
                stack.shift();
            }
            index = stack.length - 1;
            api.updateButtons();
        }

        var api = {
            isRestoring: function () { return restoring; },

            /** Push immediately (used for the initial seed snapshot). */
            pushNow: function () {
                if (restoring) { return; }
                clearTimeout(timer);
                commit();
            },

            /** Debounced push (used after edits). */
            push: function () {
                if (restoring) { return; }
                clearTimeout(timer);
                timer = setTimeout(commit, DEBOUNCE_MS);
            },

            canUndo: function () { return index > 0; },
            canRedo: function () { return index < stack.length - 1; },

            undo: function () {
                if (!api.canUndo()) { return; }
                clearTimeout(timer);
                index--;
                restoring = true;
                onRestore(NB.cloneRoots(stack[index]));
                restoring = false;
                api.updateButtons();
            },

            redo: function () {
                if (!api.canRedo()) { return; }
                clearTimeout(timer);
                index++;
                restoring = true;
                onRestore(NB.cloneRoots(stack[index]));
                restoring = false;
                api.updateButtons();
            },

            reset: function () {
                stack = [];
                index = -1;
                api.pushNow();
            },

            updateButtons: function () {
                if (state.els.undo) { state.els.undo.disabled = !api.canUndo(); }
                if (state.els.redo) { state.els.redo.disabled = !api.canRedo(); }
            },
        };

        return api;
    };
}());

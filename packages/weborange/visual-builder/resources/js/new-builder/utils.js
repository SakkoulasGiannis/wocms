/**
 * new-builder utils: the global namespace + small shared helpers.
 *
 * No build step. Every new-builder module attaches to window.NB and is loaded
 * via plain <script> tags in dependency order (see the blade @push('scripts')).
 *
 * Editor-only fields (stripped from output by clean()): _id, _name, _collapsed.
 */
(function () {
    'use strict';

    var NB = (window.NB = window.NB || {});

    NB.uid = function uid() {
        return 'n' + Math.random().toString(36).slice(2, 9);
    };

    NB.escapeHtml = function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    /** Attach a stable _id to every node (used to locate nodes for editing/DnD). */
    NB.decorate = function decorate(nodes) {
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            node._id = node._id || NB.uid();
            if (!Array.isArray(node.children)) {
                node.children = [];
            }
            NB.decorate(node.children);
        }
        return nodes;
    };

    /**
     * Strip ALL editor-internal fields (_id, _name, _collapsed) before
     * serializing to JSON / HTML. Whitelists only the JSON contract keys:
     * type, classes, attributes, content, children.
     */
    NB.clean = function clean(node) {
        var out = { type: node.type || 'div' };
        if (node.classes && String(node.classes).trim() !== '') {
            out.classes = String(node.classes).trim();
        }
        if (node.attributes && Object.keys(node.attributes).length > 0) {
            out.attributes = Object.assign({}, node.attributes);
        }
        // Persist the editor label (_name) as a data attribute so it survives the
        // HTML round-trip; elementToNode reads it back into _name on load.
        if (node._name && String(node._name).trim() !== '') {
            out.attributes = out.attributes || {};
            out.attributes['data-vb-name'] = String(node._name).trim();
        }
        if (node.html !== undefined && node.html !== null && String(node.html).trim() !== '') {
            out.html = String(node.html);
        } else if (node.content !== undefined && node.content !== null && String(node.content).trim() !== '') {
            out.content = String(node.content).trim();
        }
        out.children = (node.children || []).map(NB.clean);
        return out;
    };

    NB.cleanRoots = function cleanRoots(roots) {
        return roots.map(NB.clean);
    };

    /** Deep-clone a roots array, preserving editor fields (_name, _collapsed, _id). */
    NB.cloneRoots = function cloneRoots(roots) {
        return roots.map(function clone(node) {
            var out = {
                type: node.type || 'div',
                children: (node.children || []).map(clone),
            };
            if (node.classes) { out.classes = node.classes; }
            if (node.attributes && Object.keys(node.attributes).length) {
                out.attributes = Object.assign({}, node.attributes);
            }
            if (node.content !== undefined && node.content !== null && String(node.content) !== '') {
                out.content = node.content;
            }
            if (node.html !== undefined && node.html !== null && String(node.html) !== '') {
                out.html = node.html;
            }
            if (node._id) { out._id = node._id; }
            if (node._name) { out._name = node._name; }
            if (node._collapsed) { out._collapsed = true; }
            return out;
        });
    };

    var DEBOUNCE_IGNORE_KEYS = { _id: true };

    /** Serialize roots ignoring _id so history dedup is based on meaningful state. */
    NB.snapshotKey = function snapshotKey(roots) {
        return JSON.stringify(roots, function (key, value) {
            return DEBOUNCE_IGNORE_KEYS[key] ? undefined : value;
        });
    };
}());

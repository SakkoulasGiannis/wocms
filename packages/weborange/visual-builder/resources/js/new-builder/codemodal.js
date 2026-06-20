/**
 * new-builder code modal: a full-screen-ish modal hosting a CodeMirror editor
 * (line numbers + syntax highlight) for editing a chunk of HTML. Used by the
 * tree's "</>" action to edit a node's HTML (itself + children) and replace it.
 *
 * Falls back to a plain <textarea> if CodeMirror isn't loaded.
 *
 * NB.createCodeModal(rootEl) sets rootEl.__nbCodeModal = { open(opts) }.
 *   opts = { title, value, mode='htmlmixed', onApply(value) }
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createCodeModal = function createCodeModal(rootEl) {
        var overlay = document.createElement('div');
        overlay.className = 'nb-media-overlay hidden';
        overlay.innerHTML =
            '<div class="nb-code-modal">' +
            '<div class="nb-media-head">' +
            '<strong data-code-title>Edit HTML</strong>' +
            '<button type="button" class="nb-media-close" data-code-cancel title="Close">&times;</button>' +
            '</div>' +
            '<div class="nb-code-host" data-code-host></div>' +
            '<div class="nb-code-foot">' +
            '<span class="nb-hint" data-code-hint>Edit the HTML, then Apply — it is parsed back into the tree.</span>' +
            '<span class="nb-code-actions">' +
            '<button type="button" class="nb-code-btn" data-code-cancel>Cancel</button>' +
            '<button type="button" class="nb-code-btn nb-code-apply" data-code-apply>Apply</button>' +
            '</span>' +
            '</div>' +
            '</div>';
        rootEl.appendChild(overlay);

        var host = overlay.querySelector('[data-code-host]');
        var titleEl = overlay.querySelector('[data-code-title]');
        var cm = null;
        var textarea = null;
        var applyCb = null;

        function ensureEditor(mode) {
            if (textarea) {
                if (cm) { cm.setOption('mode', mode || 'htmlmixed'); }
                return;
            }
            textarea = document.createElement('textarea');
            textarea.className = 'nb-code-textarea';
            host.appendChild(textarea);
            if (window.CodeMirror) {
                cm = window.CodeMirror.fromTextArea(textarea, {
                    mode: mode || 'htmlmixed',
                    lineNumbers: true,
                    lineWrapping: true,
                    tabSize: 2,
                    indentUnit: 2,
                    autoCloseTags: true,
                });
                cm.setSize('100%', '100%');
            }
        }

        function getValue() {
            return cm ? cm.getValue() : (textarea ? textarea.value : '');
        }

        function setValue(v) {
            if (cm) { cm.setValue(v); } else if (textarea) { textarea.value = v; }
        }

        function open(opts) {
            opts = opts || {};
            applyCb = opts.onApply || null;
            titleEl.textContent = opts.title || 'Edit HTML';
            overlay.classList.remove('hidden');
            ensureEditor(opts.mode);
            setValue(opts.value || '');
            if (cm) {
                setTimeout(function () { cm.refresh(); cm.focus(); }, 30);
            } else if (textarea) {
                textarea.focus();
            }
        }

        function close() {
            overlay.classList.add('hidden');
            applyCb = null;
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.closest('[data-code-cancel]')) {
                close();
                return;
            }
            if (e.target.closest('[data-code-apply]')) {
                var value = getValue();
                var cb = applyCb;
                close();
                if (typeof cb === 'function') { cb(value); }
            }
        });

        rootEl.__nbCodeModal = { open: open };
    };
}());

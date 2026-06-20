/**
 * new-builder token picker: insert dynamic {tokens} (e.g. {name}, {location},
 * {main_image:hero}) into the selected node. The token list for a chosen source
 * template comes from admin/new-builder/tokens. Inserting appends the token to
 * the selected node's content (or to an <img> src) via rootEl.__nb.insertToken.
 *
 * Tokens resolve per-entity on the frontend (TokenResolver) — only meaningful
 * inside a Dynamic Loop section or on a template entry page.
 *
 * NB.createTokens(rootEl) -> wires the token panel.
 */
(function () {
    'use strict';

    var NB = window.NB;

    function escAttr(v) {
        return String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }
    function escHtml(v) {
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    NB.createTokens = function createTokens(rootEl) {
        var cfg = rootEl.querySelector('[data-tokens-config]');
        if (!cfg) { return; }
        var url = cfg.getAttribute('data-tokens-url');
        var panel = rootEl.querySelector('[data-tokens-panel]');
        var list = rootEl.querySelector('[data-tokens-list]');
        var srcSel = rootEl.querySelector('[data-tokens-source]');
        var status = rootEl.querySelector('[data-tokens-status]');
        if (!panel || !list || !srcSel) { return; }

        function setStatus(msg) {
            if (status) { status.textContent = msg || ''; }
        }

        function loadTokens(slug) {
            list.innerHTML = '';
            if (!slug) { setStatus('Pick a source to see its fields.'); return; }
            setStatus('Loading…');
            fetch(url + '?source=' + encodeURIComponent(slug), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    var toks = (j && j.tokens) || [];
                    if (!toks.length) { setStatus('No fields found for this source.'); return; }
                    setStatus('Select an element, then click a token to insert it.');
                    list.innerHTML = toks.map(function (t) {
                        return '<button type="button" class="nb-token-chip" data-token="' +
                            escAttr(t.token) + '" title="' + escAttr(t.label) + '">' + escHtml(t.token) + '</button>';
                    }).join('');
                })
                .catch(function (e) { setStatus('Error: ' + e.message); });
        }

        rootEl.addEventListener('click', function (e) {
            if (e.target.closest('[data-tokens-open]')) {
                e.preventDefault();
                panel.classList.remove('hidden');
                if (!list.children.length) { loadTokens(srcSel.value); }
                return;
            }
            if (e.target.closest('[data-tokens-cancel]')) {
                e.preventDefault();
                panel.classList.add('hidden');
                return;
            }
            var chip = e.target.closest('[data-token]');
            if (chip) {
                e.preventDefault();
                var token = chip.getAttribute('data-token');
                var ok = rootEl.__nb && typeof rootEl.__nb.insertToken === 'function'
                    ? rootEl.__nb.insertToken(token) : false;
                setStatus(ok ? ('Inserted ' + token) : 'Select an element first, then click a token.');
            }
        });

        srcSel.addEventListener('change', function () { loadTokens(srcSel.value); });
    };
}());

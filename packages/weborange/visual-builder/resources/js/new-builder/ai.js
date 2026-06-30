/**
 * new-builder AI panel: describe a section in natural language, the host AI
 * generator (POST admin/new-builder/ai) returns Tailwind HTML, and we parse it
 * into the builder tree (replace the canvas, or append to it).
 *
 * NB.createAi(rootEl) -> wires the AI panel.
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createAi = function createAi(rootEl) {
        var cfg = rootEl.querySelector('[data-ai-config]');
        if (!cfg) { return; }
        var url = cfg.getAttribute('data-ai-url');
        var csrf = cfg.getAttribute('data-csrf');
        var panel = rootEl.querySelector('[data-ai-panel]');

        function el(sel) { return rootEl.querySelector(sel); }
        function open() { if (panel) { panel.classList.remove('hidden'); } var p = el('[data-ai-prompt]'); if (p) { p.focus(); } }
        function close() { if (panel) { panel.classList.add('hidden'); } }

        function setResult(msg, kind) {
            var r = el('[data-ai-result]');
            if (!r) { return; }
            r.textContent = msg;
            r.className = 'mt-2 text-xs ' + (kind === 'err' ? 'text-red-600' : (kind === 'ok' ? 'text-emerald-700' : 'text-violet-700/80'));
        }

        function currentHtml() {
            var ta = rootEl.querySelector('[data-pane="html"]');
            return ta ? ta.value.trim() : '';
        }

        function loadResultHtml(html, replace) {
            var Core = window.NewBuilderCore;
            if (!rootEl.__nb || typeof rootEl.__nb.loadRoots !== 'function' || !Core) { return false; }
            var combined = replace ? html : (currentHtml() ? currentHtml() + '\n' + html : html);
            rootEl.__nb.loadRoots(Core.htmlToJsonRoots(combined));
            return true;
        }

        function generate(btn) {
            var promptEl = el('[data-ai-prompt]');
            var prompt = promptEl ? promptEl.value.trim() : '';
            if (!prompt) { setResult('Type what you want first.', 'err'); return; }
            var replace = !!(el('[data-ai-mode]') && el('[data-ai-mode]').checked);
            var label = el('[data-ai-label]');

            if (btn) { btn.disabled = true; }
            if (label) { label.textContent = '✨ Generating…'; }
            setResult('Thinking… this can take a few seconds.', 'info');

            var body = { prompt: prompt };
            // In replace mode, give the AI the current section so it can modify it.
            if (replace && currentHtml()) { body.current_html = currentHtml(); }
            // Optional style template — the AI matches its look.
            var tmpl = el('[data-ai-template]');
            if (tmpl && tmpl.value) { body.template_id = tmpl.value; }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify(body),
            }).then(function (r) {
                return r.json().then(function (j) { return { ok: r.ok, body: j }; });
            }).then(function (res) {
                var j = res.body || {};
                if (j.ok && j.html) {
                    var done = loadResultHtml(j.html, replace);
                    if (done) {
                        setResult('Done — generated and ' + (replace ? 'replaced the canvas' : 'appended to the canvas') + '. Edit it visually below.', 'ok');
                    } else {
                        setResult('Generated, but could not load it into the builder.', 'err');
                    }
                } else {
                    setResult(j.error || 'AI generation failed.', 'err');
                }
            }).catch(function (e) {
                setResult('Network error: ' + e.message, 'err');
            }).finally(function () {
                if (btn) { btn.disabled = false; }
                if (label) { label.innerHTML = '&#10024; Generate'; }
            });
        }

        function fixSeo(btn) {
            var html = currentHtml();
            if (!html) { setResult('Nothing to fix — the canvas is empty.', 'err'); return; }
            var label = el('[data-ai-fixseo-label]');

            if (btn) { btn.disabled = true; }
            if (label) { label.textContent = '⚒ Fixing…'; }
            setResult('Auditing heading structure… this can take a few seconds.', 'info');

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ mode: 'fix_seo', current_html: html }),
            }).then(function (r) {
                return r.json().then(function (j) { return { ok: r.ok, body: j }; });
            }).then(function (res) {
                var j = res.body || {};
                if (j.ok && j.html) {
                    var done = loadResultHtml(j.html, true);
                    setResult(done
                        ? 'Done — heading structure fixed. Review the canvas, then Save.'
                        : 'Fixed, but could not load it into the builder.', done ? 'ok' : 'err');
                } else {
                    setResult(j.error || 'SEO structure fix failed.', 'err');
                }
            }).catch(function (e) {
                setResult('Network error: ' + e.message, 'err');
            }).finally(function () {
                if (btn) { btn.disabled = false; }
                if (label) { label.innerHTML = '&#9874; Fix SEO structure'; }
            });
        }

        rootEl.addEventListener('click', function (e) {
            if (e.target.closest('[data-ai-open]')) { e.preventDefault(); open(); return; }
            if (e.target.closest('[data-ai-cancel]')) { e.preventDefault(); close(); return; }
            var gen = e.target.closest('[data-ai-generate]');
            if (gen) { e.preventDefault(); generate(gen); return; }
            var fix = e.target.closest('[data-ai-fixseo]');
            if (fix) { e.preventDefault(); fixSeo(fix); return; }
        });

        // Ctrl/Cmd+Enter in the prompt triggers Generate.
        rootEl.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter' && e.target.closest('[data-ai-prompt]')) {
                e.preventDefault();
                var gen = el('[data-ai-generate]');
                if (gen) { generate(gen); }
            }
        });
    };
}());

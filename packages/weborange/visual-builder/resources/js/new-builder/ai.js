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
        function el(sel) { return rootEl.querySelector(sel); }

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
            var addNew = !!(el('[data-ai-mode]') && el('[data-ai-mode]').checked);
            var existing = currentHtml();
            var hasContent = !!existing;
            // Default: improve the existing content and replace the canvas with the
            // better version. "Add as a new section" appends an extra section instead.
            var replaceResult = hasContent && !addNew;
            var label = el('[data-ai-label]');

            if (btn) { btn.disabled = true; }
            if (label) { label.textContent = '✨ Generating…'; }
            setResult('Thinking… this can take a few seconds.', 'info');

            var body = { prompt: prompt };
            // Always give the AI the current canvas so it improves the REAL page
            // content instead of inventing something unrelated. ai_mode tells the
            // backend whether to rewrite it (improve) or add an extra section (append).
            if (hasContent) { body.current_html = existing; body.ai_mode = addNew ? 'append' : 'improve'; }
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
                    var done = loadResultHtml(j.html, replaceResult);
                    if (done) {
                        setResult('Done — ' + (replaceResult ? 'improved and replaced the canvas' : 'added a new section') + '. Edit it visually below.', 'ok');
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
                body: JSON.stringify({ mode: 'fix_seo', current_html: html, page_title: pageTitle() }),
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

        // The title of the currently-selected target — used to create a missing h1.
        function pageTitle() {
            var sel = rootEl.querySelector('[data-save-page]');
            if (!sel || !sel.options || sel.selectedIndex < 0) { return ''; }
            var txt = (sel.options[sel.selectedIndex].textContent || '').trim();
            var dash = txt.indexOf(' — '); // labels look like "Title — /url-path"
            return dash > -1 ? txt.slice(0, dash).trim() : txt;
        }

        // Rebuild the current canvas content in the selected template's style.
        function applyTemplate(btn) {
            var html = currentHtml();
            if (!html) { setResult('Nothing to restyle — the canvas is empty.', 'err'); return; }
            var tmpl = el('[data-ai-template]');
            var templateId = tmpl ? tmpl.value : '';
            if (!templateId) { setResult('Pick a style template from the dropdown first.', 'err'); return; }
            var label = el('[data-ai-apply-template-label]');

            if (btn) { btn.disabled = true; }
            if (label) { label.textContent = '🎨 Applying…'; }
            setResult('Rebuilding the page in the template style… this can take a few seconds.', 'info');

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ mode: 'apply_template', current_html: html, template_id: templateId, page_title: pageTitle() }),
            }).then(function (r) {
                return r.json().then(function (j) { return { ok: r.ok, body: j }; });
            }).then(function (res) {
                var j = res.body || {};
                if (j.ok && j.html) {
                    var done = loadResultHtml(j.html, true);
                    setResult(done
                        ? 'Done — restyled to the template. Review the canvas, then Save.'
                        : 'Restyled, but could not load it into the builder.', done ? 'ok' : 'err');
                } else {
                    setResult(j.error || 'Apply template failed.', 'err');
                }
            }).catch(function (e) {
                setResult('Network error: ' + e.message, 'err');
            }).finally(function () {
                if (btn) { btn.disabled = false; }
                if (label) { label.innerHTML = '&#127912; Apply template style'; }
            });
        }

        rootEl.addEventListener('click', function (e) {
            var gen = e.target.closest('[data-ai-generate]');
            if (gen) { e.preventDefault(); generate(gen); return; }
            var fix = e.target.closest('[data-ai-fixseo]');
            if (fix) { e.preventDefault(); fixSeo(fix); return; }
            var appl = e.target.closest('[data-ai-apply-template]');
            if (appl) { e.preventDefault(); applyTemplate(appl); return; }
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

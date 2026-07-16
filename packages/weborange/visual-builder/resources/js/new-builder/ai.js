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

        // ── Element-scoped AI ──────────────────────────────────────────────
        // While a target element is "attached", Generate edits ONLY that element
        // (sends its HTML, replaces just it) instead of the whole page.
        var attachedId = null;
        var attachedLabel = '';
        var lastSelection = null; // {id,label} of the node currently selected in the builder

        function nb() { return rootEl.__nb; }

        function escapeHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function renderTarget() {
            var box = el('[data-ai-target]');
            if (!box) { return; }
            if (attachedId) {
                box.className = 'text-[11px] rounded-md border border-violet-300 bg-violet-100 text-violet-800 px-2 py-1.5 leading-snug flex items-center gap-1.5';
                box.innerHTML = '<span>🎯 Editing <b>' + escapeHtml(attachedLabel || 'element') + '</b> only</span>'
                    + '<button type="button" data-ai-detach class="ml-auto text-violet-500 hover:text-violet-700" title="Edit the whole page instead">✕</button>';
            } else if (lastSelection) {
                box.className = 'text-[11px] rounded-md border border-gray-200 bg-gray-50 text-gray-600 px-2 py-1.5 leading-snug flex items-center gap-1.5';
                box.innerHTML = '<span>Selected: <b>' + escapeHtml(lastSelection.label) + '</b></span>'
                    + '<button type="button" data-ai-attach class="ml-auto text-violet-600 hover:text-violet-800 font-medium" title="Make the AI edit only this element">Edit only this →</button>';
            } else {
                box.className = 'hidden';
                box.innerHTML = '';
            }
        }

        if (nb() && typeof nb().onSelectionChange === 'function') {
            nb().onSelectionChange(function (info) {
                lastSelection = info;
                renderTarget();
            });
        }

        function generate(btn) {
            var promptEl = el('[data-ai-prompt]');
            var prompt = promptEl ? promptEl.value.trim() : '';
            if (!prompt) { setResult('Type what you want first.', 'err'); return; }

            // Element-scoped mode: an element is attached → edit only that element.
            var scoped = !!attachedId;
            var scopedHtml = '';
            if (scoped) {
                scopedHtml = (nb() && nb().getNodeHtml) ? (nb().getNodeHtml(attachedId) || '') : '';
                if (!scopedHtml) {
                    attachedId = null; renderTarget();
                    setResult('The targeted element is gone — switched to editing the whole page. Try again.', 'err');
                    return;
                }
            }

            var addNew = !scoped && !!(el('[data-ai-mode]') && el('[data-ai-mode]').checked);
            var existing = scoped ? scopedHtml : currentHtml();
            var hasContent = !!existing;
            // Default: improve the existing content and replace the canvas. "Add as a
            // new section" appends. In scoped mode we always replace the element.
            var replaceResult = hasContent && !addNew;
            var label = el('[data-ai-label]');

            if (btn) { btn.disabled = true; }
            if (label) { label.textContent = '✨ Generating…'; }
            setResult(scoped ? 'Editing the selected element…' : 'Thinking… this can take a few seconds.', 'info');

            var body = { prompt: prompt };
            if (hasContent) {
                body.current_html = existing;
                body.ai_mode = scoped ? 'element' : (addNew ? 'append' : 'improve');
            }
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
                    var done;
                    if (scoped) {
                        var newId = (nb() && nb().replaceNodeHtml) ? nb().replaceNodeHtml(attachedId, j.html) : null;
                        done = !!newId;
                        if (done) { attachedId = newId; }
                        renderTarget();
                    } else {
                        done = loadResultHtml(j.html, replaceResult);
                    }
                    if (done) {
                        setResult(scoped
                            ? 'Done — updated the selected element.'
                            : ('Done — ' + (replaceResult ? 'improved and replaced the canvas' : 'added a new section') + '. Edit it visually below.'), 'ok');
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
            if (e.target.closest('[data-ai-attach]')) {
                e.preventDefault();
                if (lastSelection) { attachedId = lastSelection.id; attachedLabel = lastSelection.label; renderTarget(); }
                return;
            }
            if (e.target.closest('[data-ai-detach]')) {
                e.preventDefault();
                attachedId = null; attachedLabel = ''; renderTarget();
                return;
            }
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

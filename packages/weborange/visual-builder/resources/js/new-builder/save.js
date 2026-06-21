/**
 * new-builder save: persist the builder output as a raw-HTML section on an
 * existing page (POST admin/new-builder/save).
 *
 * Decoupled from the core: it reads the current HTML straight from the HTML
 * pane textarea ([data-pane="html"]), which the controller keeps in sync on
 * every model change. Config (endpoint URL + CSRF token) comes from
 * [data-save-config] in the blade.
 *
 * NB.createSave(rootEl) -> wires the Save panel.
 */
(function () {
    'use strict';

    var NB = window.NB;

    NB.createSave = function createSave(rootEl) {
        var cfg = rootEl.querySelector('[data-save-config]');
        if (!cfg) { return; }
        var url = cfg.getAttribute('data-save-url');
        var sectionsUrl = cfg.getAttribute('data-sections-url');
        var csrf = cfg.getAttribute('data-csrf');
        var panel = rootEl.querySelector('[data-save-panel]');
        var result = rootEl.querySelector('[data-save-result]');
        var sectionHtml = {}; // section id -> stored html (for load-back)
        var migrateMode = false; // opened with ?target= → save directly, panel stays closed

        function currentHtml() {
            var ta = rootEl.querySelector('[data-pane="html"]');
            return ta ? ta.value.trim() : '';
        }

        function open() {
            if (panel) { panel.classList.remove('hidden'); }
            refreshSections();
        }

        function close() {
            if (panel) { panel.classList.add('hidden'); }
        }

        function setResult(html, ok) {
            if (!result) { return; }
            result.className = 'nb-save-result ' + (ok ? 'nb-save-ok' : 'nb-save-err');
            result.innerHTML = html;
        }

        /** Lightweight self-contained toast (no external dependency). */
        function toast(message, ok) {
            var host = document.querySelector('[data-nb-toasts]');
            if (!host) {
                host = document.createElement('div');
                host.setAttribute('data-nb-toasts', '');
                host.style.cssText = 'position:fixed;top:16px;right:16px;z-index:99999;display:flex;flex-direction:column;gap:8px;pointer-events:none';
                document.body.appendChild(host);
            }
            var el = document.createElement('div');
            el.style.cssText = 'min-width:200px;max-width:360px;padding:10px 14px;border-radius:8px;font:600 13px/1.4 ui-sans-serif,system-ui,sans-serif;' +
                'color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.18);opacity:0;transform:translateY(-8px);transition:opacity .2s,transform .2s;' +
                'background:' + (ok ? '#059669' : '#dc2626');
            el.textContent = (ok ? '✓ ' : '✕ ') + message;
            host.appendChild(el);
            requestAnimationFrame(function () { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; });
            setTimeout(function () {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                setTimeout(function () { el.remove(); }, 220);
            }, 2600);
        }

        function escapeHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function pageValue() {
            var el = rootEl.querySelector('[data-save-page]');
            return el ? el.value : '';
        }

        function sectionValue() {
            var el = rootEl.querySelector('[data-save-section]');
            return el ? el.value : '';
        }

        function syncLoadBtn() {
            var loadBtn = rootEl.querySelector('[data-save-load]');
            if (loadBtn) { loadBtn.disabled = !sectionValue(); }
        }

        /** Point the toolbar "View page" button at the selected target's live URL. */
        function syncViewPage() {
            var btn = rootEl.querySelector('[data-view-page]');
            var sel = rootEl.querySelector('[data-save-page]');
            if (!btn || !sel) { return; }
            var opt = sel.options[sel.selectedIndex];
            var url = opt ? (opt.getAttribute('data-url') || '') : '';
            if (url) {
                btn.href = url;
                btn.classList.remove('hidden');
                btn.classList.add('inline-flex');
            } else {
                btn.href = '#';
                btn.classList.add('hidden');
                btn.classList.remove('inline-flex');
            }
        }

        /**
         * Hide the migration-only options (Replace page content) when editing an
         * existing section in place — they only make sense for a fresh save.
         */
        function updateReplaceVisibility() {
            var replaceRow = rootEl.querySelector('[data-save-replace]');
            replaceRow = replaceRow ? replaceRow.closest('label') : null;
            if (!replaceRow) { return; }
            var editingExisting = !!sectionValue();
            replaceRow.style.display = editingExisting ? 'none' : '';
            if (editingExisting) {
                var box = rootEl.querySelector('[data-save-replace]');
                if (box) { box.checked = false; }
            }
            // Update the submit button label to reflect update vs create.
            var btn = rootEl.querySelector('[data-save-submit]');
            if (btn) { btn.textContent = editingExisting ? 'Save changes' : 'Save section'; }
        }

        /** Fetch the page's existing html-sections into the Section dropdown. */
        function refreshSections(selectId, autoSingle) {
            var sel = rootEl.querySelector('[data-save-section]');
            var pageId = pageValue();
            if (!sel || !sectionsUrl || !pageId) { return; }
            fetch(sectionsUrl + '?target_id=' + encodeURIComponent(pageId), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    var list = (j && j.sections) || [];
                    sectionHtml = {};
                    var html = '<option value="">➕ New section</option>';
                    list.forEach(function (s) {
                        sectionHtml[s.id] = s.html || '';
                        html += '<option value="' + s.id + '">' + escapeHtml(s.name) + '</option>';
                    });
                    sel.innerHTML = html;
                    if (selectId) {
                        sel.value = String(selectId);
                    } else if (autoSingle && list.length === 1) {
                        // Editing a page with a single section → select it so Save
                        // updates it in place instead of appending a duplicate.
                        sel.value = String(list[0].id);
                        var nameEl = rootEl.querySelector('[data-save-name]');
                        if (nameEl) { nameEl.value = list[0].name; }
                        setResult('Editing section “' + escapeHtml(list[0].name) + '” — Save updates it in place.', true);
                    }
                    syncLoadBtn();
                    updateReplaceVisibility();
                })
                .catch(function () { /* leave the dropdown as-is on failure */ });
        }

        /** Load the selected section's stored HTML back into the builder. */
        function loadSelected() {
            var id = sectionValue();
            if (!id) { setResult('Select an existing section to load.', false); return; }
            var html = sectionHtml[id];
            var Core = window.NewBuilderCore;
            if (!rootEl.__nb || typeof rootEl.__nb.loadRoots !== 'function' || !Core) { return; }
            try {
                rootEl.__nb.loadRoots(Core.htmlToJsonRoots(html || ''));
                var nameEl = rootEl.querySelector('[data-save-name]');
                var sel = rootEl.querySelector('[data-save-section]');
                if (nameEl && sel) { nameEl.value = sel.options[sel.selectedIndex].textContent.trim(); }
                setResult('Loaded section into the builder. Edit, then Save to update it.', true);
            } catch (e) {
                setResult('Could not parse the saved HTML: ' + escapeHtml(e.message), false);
            }
        }

        function submit(btn) {
            var pageSel = rootEl.querySelector('[data-save-page]');
            var nameEl = rootEl.querySelector('[data-save-name]');
            var convertEl = rootEl.querySelector('[data-save-convert]');
            var pageId = pageSel ? pageSel.value : '';
            if (!pageId) { setResult('Pick a page first.', false); return; }
            var html = currentHtml();
            if (!html) { setResult('Nothing to save — the builder is empty.', false); return; }

            var replaceEl = rootEl.querySelector('[data-save-replace]');
            var body = {
                target_id: pageId,
                section_id: sectionValue() || null,
                html: html,
                name: nameEl ? nameEl.value : '',
                convert: convertEl && convertEl.checked ? 1 : 0,
                replace: replaceEl && replaceEl.checked ? 1 : 0,
            };

            var loopOn = rootEl.querySelector('[data-save-loop]');
            if (loopOn && loopOn.checked) {
                var srcEl = rootEl.querySelector('[data-loop-source]');
                if (!srcEl || !srcEl.value) { setResult('Pick a loop source (collection).', false); return; }
                var order = (rootEl.querySelector('[data-loop-order]').value || 'created_at|desc').split('|');
                body.loop_source = srcEl.value;
                body.loop_columns = parseInt(rootEl.querySelector('[data-loop-columns]').value, 10) || 3;
                body.loop_limit = parseInt(rootEl.querySelector('[data-loop-limit]').value, 10) || 12;
                body.loop_order_by = order[0];
                body.loop_order_dir = order[1] || 'desc';
                body.loop_heading = rootEl.querySelector('[data-loop-heading]').value || '';
            }

            if (btn) { btn.disabled = true; }
            setResult('Saving…', true);

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(body),
            }).then(function (r) {
                return r.json().then(function (j) { return { ok: r.ok, body: j }; });
            }).then(function (res) {
                var j = res.body || {};
                if (j.success) {
                    var live = j.url ? ' &middot; <a href="' + j.url + '" target="_blank" class="underline font-semibold">View live &#8599;</a>' : '';
                    var edit = j.edit_url ? ' &middot; <a href="' + j.edit_url + '" target="_blank" class="underline">Open page editor</a>' : '';
                    setResult(escapeHtml(j.message || 'Saved.') + live + edit, true);
                    toast(j.message || 'Saved', true);
                    refreshSections(j.section_id); // reflect the new/updated section, keep it selected
                } else if (j.needs_convert) {
                    setResult(escapeHtml(j.message || 'Page is not in sections mode.') +
                        ' Tick the checkbox below and Save again.', false);
                    if (convertEl) { convertEl.checked = false; }
                } else {
                    setResult(escapeHtml(j.message || 'Save failed.'), false);
                }
            }).catch(function (e) {
                setResult('Network error: ' + escapeHtml(e.message), false);
            }).finally(function () {
                if (btn) { btn.disabled = false; }
            });
        }

        rootEl.addEventListener('click', function (e) {
            if (e.target.closest('[data-save-open]')) {
                e.preventDefault();
                // Primary Save: in migrate/edit mode (target preselected) or when the
                // panel is already open, this saves directly. In generic mode it opens
                // the panel first so the user can pick a target/section.
                var openBtn = e.target.closest('[data-save-open]');
                var panelVisible = panel && !panel.classList.contains('hidden');
                if (pageValue() && (migrateMode || panelVisible)) {
                    submit(openBtn);
                } else {
                    open();
                }
            } else if (e.target.closest('[data-save-cancel]')) {
                e.preventDefault();
                close();
            } else if (e.target.closest('[data-save-load]')) {
                e.preventDefault();
                loadSelected();
            } else if (e.target.closest('[data-save-submit]')) {
                e.preventDefault();
                submit(e.target.closest('[data-save-submit]'));
            }
        });

        rootEl.addEventListener('change', function (e) {
            if (e.target.closest('[data-save-page]')) {
                refreshSections(); // new page → reload its sections (resets to "New section")
                syncViewPage();
            } else if (e.target.closest('[data-save-section]')) {
                syncLoadBtn();
                updateReplaceVisibility();
            } else if (e.target.closest('[data-save-loop]')) {
                var box = rootEl.querySelector('[data-save-loop-config]');
                if (box) { box.classList.toggle('hidden', !e.target.checked); }
            }
        });

        // Migrate flow: when opened with ?target=, preselect that page + open the
        // Save panel so it's clear where edits go.
        var preselect = cfg.getAttribute('data-preselect-target');
        if (preselect) {
            // Edit mode: keep the panel closed (don't nag on every load); preselect
            // the page + its single section silently so the top-right Save writes
            // straight back to it in one click.
            migrateMode = true;
            var pageSel = rootEl.querySelector('[data-save-page]');
            if (pageSel) { pageSel.value = preselect; }
            refreshSections(null, true);
        }

        // Point the toolbar "View page" button at whatever target is selected.
        syncViewPage();
    };
}());

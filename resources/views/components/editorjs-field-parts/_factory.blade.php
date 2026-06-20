function editorjsField(config) {
    return {
        editor: null,
        undo: null,
        dragDrop: null,
        uid: config.uid,
        wireModel: config.wireModel,
        /** When false, NO background server autosave — content persists only on
            an explicit Save click. (Set per-instance; on by default.) */
        autosave: config.autosave !== false,
        initialValue: config.initialValue || '',
        uploadImageUrl: config.uploadImageUrl,
        fetchImageUrl: (window._editorjsField_fetchUrl = config.fetchImageUrl),
        uploadFileUrl: config.uploadFileUrl,
        mediaListUrl: (window._editorjsField_mediaUrl = config.mediaListUrl || ''),
        csrfToken: (window._editorjsField_csrf = config.csrfToken),
        placeholder: config.placeholder,

        /** Save state: idle | saving | saved | error — drives the indicator UI */
        saveState: 'idle',
        _saveStateTimer: null,
        setSaveState(state) {
            this.saveState = state;
            clearTimeout(this._saveStateTimer);
            if (state === 'saved' || state === 'error') {
                this._saveStateTimer = setTimeout(() => { this.saveState = 'idle'; }, state === 'saved' ? 1800 : 4000);
            }
        },

        /** Local autosave: snapshot every 5s to localStorage. Recovered on init if newer than initialValue. */
        _autosaveKey() { return 'ejs:autosave:' + (this.wireModel || this.uid); },
        _autosaveStash(data) {
            try {
                const payload = { t: Date.now(), data };
                localStorage.setItem(this._autosaveKey(), JSON.stringify(payload));
            } catch (_) { /* quota / private mode */ }
        },
        _autosaveRead() {
            try {
                const raw = localStorage.getItem(this._autosaveKey());
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                if (!parsed || !parsed.t || !parsed.data) return null;
                // Ignore snapshots older than 7 days
                if (Date.now() - parsed.t > 7 * 24 * 60 * 60 * 1000) return null;
                return parsed;
            } catch (_) { return null; }
        },
        _autosaveClear() {
            try { localStorage.removeItem(this._autosaveKey()); } catch (_) {}
        },
        showRecovery: false,
        showRecoveryPreview: false,
        recoveryAge: '',
        _recoveryData: null,
        previewCurrentHtml: '',
        previewSnapshotHtml: '',

        /** Convert EditorJS output data → a simple HTML preview string. Not a
         *  full renderer (tunes, columns, container nesting are flattened), just
         *  enough for the user to read what's in there and decide. */
        _editorJsToPreviewHtml(data) {
            if (!data || !Array.isArray(data.blocks)) return '<p class="text-gray-400 italic">(empty)</p>';
            const esc = (s) => String(s ?? '').replace(/[<>&]/g, (c) => ({ '<':'&lt;', '>':'&gt;', '&':'&amp;' }[c]));
            const renderBlocks = (blocks) => blocks.map((b) => {
                const d = b.data || {};
                switch (b.type) {
                    case 'paragraph': return `<p>${d.text || ''}</p>`;
                    case 'header': {
                        const lvl = Math.min(Math.max(parseInt(d.level || 2, 10), 1), 6);
                        return `<h${lvl}>${d.text || ''}</h${lvl}>`;
                    }
                    case 'list': {
                        const tag = d.style === 'ordered' ? 'ol' : 'ul';
                        const items = (d.items || []).map(i => {
                            if (typeof i === 'string') return `<li>${i}</li>`;
                            return `<li>${i.content || i.text || ''}</li>`;
                        }).join('');
                        return `<${tag}>${items}</${tag}>`;
                    }
                    case 'checklist': {
                        const items = (d.items || []).map(i => `<li>${i.checked ? '☑' : '☐'} ${i.text || ''}</li>`).join('');
                        return `<ul class="list-none pl-0">${items}</ul>`;
                    }
                    case 'quote':     return `<blockquote>${d.text || ''}${d.caption ? `<br><cite>— ${d.caption}</cite>` : ''}</blockquote>`;
                    case 'code':      return `<pre><code>${esc(d.code || '')}</code></pre>`;
                    case 'delimiter': return '<hr>';
                    case 'image':     return `<figure><img src="${esc(d.file?.url || d.url || '')}" alt="${esc(d.caption || '')}" class="max-w-full">${d.caption ? `<figcaption>${esc(d.caption)}</figcaption>` : ''}</figure>`;
                    case 'table': {
                        const rows = (d.content || []).map(r => `<tr>${r.map(c => `<td class="border px-2 py-1">${c || ''}</td>`).join('')}</tr>`).join('');
                        return `<table class="border-collapse">${rows}</table>`;
                    }
                    case 'raw':       return `<div>${d.html || ''}</div>`;
                    case 'liveHtml':  return `<div>${d.html || ''}</div>`;
                    case 'space':     return `<div style="height:${esc(d.height || '2rem')}"></div>`;
                    case 'container': return `<section class="border border-dashed border-purple-300 rounded p-3 my-2"><div class="text-[10px] text-purple-500 uppercase mb-2">Container</div>${renderBlocks(d.content?.blocks || [])}</section>`;
                    case 'columns':   return `<div class="grid gap-3" style="grid-template-columns:repeat(${d.cols || 2},1fr)">${(d.columns || []).map(c => `<div class="border border-dashed border-gray-300 rounded p-2">${c}</div>`).join('')}</div>`;
                    default:          return `<div class="text-xs text-gray-400 italic my-1">[${esc(b.type)} block]</div>`;
                }
            }).join('\n');
            return renderBlocks(data.blocks);
        },

        async previewRecovery() {
            this.previewSnapshotHtml = this._editorJsToPreviewHtml(this._recoveryData);
            this.previewCurrentHtml = '<p class="text-gray-400 italic">(loading current state…)</p>';
            this.showRecoveryPreview = true;
            try {
                if (this.editor && this.editor !== '_loading_' && typeof this.editor.save === 'function') {
                    const current = await this.editor.save();
                    this.previewCurrentHtml = this._editorJsToPreviewHtml(current);
                }
            } catch (e) {
                this.previewCurrentHtml = '<p class="text-red-500 italic">(could not read current state)</p>';
            }
        },

        recoverContent() {
            if (!this._recoveryData || !this.editor || this.editor === '_loading_') {
                this.showRecovery = false;
                return;
            }
            try {
                this.editor.render(this._recoveryData).then(() => {
                    this.toast('Unsaved changes restored', 'success');
                    this.showRecovery = false;
                    this._recoveryData = null;
                });
            } catch (e) {
                console.warn('[EditorJS] recovery render failed:', e);
                this.toast('Could not restore changes', 'error');
                this.showRecovery = false;
            }
        },
        dismissRecovery() {
            this.showRecovery = false;
            this._recoveryData = null;
            this._autosaveClear();
        },
        _autosaveTick() {
            if (!this.editor || this.editor === '_loading_') return;
            this.editor.save().then(data => {
                if (data && Array.isArray(data.blocks) && data.blocks.length > 0) {
                    this._autosaveStash(data);
                }
            }).catch(() => {});
        },
        _formatAge(ms) {
            const s = Math.floor(ms / 1000);
            if (s < 60) return s + 's ago';
            if (s < 3600) return Math.floor(s / 60) + 'm ago';
            if (s < 86400) return Math.floor(s / 3600) + 'h ago';
            return Math.floor(s / 86400) + 'd ago';
        },

        /**
         * Parse a string of HTML and insert the resulting blocks at the current
         * caret position. Replaces the current block if it is empty (so the
         * brand-new paragraph EditorJS auto-inserts gets overwritten on first
         * paste into an empty editor).
         */
        async insertHtmlAsBlocks(html) {
            if (!this.editor || this.editor === '_loading_' || typeof this.editor.blocks?.insert !== 'function') return false;
            // Styled markup (has class=/style=) → ONE "HTML (live)" block that renders
            // the full design (backgrounds, colors, layout) and is editable in place:
            // click text to edit, click image → media picker. Plain markup → clean
            // native blocks via the parser.
            let parsed;
            if (window.LiveHtmlTool && /\b(class|style)\s*=/.test(html)) {
                parsed = { blocks: [{ type: 'liveHtml', data: { html: html } }] };
            } else {
                parsed = window._veHtmlToBlocks(html);
            }
            if (!parsed || !parsed.blocks || !parsed.blocks.length) return false;
            let insertIdx;
            try {
                insertIdx = this.editor.blocks.getCurrentBlockIndex();
                if (typeof insertIdx !== 'number' || insertIdx < 0) {
                    insertIdx = this.editor.blocks.getBlocksCount();
                }
            } catch (e) {
                insertIdx = 0;
            }
            let replaceCurrent = false;
            try {
                const current = this.editor.blocks.getBlockByIndex(insertIdx);
                if (current) {
                    // Guard with a timeout: a container block's nested-editor save()
                    // can hang and would otherwise freeze the whole paste.
                    const data = await Promise.race([
                        Promise.resolve(current.save?.()),
                        new Promise(r => setTimeout(() => r(null), 400)),
                    ]);
                    const isEmpty = !data || !data.data || (
                        (typeof data.data.text === 'string' && data.data.text.trim() === '') &&
                        !data.data.items?.length && !data.data.code
                    );
                    if (isEmpty) replaceCurrent = true;
                }
            } catch (e) { /* keep replaceCurrent=false */ }

            for (let i = 0; i < parsed.blocks.length; i++) {
                const b = parsed.blocks[i];
                const idx = insertIdx + (replaceCurrent ? 0 : 1) + i;
                try {
                    this.editor.blocks.insert(b.type, b.data, {}, idx, replaceCurrent && i === 0);
                } catch (e) {
                    console.warn('[paste] insert failed for block', b.type, e);
                }
            }
            return true;
        },

        /** Open media library picker; on selection, insert an image block at end. */
        pickFromMediaLibrary() {
            if (!this.editor || this.editor === '_loading_') return;
            if (typeof window.editorjsMediaPicker !== 'function') {
                this.toast('Media picker not available', 'error');
                return;
            }
            const editor = this.editor;
            window.editorjsMediaPicker({
                url: this.mediaListUrl,
                onPick: ({ url, name }) => {
                    try {
                        const idx = (editor.blocks.getBlocksCount?.() || 0);
                        editor.blocks.insert('image', {
                            file: { url },
                            caption: name || '',
                            withBorder: false,
                            withBackground: false,
                            stretched: false,
                        }, {}, idx, true);
                    } catch (e) {
                        this.toast('Failed to insert image: ' + e.message, 'error');
                    }
                },
            });
        },

        /** Lightweight floating toast for upload/network errors that EditorJS swallows. */
        toast(message, type = 'info') {
            try {
                const t = document.createElement('div');
                const colors = type === 'error'
                    ? 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca'
                    : (type === 'success' ? 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0' : 'background:#f8fafc;color:#0f172a;border:1px solid #e2e8f0');
                t.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,.08);z-index:99999;max-width:360px;' + colors;
                t.textContent = message;
                document.body.appendChild(t);
                setTimeout(() => { t.style.transition = 'opacity .3s ease'; t.style.opacity = '0'; }, 3500);
                setTimeout(() => t.remove(), 4000);
            } catch (_) {}
        },

        parseInitialData() {
            if (!this.initialValue || this.initialValue === '') return null;

            // Defensive: initialValue can arrive as either a STRING (legacy
            // double-encoded shape that survived the healer) OR an OBJECT
            // (the proper {time, blocks, version} EditorJS payload the
            // PageCompiler now stores). Calling .trim() on an object throws,
            // which made the editor mount empty. Handle both shapes upfront.
            if (typeof this.initialValue === 'object' && this.initialValue !== null) {
                if (Array.isArray(this.initialValue.blocks)) {
                    return this.initialValue;
                }
                // Object but not a valid EditorJS shape — bail to empty.
                return null;
            }

            const val = String(this.initialValue).trim();

            // Try EditorJS JSON — STRICT: must look like an EditorJS save, not just any JSON.
            // Requires: blocks array AND (time OR version) — otherwise fall through to text/HTML parse.
            if (val.startsWith('{') && val.endsWith('}')) {
                try {
                    const parsed = JSON.parse(val);
                    if (parsed && Array.isArray(parsed.blocks) && (parsed.time !== undefined || parsed.version !== undefined)) {
                        return parsed;
                    }
                } catch (e) {}
            }

            // Legacy HTML — convert common tags to proper blocks. Preserve text-align tunes.
            if (val.startsWith('<') || val.includes('<p') || val.includes('<h')) {
                try {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = val;
                    const blocks = [];

                    const readAlignment = (el) => {
                        const ta = (el.style && el.style.textAlign) ? el.style.textAlign.toLowerCase() : '';
                        return ['left', 'center', 'right', 'justify'].includes(ta) ? ta : null;
                    };
                    const wrapTune = (block, align) => {
                        if (align) block.tunes = { textAlignment: { alignment: align } };
                        return block;
                    };

                    tmp.childNodes.forEach(node => {
                        if (node.nodeType !== Node.ELEMENT_NODE) {
                            if (node.textContent.trim()) {
                                blocks.push({ type: 'paragraph', data: { text: node.textContent } });
                            }
                            return;
                        }
                        const tag = node.tagName.toLowerCase();
                        const align = readAlignment(node);
                        // Preserve every class/id/style/data-* attribute by routing styled nodes
                        // to a raw block (keeps full HTML, editable as HTML). Plain tags fall
                        // through to dedicated editable blocks.
                        const attrs = node.attributes ? Array.from(node.attributes) : [];
                        const hasStylingAttrs = attrs.some(a =>
                            a.name === 'class' || a.name === 'id' || a.name === 'style' || a.name.indexOf('data-') === 0
                        );

                        if (hasStylingAttrs && tag !== 'img') {
                            blocks.push({ type: 'raw', data: { html: node.outerHTML } });
                        } else if (tag === 'p' || tag === 'div') {
                            if (node.innerHTML.trim()) {
                                blocks.push(wrapTune({ type: 'paragraph', data: { text: node.innerHTML } }, align));
                            }
                        } else if (/^h[1-6]$/.test(tag)) {
                            blocks.push(wrapTune({ type: 'header', data: { text: node.innerHTML, level: parseInt(tag[1]) } }, align));
                        } else if (tag === 'ul' || tag === 'ol') {
                            const items = Array.from(node.querySelectorAll(':scope > li')).map(li => ({ content: li.innerHTML, items: [] }));
                            if (items.length) { blocks.push(wrapTune({ type: 'list', data: { style: tag === 'ul' ? 'unordered' : 'ordered', items } }, align)); }
                        } else if (tag === 'blockquote') {
                            blocks.push(wrapTune({ type: 'quote', data: { text: node.innerHTML, caption: '', alignment: 'left' } }, align));
                        } else if (tag === 'pre') {
                            const codeEl = node.querySelector('code') || node;
                            blocks.push({ type: 'code', data: { code: codeEl.textContent || '' } });
                        } else if (tag === 'hr') {
                            blocks.push({ type: 'delimiter', data: {} });
                        } else if (tag === 'img') {
                            blocks.push({ type: 'image', data: { file: { url: node.getAttribute('src') || '' }, caption: node.getAttribute('alt') || '', withBorder: false, withBackground: false, stretched: false } });
                        } else if (tag === 'figure') {
                            const fImg = node.querySelector('img');
                            const fCap = node.querySelector('figcaption');
                            if (fImg) {
                                blocks.push({ type: 'image', data: { file: { url: fImg.getAttribute('src') || '' }, caption: (fCap ? fCap.innerHTML : (fImg.getAttribute('alt') || '')), withBorder: false, withBackground: false, stretched: false } });
                            } else {
                                blocks.push({ type: 'raw', data: { html: node.outerHTML } });
                            }
                        } else {
                            // table, iframe, video, audio, section, article, header, footer,
                            // aside, nav, details, embed, svg, … — preserved verbatim.
                            blocks.push({ type: 'raw', data: { html: node.outerHTML } });
                        }
                    });
                    if (blocks.length) { return { blocks }; }
                } catch (e) {}
                return { blocks: [{ type: 'raw', data: { html: val } }] };
            }

            // Plain text
            if (val.length > 0) {
                return {
                    blocks: [{ type: 'paragraph', data: { text: val } }]
                };
            }

            return null;
        },

        async init() {
            if (this.editor) return;
            this.editor = '_loading_'; // sentinel blocks concurrent calls during await gap
            this._initAttempts = (this._initAttempts || 0) + 1;

            await this.$nextTick();

            const holderEl = document.getElementById(this.uid);
            // Wait for the FULL editorjs script bundle — not just core. The loader sets
            // window._editorjsLoaded = true after every tool (Header, NestedList, …) is
            // loaded. Proceeding earlier hits "Header is not defined" inside the tools cfg.
            if (!holderEl || !window.EditorJS || !window._editorjsLoaded || typeof Header === 'undefined') {
                this.editor = null; // reset so retry can proceed
                if (this._initAttempts > 30) {
                    // ~6 seconds of retries — give up and show error UI
                    if (holderEl) {
                        holderEl.innerHTML = '<div style="padding:1rem;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:13px">Editor failed to load. Please refresh the page or check your connection.</div>';
                    }
                    return;
                }
                setTimeout(() => this.init(), 200);
                return;
            }
            this._initAttempts = 0; // reset for future re-inits

            // Destroy any stale EditorJS instance on this DOM node
            if (holderEl._editorjsInstance) {
                try { await holderEl._editorjsInstance.destroy(); } catch (_) {}
                holderEl._editorjsInstance = null;
            }
            holderEl.querySelectorAll('.codex-editor').forEach(el => el.remove());

            const self = this;
            const initialData = this.parseInitialData();

            // Shared image tool config. Defined once here and exposed as
            // window.__editorImageTool so NESTED editors (ContainerTool,
            // ColumnsTool in _tools.blade.php) register the exact same image
            // uploader — that's how images become addable inside a column /
            // container, not just at the top level.
            const __imageTool = {
                class: ImageTool,
                tunes: window.ImageSizeTune ? ['imageSize'] : [],
                config: {
                    uploader: {
                        async uploadByFile(file) {
                            if (file.size > 16 * 1024 * 1024) {
                                self.toast('Image too large (max 16 MB)', 'error');
                                return { success: 0, message: 'File too large' };
                            }
                            if (!/^image\//.test(file.type)) {
                                self.toast('Selected file is not an image', 'error');
                                return { success: 0, message: 'Not an image' };
                            }
                            const form = new FormData();
                            form.append('image', file);
                            try {
                                const res = await fetch(self.uploadImageUrl, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                    body: form,
                                });
                                if (!res.ok) {
                                    let msg = 'Upload failed (' + res.status + ')';
                                    try {
                                        const err = await res.json();
                                        if (err && err.message) msg = err.message;
                                    } catch (_) {}
                                    self.toast(msg, 'error');
                                    return { success: 0, message: msg };
                                }
                                const data = await res.json();
                                if (!data || data.success !== 1) {
                                    self.toast(data?.message || 'Upload failed', 'error');
                                }
                                return data;
                            } catch (e) {
                                self.toast('Network error during upload', 'error');
                                return { success: 0, message: 'Network error' };
                            }
                        },
                        async uploadByUrl(url) {
                            try {
                                const res = await fetch(self.fetchImageUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': self.csrfToken,
                                    },
                                    body: JSON.stringify({ url }),
                                });
                                if (!res.ok) {
                                    let msg = 'Fetch failed (' + res.status + ')';
                                    try { const err = await res.json(); if (err && err.message) msg = err.message; } catch (_) {}
                                    self.toast(msg, 'error');
                                    return { success: 0, message: msg };
                                }
                                return await res.json();
                            } catch (e) {
                                self.toast('Network error fetching image', 'error');
                                return { success: 0, message: 'Network error' };
                            }
                        },
                    },
                },
            };
            window.__editorImageTool = __imageTool;

            this.editor = new EditorJS({
                holder: this.uid,
                placeholder: this.placeholder,
                data: initialData || undefined,
                minHeight: 0,

                // Explicit inline toolbar list — ensures ColorTool, InlineAlignmentTool
                // and other custom inline tools always show alongside the built-in ones.
                inlineToolbar: ['bold', 'italic', 'underline', 'marker', 'inlineCode',
                    ...(window.ColorTool ? ['color'] : []),
                    ...(window.InlineAlignmentTool ? ['inlineAlignment'] : []),
                    'link'],

                tools: {
                    // Block tools
                    ...(window.ParagraphWithInlineTools ? { paragraph: { class: window.ParagraphWithInlineTools, inlineToolbar: true } } : {}),
                    header: {
                        class: (window.HeaderWithInlineTools || Header),
                        inlineToolbar: true,
                        config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 },
                    },
                    list: {
                        class: NestedList,
                        inlineToolbar: true,
                        config: { defaultStyle: 'unordered' },
                    },
                    checklist: {
                        class: Checklist,
                        inlineToolbar: true,
                    },
                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        config: { quotePlaceholder: 'Enter a quote', captionPlaceholder: 'Quote author' },
                    },
                    code: CodeTool,
                    delimiter: Delimiter,
                    warning: {
                        class: Warning,
                        inlineToolbar: true,
                        config: { titlePlaceholder: 'Title', messagePlaceholder: 'Message' },
                    },
                    table: {
                        class: Table,
                        inlineToolbar: true,
                        config: { rows: 2, cols: 3, withHeadings: true },
                    },
                    image: __imageTool,
                    attaches: {
                        class: AttachesTool,
                        config: {
                            uploader: {
                                async uploadByFile(file) {
                                    if (file.size > 32 * 1024 * 1024) {
                                        self.toast('File too large (max 32 MB)', 'error');
                                        return { success: 0, message: 'File too large' };
                                    }
                                    const form = new FormData();
                                    form.append('file', file);
                                    try {
                                        const res = await fetch(self.uploadFileUrl, {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                            body: form,
                                        });
                                        if (!res.ok) {
                                            let msg = 'Upload failed (' + res.status + ')';
                                            try { const err = await res.json(); if (err && err.message) msg = err.message; } catch (_) {}
                                            self.toast(msg, 'error');
                                            return { success: 0, message: msg };
                                        }
                                        return await res.json();
                                    } catch (e) {
                                        self.toast('Network error during upload', 'error');
                                        return { success: 0, message: 'Network error' };
                                    }
                                },
                            },
                        },
                    },
                    embed: {
                        class: Embed,
                        config: { services: { youtube: true, vimeo: true, coub: true, imgur: true, gfycat: true, twitch: true, twitter: true } },
                    },
                    linkTool: {
                        class: LinkTool,
                        config: { endpoint: self.fetchImageUrl },
                    },
                    raw: RawTool,

                    // Inline tools
                    marker: Marker,
                    inlineCode: InlineCode,
                    underline: Underline,
                    ...(window.ColorTool ? { color: { class: window.ColorTool } } : {}),

                    // Inline alignment (multi-block support — select text across lines, click button)
                    ...(window.InlineAlignmentTool ? { inlineAlignment: { class: window.InlineAlignmentTool } } : {}),

                    // Block tune — CSS classes per block
                    ...(window.BlockClassesTune ? { blockClasses: window.BlockClassesTune } : {}),

                    // Block tune — text alignment (left / center / right / justify)
                    ...(window.TextAlignmentTune ? { textAlignment: window.TextAlignmentTune } : {}),

                    // Block tune — per-image resize (25/50/75/100% or custom)
                    ...(window.ImageSizeTune ? { imageSize: window.ImageSizeTune } : {}),

                    // Columns block (custom)
                    ...(window.ColumnsTool ? { columns: { class: window.ColumnsTool } } : {}),

                    // Container block (custom) — responsive max-width
                    ...(window.ContainerTool ? { container: { class: window.ContainerTool } } : {}),

                    // Live HTML block (custom) — renders pasted markup styled & editable in place
                    ...(window.LiveHtmlTool ? { liveHtml: { class: window.LiveHtmlTool } } : {}),

                    // Space block (custom) — vertical spacer
                    ...(window.SpaceTool ? { space: { class: window.SpaceTool } } : {}),

                    // Section Embed (custom) — embeds any Section/Template as a looping block
                    ...(window.SectionEmbedTool ? { sectionEmbed: { class: window.SectionEmbedTool } } : {}),
                },
                tunes: [
                    ...(window.TextAlignmentTune ? ['textAlignment'] : []),
                    ...(window.BlockClassesTune ? ['blockClasses'] : []),
                ],

                onChange: (api, event) => {
                    // While we're restoring a history snapshot, editor.blocks.render()
                    // fires onChange — ignore it so the restore doesn't get recorded
                    // as a brand-new edit (which would corrupt undo/redo).
                    if (self._restoring) { return; }

                    // Undo history: schedule a snapshot. Trailing 500ms idle, but
                    // FORCED at least every ~1.8s during continuous fast typing so
                    // long bursts still get intermediate undo steps (otherwise a
                    // whole paragraph typed without pausing = a single undo step).
                    self._histSchedule();

                    // 600ms after last keystroke: snapshot to localStorage (cheap,
                    // crash protection).
                    self.setSaveState('saving');
                    clearTimeout(self._saveTimer);
                    self._saveTimer = setTimeout(async () => {
                        try {
                            const outputData = await self.editor.save();
                            self._autosaveStash(outputData);
                            // NOTE: we deliberately do NOT push a per-change
                            // Livewire .set here. Doing so writes the wireModel on
                            // every edit, which in the Visual Editor context
                            // (wireModel = sectionContent.<field>) interfered with
                            // its own patch/flush save flow. Each save path syncs
                            // the editor explicitly right before persisting:
                            //  - entry form  → saveEntry() flushes every editor
                            //  - visual editor close (X) → collects a patch
                            //  - fullscreen Save / Save & Close → flushSave()
                            self.setSaveState('saved');
                        } catch (e) {
                            console.warn('EditorJS local autosave error:', e);
                            self.setSaveState('error');
                        }
                    }, 600);

                    // 30 seconds AFTER the last keystroke: silent background
                    // sync to the server via flushSave. The save itself fires
                    // updatedSectionContent → updateSection → preview-reload
                    // server-side, which reloads the iframe. We mark this as
                    // a background save so the server skips the preview-reload
                    // (otherwise the iframe blinks every 30s after edits).
                    // Preview only refreshes on explicit X click.
                    // Skipped entirely when autosave is disabled (e.g. the Visual
                    // Builder) — the user persists only via the Save button. The
                    // 600ms localStorage stash above still protects against crashes.
                    if (self.autosave) {
                        clearTimeout(self._bgSyncTimer);
                        self._bgSyncTimer = setTimeout(() => {
                            if (typeof self.flushSave === 'function') {
                                self._silentBg = true;
                                self.flushSave().catch(() => {}).finally(() => { self._silentBg = false; });
                            }
                        }, 30000);
                    }
                },

                onReady: () => {
                    // Stamp instance on DOM node so re-init guard can clean it up
                    const el = document.getElementById(self.uid);
                    if (el) el._editorjsInstance = self.editor;

                    // Undo / Redo — CUSTOM snapshot history (we do NOT use the
                    // editorjs-undo plugin). That plugin tracks DOM mutations and
                    // rebuilds blocks on undo, which loses the caret and mangles
                    // our nested Container/Columns/liveHtml blocks ("jumps / loses
                    // cursor"). Instead we snapshot the full editor JSON on each
                    // debounced change (see onChange) and restore via
                    // editor.blocks.render() — deterministic for every block type.
                    try {
                        self._history = [];
                        self._histPos = -1;
                        self._restoring = false;
                        self.editor.save().then((d) => {
                            self._history = [{ data: d, caret: self._histCaret() }];
                            self._histPos = 0;
                            self._histLastRec = Date.now();
                        }).catch(() => {});
                        // editorjs-undo-compatible shape so the toolbar buttons
                        // (undo?.undo?.() / undo?.redo?.()) work unchanged.
                        self.undo = {
                            undo: () => self._histUndo(),
                            redo: () => self._histRedo(),
                        };
                        // Ctrl/Cmd+Z = undo, Ctrl/Cmd+Shift+Z or Ctrl+Y = redo.
                        if (el && !el._histKeysHooked) {
                            el._histKeysHooked = true;
                            el.addEventListener('keydown', (ev) => {
                                if (!(ev.ctrlKey || ev.metaKey)) { return; }
                                const k = (ev.key || '').toLowerCase();
                                if (k === 'z' && !ev.shiftKey) { ev.preventDefault(); self._histUndo(); }
                                else if ((k === 'z' && ev.shiftKey) || k === 'y') { ev.preventDefault(); self._histRedo(); }
                            }, true);
                        }
                    } catch (e) {
                        console.warn('[EditorJS] Undo init failed (non-fatal):', e);
                    }
                    // Drag & drop reorder — pick up a block by its drag handle and drop elsewhere
                    try {
                        if (window.DragDrop) {
                            self.dragDrop = new window.DragDrop(self.editor);
                        }
                    } catch (e) {
                        console.warn('[EditorJS] DragDrop init failed (non-fatal):', e);
                    }

                    // Floating multi-block alignment toolbar (text selection across blocks)
                    if (el && typeof window.initMultiBlockAlignmentBar === 'function') {
                        window.initMultiBlockAlignmentBar(el);
                    }

                    // One-click ↑/↓ reorder arrows on each block (clearer than drag-and-drop)
                    setTimeout(() => {
                        if (el && typeof window.attachReorderArrows === 'function') {
                            window.attachReorderArrows(el, self.editor);
                        }
                    }, 100);

                    // ----------------------------------------------------------
                    // Paste interceptor: if user pastes content that looks like
                    // HTML (either text/html from a real source, or text/plain
                    // raw markup from a code editor), convert it to editable
                    // EditorJS blocks. Without this, pasting raw HTML lands as
                    // one paragraph per line which is unusable.
                    // ----------------------------------------------------------
                    if (el && !el._vePasteHooked) {
                        el._vePasteHooked = true;
                        el.addEventListener('paste', async function (ev) {
                            try {
                                const cd = ev.clipboardData || window.clipboardData;
                                if (!cd) return;
                                // Bail if the paste landed inside a NESTED editor
                                // (Container or Columns subEditor). The outer handler
                                // is registered in capture mode so without this guard
                                // we'd insert into the OUTER editor's block index —
                                // making the pasted content appear AFTER the
                                // Container block instead of inside it. Walk up from
                                // the paste target; the closest `.codex-editor` is
                                // the editor that should own the paste. Only handle
                                // it ourselves when that's our top-level editor.
                                const closestEditor = ev.target && ev.target.closest
                                    ? ev.target.closest('.codex-editor')
                                    : null;
                                const outerEditor = el.querySelector(':scope > .codex-editor')
                                    || el.querySelector('.codex-editor');
                                if (closestEditor && outerEditor && closestEditor !== outerEditor) {
                                    return; // nested editor — let it handle its own paste
                                }
                                // Only intercept raw HTML markup pasted as plain text.
                                // text/plain = the real markup; text/html = an escaped
                                // line-wrapped version that would parse as one paragraph
                                // per line. Rich-content pastes (non-markup plain text)
                                // fall through to EditorJS's native handler.
                                const plain = cd.getData('text/plain') || '';
                                if (!window._veLooksLikeHtml(plain)) return;
                                ev.preventDefault();
                                ev.stopPropagation();
                                await self.insertHtmlAsBlocks(plain.trim());
                            } catch (e) {
                                console.warn('[paste] interceptor failed (non-fatal):', e);
                            }
                        }, true);
                    }

                    // Click an image block → open media picker to replace it
                    if (el && typeof window._veAttachImageReplace === 'function') {
                        window._veAttachImageReplace(el, self.editor);
                    }

                    // Local autosave: snapshot every 5s. Recovery offered on next mount if
                    // the snapshot is newer than what was loaded from server.
                    self._autosaveInterval = setInterval(() => self._autosaveTick(), 5000);

                    // Recovery: if a snapshot exists newer than initialValue, offer it
                    setTimeout(() => {
                        const snap = self._autosaveRead();
                        if (!snap) return;
                        // Compare snapshot to current editor content; only offer if different
                        self.editor.save().then(current => {
                            const sameAsCurrent = JSON.stringify(current?.blocks || []) === JSON.stringify(snap.data?.blocks || []);
                            if (sameAsCurrent) {
                                self._autosaveClear();
                                return;
                            }
                            self._recoveryData = snap.data;
                            self.recoveryAge = self._formatAge(Date.now() - snap.t);
                            self.showRecovery = true;
                        }).catch(() => {});
                    }, 350);
                },
            });
        },

        /**
         * Custom snapshot-based undo/redo.
         * --------------------------------------------------------------------
         * editorjs-undo watches DOM mutations and rebuilds blocks on undo,
         * which loses the caret and corrupts nested Container/Columns/liveHtml
         * blocks. We instead keep a stack of full editor.save() JSON snapshots
         * and restore them with editor.blocks.render() — uniform and reliable
         * for every block type. Each snapshot remembers the focused block index
         * so the caret returns near the change instead of jumping to the top.
         */
        _histCaret() {
            try {
                const idx = this.editor?.blocks?.getCurrentBlockIndex?.();
                return (typeof idx === 'number' && idx >= 0) ? idx : null;
            } catch (_) {
                return null;
            }
        },

        _histSchedule() {
            if (this._restoring) {
                return;
            }
            const now = Date.now();
            if (!this._histLastRec) {
                this._histLastRec = now;
            }
            const fire = async () => {
                clearTimeout(this._histTimer);
                this._histTimer = null;
                try {
                    this._histRecord(await this.editor.save());
                    this._histLastRec = Date.now();
                } catch (_) {}
            };
            clearTimeout(this._histTimer);
            // maxWait: force a checkpoint during sustained typing so a long burst
            // doesn't collapse into one undo step; otherwise wait for a 500ms pause.
            if (now - this._histLastRec >= 1800) {
                fire();
            } else {
                this._histTimer = setTimeout(fire, 500);
            }
        },

        async _histFlushPending() {
            if (this._restoring) {
                return;
            }
            clearTimeout(this._histTimer);
            this._histTimer = null;
            try {
                this._histRecord(await this.editor.save());
                this._histLastRec = Date.now();
            } catch (_) {}
        },

        _histRecord(data) {
            if (this._restoring || !Array.isArray(this._history)) {
                return;
            }
            try {
                const snap = JSON.stringify(data?.blocks || []);
                const cur = this._history[this._histPos];
                if (cur && JSON.stringify(cur.data?.blocks || []) === snap) {
                    return; // identical to current state — nothing to record
                }
                // Typing into a fresh branch invalidates any redo history.
                if (this._histPos < this._history.length - 1) {
                    this._history = this._history.slice(0, this._histPos + 1);
                }
                this._history.push({ data, caret: this._histCaret() });
                const MAX = 60;
                while (this._history.length > MAX) {
                    this._history.shift();
                }
                this._histPos = this._history.length - 1;
            } catch (_) {}
        },

        async _histApply(entry) {
            if (!entry || !this.editor || typeof this.editor.blocks?.render !== 'function') {
                return;
            }
            this._restoring = true;
            try {
                await this.editor.blocks.render({ blocks: entry.data?.blocks || [] });
                const idx = entry.caret;
                if (typeof idx === 'number' && idx >= 0) {
                    try {
                        const max = this.editor.blocks.getBlocksCount() - 1;
                        this.editor.caret.setToBlock(Math.min(idx, max), 'end');
                    } catch (_) {}
                }
            } catch (e) {
                console.warn('[undo] render failed (non-fatal):', e);
            } finally {
                // Let render's trailing onChange fire (and be ignored) before we
                // re-enable recording.
                setTimeout(() => { this._restoring = false; }, 250);
            }
        },

        async _histUndo() {
            // Capture any just-typed text that hasn't hit the debounce yet, so
            // it becomes its own undo step (otherwise a fast type → Ctrl+Z would
            // either no-op or skip straight past the unsaved text).
            await this._histFlushPending();
            if (Array.isArray(this._history) && this._histPos > 0) {
                this._histPos -= 1;
                await this._histApply(this._history[this._histPos]);
            }
        },

        async _histRedo() {
            if (Array.isArray(this._history) && this._histPos < this._history.length - 1) {
                this._histPos += 1;
                await this._histApply(this._history[this._histPos]);
            }
        },

        /**
         * Force-flush any pending debounced save BEFORE destroying the editor.
         * Called from destroy() and from form-submit hooks to prevent data loss.
         */
        async flushSave() {
            try {
                if (this._saveTimer) {
                    clearTimeout(this._saveTimer);
                    this._saveTimer = null;
                }
                if (!this.editor || this.editor === '_loading_' || typeof this.editor.save !== 'function') {
                    return;
                }
                this.setSaveState('saving');
                const outputData = await this.editor.save();
                const root = document.getElementById(this.uid);
                if (typeof window.rescueInlineFormatting === 'function') {
                    window.rescueInlineFormatting(outputData, root);
                }
                if (typeof window.patchAlignmentTunes === 'function') {
                    window.patchAlignmentTunes(outputData, root);
                }
                const json = JSON.stringify(outputData);
                if (this.wireModel) {
                    const el = document.getElementById(this.uid);
                    if (el) {
                        const lwEl = el.closest('[wire\\:id]');
                        if (lwEl && window.Livewire) {
                            /* Mark this save as silent if it came from the
                               background timer — server skips preview-reload. */
                            try {
                                const lw = Livewire.find(lwEl.getAttribute('wire:id'));
                                if (lw && this._silentBg) {
                                    lw.set('silentBackgroundSave', true, false);
                                }
                            } catch (_) {}
                            // Background (silent) saves use a DEFERRED set (3rd
                            // arg false): the value lands on the Livewire
                            // property client-side without forcing a server
                            // re-render. A live set (true) re-renders the parent
                            // component, which morphs the surrounding DOM and
                            // yanks the scroll position to the top every 30s —
                            // and destabilises the EditorJS undo stack. The
                            // deferred value still reaches the server with the
                            // next real request (the explicit Save/Update click),
                            // and localStorage covers crash recovery in between.
                            // Explicit flushSave calls (Save & Close) keep the
                            // live set so the server persists immediately.
                            const liveSet = ! this._silentBg;
                            Livewire.find(lwEl.getAttribute('wire:id'))?.set(this.wireModel, json, liveSet);
                        }
                    }
                }
                this.setSaveState('saved');
                this._autosaveClear();
            } catch (e) {
                console.warn('[EditorJS] flushSave failed:', e);
                this.setSaveState('error');
            }
        },

        destroy() {
            // Cancel any pending save
            if (this._saveTimer) { clearTimeout(this._saveTimer); this._saveTimer = null; }
            // Stop autosave interval
            if (this._autosaveInterval) { clearInterval(this._autosaveInterval); this._autosaveInterval = null; }

            // Disconnect reorder MutationObserver attached by attachReorderArrows
            try {
                const el = document.getElementById(this.uid);
                if (el) {
                    if (el._reorderObserver) {
                        el._reorderObserver.disconnect();
                        el._reorderObserver = null;
                    }
                    el._reorderArrowsAttached = false;
                    el._editorjsInstance = null;
                }
            } catch (_) {}

            // Clear undo plugin
            if (this.undo) { try { this.undo.clear?.(); } catch (_) {} this.undo = null; }
            // DragDrop has no destroy API but drop our reference so GC can reclaim
            this.dragDrop = null;

            // Destroy editor
            if (this.editor && this.editor !== '_loading_' && typeof this.editor.destroy === 'function') {
                try { this.editor.destroy(); } catch (_) {}
            }
            this.editor = null;
        },
    };
}

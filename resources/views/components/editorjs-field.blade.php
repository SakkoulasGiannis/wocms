{{--
    EditorJS Field Component
    Usage:
      <x-editorjs-field
          :name="$field->name"
          :value="$fieldValues[$field->name] ?? ''"
          wire-model="fieldValues.{{ $field->name }}"
          :placeholder="'Start writing...'"
      />
--}}
@props([
    'name',
    'value' => '',
    'wireModel' => null,
    'placeholder' => 'Start writing or press / for commands...',
    'minHeight' => '200px',
    'uid' => null,
])

@php
    $uid = $uid ?? 'ejs-' . str_replace(['.', '[', ']'], '-', $name) . '-' . Str::random(6);
@endphp

<div
    wire:ignore
    x-data="editorjsField({
        uid: '{{ $uid }}',
        wireModel: {{ $wireModel ? "'" . $wireModel . "'" : 'null' }},
        initialValue: {{ json_encode($value) }},
        uploadImageUrl: '{{ route('admin.editorjs.upload-image') }}',
        fetchImageUrl: '{{ route('admin.editorjs.fetch-image') }}',
        uploadFileUrl: '{{ route('admin.editorjs.upload-file') }}',
        csrfToken: '{{ csrf_token() }}',
        placeholder: {{ json_encode($placeholder) }},
    })"
    x-init="init()"
    x-on:livewire:navigated.window="destroy()"
>
    <div id="{{ $uid }}" class="editorjs-container" style="min-height: {{ $minHeight }}; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff; padding: 0.5rem 0;"></div>
</div>

@once
@push('styles')
<style>
.editorjs-container .ce-block__content,
.editorjs-container .ce-toolbar__content {
    max-width: calc(100% - 2rem);
}
.editorjs-container .codex-editor {
    font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
}
.editorjs-container .ce-paragraph {
    line-height: 1.6;
}
.editorjs-container .cdx-block {
    padding: 0.25rem 0;
}
.editorjs-container .ce-toolbar__plus,
.editorjs-container .ce-toolbar__settings-btn {
    color: #6b7280;
}
.editorjs-container .ce-toolbar__plus:hover,
.editorjs-container .ce-toolbar__settings-btn:hover {
    color: #111827;
    background: #f3f4f6;
}
.editorjs-container .cdx-settings-button:hover,
.editorjs-container .cdx-settings-button--active {
    background: #dbeafe;
    color: #1d4ed8;
}
.editorjs-container h1.ce-header { font-size: 2em; font-weight: 700; }
.editorjs-container h2.ce-header { font-size: 1.5em; font-weight: 700; }
.editorjs-container h3.ce-header { font-size: 1.25em; font-weight: 600; }
.editorjs-container h4.ce-header { font-size: 1.1em; font-weight: 600; }
</style>
@endpush

@push('scripts')
{{-- EditorJS Core --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.30.8/dist/editorjs.umd.min.js"></script>

{{-- Block Tools --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.7/dist/header.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@2.11.6/dist/paragraph.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/nested-list@1.4.2/dist/nested-list.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.7.6/dist/quote.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2.9.3/dist/code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1.4.2/dist/delimiter.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@2.10.1/dist/image.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@2.7.6/dist/embed.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@2.4.3/dist/table.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/link@2.6.2/dist/link.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/raw@2.5.0/dist/raw.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@1.6.0/dist/checklist.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/warning@1.4.0/dist/warning.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/attaches@1.3.2/dist/attaches.umd.js"></script>

{{-- Inline Tools --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@1.4.0/dist/marker.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.5.0/dist/inline-code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@1.2.1/dist/underline.umd.js"></script>

{{-- Undo/Redo --}}
<script src="https://cdn.jsdelivr.net/npm/editorjs-undo@2.0.1/dist/bundle.js"></script>

<script>
function editorjsField(config) {
    return {
        editor: null,
        uid: config.uid,
        wireModel: config.wireModel,
        initialValue: config.initialValue || '',
        uploadImageUrl: config.uploadImageUrl,
        fetchImageUrl: config.fetchImageUrl,
        uploadFileUrl: config.uploadFileUrl,
        csrfToken: config.csrfToken,
        placeholder: config.placeholder,

        parseInitialData() {
            if (!this.initialValue || this.initialValue === '') return null;

            const val = this.initialValue.trim();

            // Try EditorJS JSON
            if (val.startsWith('{')) {
                try {
                    const parsed = JSON.parse(val);
                    if (parsed.blocks) return parsed;
                } catch (e) {}
            }

            // Legacy HTML — convert common tags to proper blocks
            if (val.startsWith('<') || val.includes('<p') || val.includes('<h')) {
                try {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = val;
                    const blocks = [];
                    tmp.childNodes.forEach(node => {
                        if (node.nodeType !== Node.ELEMENT_NODE) {
                            if (node.textContent.trim()) {
                                blocks.push({ type: 'paragraph', data: { text: node.textContent } });
                            }
                            return;
                        }
                        const tag = node.tagName.toLowerCase();
                        if (tag === 'p' || tag === 'div') {
                            if (node.innerHTML.trim()) {
                                blocks.push({ type: 'paragraph', data: { text: node.innerHTML } });
                            }
                        } else if (/^h[1-6]$/.test(tag)) {
                            blocks.push({ type: 'header', data: { text: node.textContent, level: parseInt(tag[1]) } });
                        } else if (tag === 'ul' || tag === 'ol') {
                            const items = Array.from(node.querySelectorAll('li')).map(li => ({ content: li.innerHTML, items: [] }));
                            if (items.length) { blocks.push({ type: 'list', data: { style: tag === 'ul' ? 'unordered' : 'ordered', items } }); }
                        } else if (tag === 'blockquote') {
                            blocks.push({ type: 'quote', data: { text: node.innerHTML, caption: '', alignment: 'left' } });
                        } else {
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
            await this.$nextTick();

            const holderEl = document.getElementById(this.uid);
            if (!holderEl || !window.EditorJS) {
                setTimeout(() => this.init(), 200);
                return;
            }

            const self = this;
            const initialData = this.parseInitialData();

            this.editor = new EditorJS({
                holder: this.uid,
                placeholder: this.placeholder,
                data: initialData || undefined,

                tools: {
                    // Block tools
                    header: {
                        class: Header,
                        config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 },
                    },
                    paragraph: {
                        class: Paragraph,
                        inlineToolbar: true,
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
                    image: {
                        class: ImageTool,
                        config: {
                            uploader: {
                                async uploadByFile(file) {
                                    const form = new FormData();
                                    form.append('image', file);
                                    const res = await fetch(self.uploadImageUrl, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                        body: form,
                                    });
                                    return res.json();
                                },
                                async uploadByUrl(url) {
                                    const res = await fetch(self.fetchImageUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': self.csrfToken,
                                        },
                                        body: JSON.stringify({ url }),
                                    });
                                    return res.json();
                                },
                            },
                        },
                    },
                    attaches: {
                        class: AttachesTool,
                        config: {
                            uploader: {
                                async uploadByFile(file) {
                                    const form = new FormData();
                                    form.append('file', file);
                                    const res = await fetch(self.uploadFileUrl, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': self.csrfToken },
                                        body: form,
                                    });
                                    return res.json();
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
                },

                onChange: async (api, event) => {
                    try {
                        const outputData = await self.editor.save();
                        const json = JSON.stringify(outputData);
                        if (self.wireModel) {
                            // Find Livewire component and set value
                            const el = document.getElementById(self.uid);
                            if (el) {
                                const lwEl = el.closest('[wire\\:id]');
                                if (lwEl && window.Livewire) {
                                    Livewire.find(lwEl.getAttribute('wire:id'))?.set(self.wireModel, json, false);
                                }
                            }
                        }
                    } catch(e) {
                        console.error('EditorJS save error:', e);
                    }
                },

                onReady: () => {
                    // Init undo/redo if available
                    if (window.Undo) {
                        new Undo({ editor: self.editor });
                    }
                },
            });
        },

        destroy() {
            if (this.editor && typeof this.editor.destroy === 'function') {
                this.editor.destroy();
                this.editor = null;
            }
        },
    };
}
</script>
@endpush
@endonce

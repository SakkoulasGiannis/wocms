@push('scripts')
{{-- EditorJS Core --}}
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.30.8/dist/editorjs.umd.min.js"></script>
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
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@1.4.0/dist/marker.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.5.0/dist/inline-code.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@1.2.1/dist/underline.umd.js"></script>
{{-- editorjs-undo disabled — crashes with custom tools (reads .type of undefined) --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/editorjs-undo@2.0.1/dist/bundle.js"></script> --}}
<script>
if (typeof window.editorjsField === 'undefined') {
    window.editorjsField = function(config) {
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
                if (!this.initialValue || this.initialValue === '') { return null; }

                const val = this.initialValue.trim();

                if (val.startsWith('{')) {
                    try {
                        const parsed = JSON.parse(val);
                        if (parsed.blocks) { return parsed; }
                    } catch (e) {}
                }

                if (val.startsWith('<') || val.includes('<p') || val.includes('<h')) {
                    try {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = val;
                        const blocks = [];
                        tmp.childNodes.forEach(node => {
                            if (node.nodeType !== Node.ELEMENT_NODE) {
                                if (node.textContent.trim()) { blocks.push({ type: 'paragraph', data: { text: node.textContent } }); }
                                return;
                            }
                            const tag = node.tagName.toLowerCase();
                            if (tag === 'p' || tag === 'div') {
                                if (node.innerHTML.trim()) { blocks.push({ type: 'paragraph', data: { text: node.innerHTML } }); }
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

                if (val.length > 0) {
                    return { blocks: [{ type: 'paragraph', data: { text: val } }] };
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
                        header: { class: Header, config: { levels: [1, 2, 3, 4, 5, 6], defaultLevel: 2 } },
                        paragraph: { class: Paragraph, inlineToolbar: true },
                        list: { class: NestedList, inlineToolbar: true, config: { defaultStyle: 'unordered' } },
                        checklist: { class: Checklist, inlineToolbar: true },
                        quote: { class: Quote, inlineToolbar: true, config: { quotePlaceholder: 'Enter a quote', captionPlaceholder: 'Quote author' } },
                        code: CodeTool,
                        delimiter: Delimiter,
                        warning: { class: Warning, inlineToolbar: true, config: { titlePlaceholder: 'Title', messagePlaceholder: 'Message' } },
                        table: { class: Table, inlineToolbar: true, config: { rows: 2, cols: 3, withHeadings: true } },
                        image: {
                            class: ImageTool,
                            config: {
                                uploader: {
                                    async uploadByFile(file) {
                                        const form = new FormData();
                                        form.append('image', file);
                                        const res = await fetch(self.uploadImageUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': self.csrfToken }, body: form });
                                        return res.json();
                                    },
                                    async uploadByUrl(url) {
                                        const res = await fetch(self.fetchImageUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': self.csrfToken }, body: JSON.stringify({ url }) });
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
                                        const res = await fetch(self.uploadFileUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': self.csrfToken }, body: form });
                                        return res.json();
                                    },
                                },
                            },
                        },
                        embed: { class: Embed, config: { services: { youtube: true, vimeo: true, coub: true, imgur: true, gfycat: true, twitch: true, twitter: true } } },
                        linkTool: { class: LinkTool, config: { endpoint: self.fetchImageUrl } },
                        raw: RawTool,
                        marker: Marker,
                        inlineCode: InlineCode,
                        underline: Underline,
                    },

                    onChange: async (api, event) => {
                        try {
                            const outputData = await self.editor.save();
                            const json = JSON.stringify(outputData);
                            if (self.wireModel) {
                                const el = document.getElementById(self.uid);
                                if (el) {
                                    const lwEl = el.closest('[wire\\:id]');
                                    if (lwEl && window.Livewire) {
                                        Livewire.find(lwEl.getAttribute('wire:id'))?.set(self.wireModel, json, false);
                                    }
                                }
                            }
                        } catch (e) {
                            console.error('EditorJS save error:', e);
                        }
                    },

                    onReady: () => {
                        if (window.Undo) { new Undo({ editor: self.editor }); }
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
    };
}
</script>
@endpush

<div class="container mx-auto px-4 py-8">
    <!-- Back to Pages Button -->
    <div class="mb-4">
        <a href="{{ route('admin.page-sections.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Πίσω στη λίστα σελίδων
        </a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Page Sections - {{ $pageTitle }}</h1>
        <button wire:click="openAddModal" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            + Add Section
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Sections List -->
    <div class="space-y-6">
        @forelse($sections as $section)
            <div wire:key="section-{{ $section->id }}"
                 class="bg-white shadow-md rounded-lg overflow-hidden border-2 transition-all
                        {{ $selectedSectionId === $section->id ? 'border-blue-500 ring-2 ring-blue-200' : ($section->is_active ? 'border-gray-200' : 'border-red-300') }}"
                 wire:click="selectSection({{ $section->id }})"
                 style="cursor: pointer;">

                <!-- Section Header -->
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold">{{ $section->name }}</h3>
                        <span class="text-xs bg-gray-200 px-2 py-1 rounded">
                            {{ $section->sectionTemplate?->name ?? ($availableSectionTypes[$section->section_type]['name'] ?? $section->section_type) }}
                        </span>
                        @if(!$section->is_active)
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Inactive</span>
                        @endif
                        @if($selectedSectionId === $section->id)
                            <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">✓ Selected for AI</span>
                        @endif
                    </div>

                    <div class="flex gap-2" onclick="event.stopPropagation()">
                        <!-- Move Up/Down -->
                        <button wire:click="moveUp({{ $section->id }})"
                                class="text-gray-600 hover:text-gray-800 p-1 hover:bg-gray-200 rounded"
                                title="Move Up">
                            ↑
                        </button>
                        <button wire:click="moveDown({{ $section->id }})"
                                class="text-gray-600 hover:text-gray-800 p-1 hover:bg-gray-200 rounded"
                                title="Move Down">
                            ↓
                        </button>

                        <!-- Toggle JSON View -->
                        <button wire:click="toggleJson({{ $section->id }})"
                                class="px-3 py-1 rounded text-xs {{ in_array($section->id, $showJsonFor) ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700' }} hover:bg-purple-200"
                                title="Toggle JSON View">
                            {{ in_array($section->id, $showJsonFor) ? 'Hide' : 'Show' }} JSON
                        </button>

                        <!-- Toggle Active -->
                        <button wire:click="toggleActive({{ $section->id }})"
                                class="px-3 py-1 rounded text-xs {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                            {{ $section->is_active ? 'Active' : 'Inactive' }}
                        </button>

                        <!-- Edit with AI -->
                        <a href="{{ route('admin.page-sections.edit', ['sectionId' => $section->id]) }}"
                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Edit
                        </a>

                        <!-- Quick Edit -->
                        <button wire:click="editSection({{ $section->id }})"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs">
                            Quick Edit
                        </button>

                        <!-- Delete -->
                        <button wire:click="deleteSection({{ $section->id }})"
                                onclick="return confirm('Are you sure?')"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Section Content -->
                <div class="p-4">
                    @if(in_array($section->id, $showJsonFor))
                        <!-- JSON View -->
                        <div class="bg-gray-900 text-gray-100 p-4 rounded font-mono text-xs overflow-x-auto">
                            <pre>{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @else
                        <!-- HTML Preview (Rendered Section) -->
                        <div class="border-2 border-dashed border-gray-300 rounded p-2">
                            <div class="text-xs text-gray-500 mb-2 font-semibold">Preview:</div>
                            <div class="bg-white">
                                @php
                                    $componentName = 'sections.' . str_replace('_', '-', $section->section_type);
                                    $renderError = null;

                                    try {
                                        $componentView = view()->make('components.' . str_replace('.', '/', $componentName), [
                                            'content' => $section->content,
                                            'settings' => $section->settings
                                        ]);
                                        echo $componentView->render();
                                    } catch (\Throwable $e) {
                                        $renderError = $e->getMessage();
                                    }
                                @endphp

                                @if($renderError)
                                    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded text-sm">
                                        <strong>Error rendering section:</strong> {{ $renderError }}
                                        <div class="mt-2 text-xs">
                                            Component: {{ $componentName }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <p class="text-gray-600">No sections found. Click "Add Section" to create your first section.</p>
            </div>
        @endforelse
    </div>

    <!-- Add/Edit Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold">{{ $editingSection ? 'Edit Section' : 'Add New Section' }}</h2>
                </div>

                <div class="p-6">
                    @if(!$selectedSectionType && !$selectedTemplateId)
                        <!-- Template Selection -->
                        <h3 class="text-lg font-semibold mb-4">Select Section Template</h3>

                        @if(count($availableTemplates) > 0)
                            @php
                                $categories = collect($availableTemplates)->groupBy('category');
                            @endphp

                            @foreach($categories as $category => $templates)
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mt-6 mb-3">{{ ucfirst($category) }}</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @foreach($templates as $tpl)
                                        <button wire:click="selectTemplate({{ $tpl['id'] }})"
                                                class="border border-gray-300 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 text-left transition">
                                            <h4 class="font-semibold mb-1">{{ $tpl['name'] }}</h4>
                                            <p class="text-sm text-gray-600">{{ $tpl['description'] }}</p>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500">No templates available.</p>
                        @endif
                    @else
                        <!-- Section Form -->
                        @php
                            $templateFields = $selectedTemplateId
                                ? \App\Models\SectionTemplateField::where('section_template_id', $selectedTemplateId)->orderBy('order')->get()
                                : collect();
                        @endphp

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Section Name</label>
                                <input type="text"
                                       wire:model="sectionName"
                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                       placeholder="Section name">
                            </div>

                            @if($templateFields->isNotEmpty())
                                {{-- Dynamic Fields from SectionTemplateField --}}
                                @foreach($templateFields as $field)
                                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            {{ $field->label }}
                                            @if($field->is_required) <span class="text-red-500">*</span> @endif
                                        </label>
                                        @if($field->description)
                                            <p class="text-xs text-gray-500 mb-2">{{ $field->description }}</p>
                                        @endif

                                        @switch($field->type)
                                            @case('text')
                                            @case('url')
                                            @case('email')
                                                <input type="{{ $field->type }}"
                                                       wire:model="sectionContent.{{ $field->name }}"
                                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                                       placeholder="{{ $field->placeholder ?? $field->label }}">
                                                @break

                                            @case('number')
                                                <input type="number"
                                                       wire:model="sectionContent.{{ $field->name }}"
                                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                                       placeholder="{{ $field->placeholder ?? '' }}">
                                                @break

                                            @case('textarea')
                                                <textarea wire:model="sectionContent.{{ $field->name }}"
                                                          class="w-full border border-gray-300 rounded px-3 py-2"
                                                          rows="3"
                                                          placeholder="{{ $field->placeholder ?? '' }}"></textarea>
                                                @break

                                            @case('image')
                                                <div class="space-y-2">
                                                    @if(!empty($sectionContent[$field->name]))
                                                        <div class="relative inline-block">
                                                            <img src="{{ $sectionContent[$field->name] }}" class="h-24 rounded-lg border border-gray-200 object-cover" alt="Preview">
                                                            <button type="button" wire:click="$set('sectionContent.{{ $field->name }}', '')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                                                        </div>
                                                    @endif
                                                    <div class="flex items-center gap-2">
                                                        <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 text-sm transition">
                                                            <i class="fa fa-upload"></i> Upload
                                                            <input type="file" wire:model="sectionImageUploads.{{ $field->name }}" accept="image/*" class="hidden">
                                                        </label>
                                                        <input type="text"
                                                               wire:model="sectionContent.{{ $field->name }}"
                                                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm"
                                                               placeholder="or enter image URL">
                                                    </div>
                                                    <div wire:loading wire:target="sectionImageUploads.{{ $field->name }}" class="text-xs text-blue-600"><i class="fa fa-spinner fa-spin"></i> Uploading...</div>
                                                </div>
                                                @break

                                            @case('select')
                                                @php
                                                    $rawOpts = json_decode($field->options ?? '[]', true) ?: [];
                                                    // Support both ["h1","h2"] and {"value":"label"} and [{"value":"v","label":"l"}]
                                                    $selectOptions = [];
                                                    foreach ($rawOpts as $k => $v) {
                                                        if (is_array($v)) {
                                                            $selectOptions[$v['value'] ?? $k] = $v['label'] ?? $v['value'] ?? $k;
                                                        } elseif (is_int($k)) {
                                                            $selectOptions[$v] = $v;
                                                        } else {
                                                            $selectOptions[$k] = $v;
                                                        }
                                                    }
                                                @endphp
                                                <select wire:model="sectionContent.{{ $field->name }}"
                                                        class="w-full border border-gray-300 rounded px-3 py-2">
                                                    <option value="">-- Select --</option>
                                                    @foreach($selectOptions as $optVal => $optLabel)
                                                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @break

                                            @case('checkbox')
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" wire:model="sectionContent.{{ $field->name }}" class="rounded border-gray-300">
                                                    <span class="text-sm">{{ $field->label }}</span>
                                                </label>
                                                @break

                                            @case('color')
                                                <input type="color"
                                                       wire:model="sectionContent.{{ $field->name }}"
                                                       class="w-20 h-10 border border-gray-300 rounded cursor-pointer">
                                                @break

                                            @case('repeater')
                                                @php
                                                    $subFields = json_decode($field->settings ?? '{}', true)['sub_fields'] ?? [];
                                                    $repeaterItems = $sectionContent[$field->name] ?? [];
                                                    if (!is_array($repeaterItems)) $repeaterItems = [];
                                                @endphp

                                                <div class="space-y-3">
                                                    @foreach($repeaterItems as $rIdx => $rItem)
                                                        <div class="border border-gray-300 rounded-lg p-3 bg-white relative">
                                                            <button type="button"
                                                                    wire:click="removeRepeaterItem('{{ $field->name }}', {{ $rIdx }})"
                                                                    class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-xs font-bold"
                                                                    title="Remove">✕</button>
                                                            <div class="text-xs text-gray-400 mb-2 font-semibold">#{{ $rIdx + 1 }}</div>
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                @foreach($subFields as $sf)
                                                                    <div>
                                                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $sf['label'] }}</label>
                                                                        @if(($sf['type'] ?? 'text') === 'textarea')
                                                                            <textarea wire:model="sectionContent.{{ $field->name }}.{{ $rIdx }}.{{ $sf['name'] }}"
                                                                                      class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                                                                      rows="2"></textarea>
                                                                        @else
                                                                            <input type="{{ $sf['type'] ?? 'text' }}"
                                                                                   wire:model="sectionContent.{{ $field->name }}.{{ $rIdx }}.{{ $sf['name'] }}"
                                                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                                                                   placeholder="{{ $sf['label'] }}">
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach

                                                    <button type="button"
                                                            wire:click="addRepeaterItem('{{ $field->name }}')"
                                                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                        + Add {{ $field->label }} Item
                                                    </button>
                                                </div>
                                                @break

                                            @case('wysiwyg')
                                                <x-editorjs-field
                                                    :name="$field->name"
                                                    :value="$sectionContent[$field->name] ?? ''"
                                                    wire-model="sectionContent.{{ $field->name }}"
                                                    :uid="'ejs-sm-' . $field->name"
                                                />
                                                @break

                                            @default
                                                <input type="text"
                                                       wire:model="sectionContent.{{ $field->name }}"
                                                       class="w-full border border-gray-300 rounded px-3 py-2"
                                                       placeholder="{{ $field->placeholder ?? $field->label }}">
                                        @endswitch
                                    </div>
                                @endforeach

                                {{-- JSON fallback toggle --}}
                                <details class="mt-4">
                                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Advanced: Edit Raw JSON</summary>
                                    <div class="mt-2 space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Content JSON</label>
                                            <textarea wire:model="sectionContent"
                                                      class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-xs"
                                                      rows="8">{{ json_encode($sectionContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Settings JSON</label>
                                            <textarea wire:model="sectionSettings"
                                                      class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-xs"
                                                      rows="4">{{ json_encode($sectionSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                        </div>
                                    </div>
                                </details>
                            @else
                                {{-- No fields defined: fallback to JSON --}}
                                <div>
                                    <label class="block text-sm font-medium mb-1">Content (JSON)</label>
                                    <textarea wire:model="sectionContent"
                                              class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"
                                              rows="10">{{ json_encode($sectionContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Settings (JSON)</label>
                                    <textarea wire:model="sectionSettings"
                                              class="w-full border border-gray-300 rounded px-3 py-2 font-mono text-sm"
                                              rows="5">{{ json_encode($sectionSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-6 border-t flex justify-end gap-2">
                    <button wire:click="$set('showAddModal', false)"
                            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">
                        Cancel
                    </button>
                    @if($selectedSectionType || $selectedTemplateId)
                        <button wire:click="{{ $editingSection ? 'updateSection' : 'saveSection' }}"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
                            {{ $editingSection ? 'Update' : 'Add' }} Section
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

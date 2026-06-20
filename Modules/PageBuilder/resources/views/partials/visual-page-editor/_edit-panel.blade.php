    {{-- ===== EDIT PANEL (floats over preview) ===== --}}
    @if($selectedSectionId || $showAddPanel)
        <div data-edit-panel
             class="bg-white border-r border-gray-200 flex flex-col shadow-2xl overflow-hidden"
             style="position:absolute; left:0; top:0; width:288px; height:100vh; z-index:30;">

            {{-- Panel Header --}}
            <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between flex-shrink-0">
                <h3 class="font-semibold text-gray-800 text-sm">
                    @if($showAddPanel)
                        Add Section
                    @else
                        Edit: {{ collect($sections)->firstWhere('id', $selectedSectionId)['name'] ?? 'Section' }}
                    @endif
                </h3>
                @if($showAddPanel && $addingChildOfSectionId)
                    <span class="text-xs text-green-600 font-normal ml-auto mr-2">child</span>
                @endif
                <button type="button"
                        x-on:click.prevent="
                            (async () => {
                                /* Collect EVERY editor via the shared collector, then send
                                   everything to the server in ONE saveAndClose() call. */
                                const eds = (typeof window.veCollectEditors === 'function') ? await window.veCollectEditors() : [];
                                const patch = {};
                                eds.forEach((e) => { const m = e.wireModel.match(/^sectionContent\.(.+)$/); if (m) { patch[m[1]] = e.json; } });
                                $wire.saveAndClose(patch);
                            })();
                        "
                        class="text-gray-400 hover:text-gray-700"
                        title="Save and close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-4">

                @if($showAddPanel)
                    {{-- Template picker --}}
                    @if(!$selectedTemplateId)
                        <div x-data="{ tplSearch: '' }">
                            @if($addingChildOfSectionId)
                                @php $parentName = collect($sections)->firstWhere('id', $addingChildOfSectionId)['name'] ?? 'section'; @endphp
                                <div class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-2.5 py-1.5 mb-3">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Adding child of <span class="font-semibold ml-0.5">{{ $parentName }}</span>
                                </div>
                            @endif
                            <input type="text"
                                   x-model="tplSearch"
                                   placeholder="Search templates..."
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            @php
                                $grouped = collect($availableTemplates)->groupBy('category');
                            @endphp
                            @foreach($grouped as $category => $templates)
                                <div class="mb-3"
                                     x-show="tplSearch === '' || {{ collect($templates)->map(fn($t) => "'" . addslashes(strtolower($t['name'] . ' ' . $t['description'])) . "'.includes(tplSearch.toLowerCase())") ->implode(' || ') }}">
                                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ $category ?: 'General' }}</div>
                                    <div class="space-y-1">
                                        @foreach($templates as $tpl)
                                            <button type="button"
                                                    wire:click="selectTemplate({{ $tpl['id'] }})"
                                                    x-show="tplSearch === '' || '{{ addslashes(strtolower($tpl['name'] . ' ' . $tpl['description'])) }}'.includes(tplSearch.toLowerCase())"
                                                    class="w-full text-left px-3 py-2 rounded-lg border border-gray-200 hover:border-purple-400 hover:bg-purple-50 transition text-sm">
                                                <div class="font-medium text-gray-800">{{ $tpl['name'] }}</div>
                                                @if($tpl['description'])
                                                    <div class="text-xs text-gray-400 mt-0.5">{{ $tpl['description'] }}</div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Selected template info --}}
                        <div class="flex items-center justify-between bg-purple-50 border border-purple-200 rounded-lg px-3 py-2">
                            <div class="text-sm font-medium text-purple-800">
                                {{ collect($availableTemplates)->firstWhere('id', $selectedTemplateId)['name'] ?? '' }}
                            </div>
                            <button wire:click="$set('selectedTemplateId', null)" class="text-purple-400 hover:text-purple-700 text-xs">Change</button>
                        </div>
                    @endif
                @endif

                {{-- Fields form (shown when editing OR when template is selected for add) --}}
                @if($selectedTemplate && ($selectedSectionId || $showAddPanel))
                    {{-- Section name --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Section Name</label>
                        <input type="text" wire:model.live.debounce.500ms="sectionName"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="Section name">
                    </div>

                    {{-- Dynamic Fields --}}
                    @foreach($selectedTemplate->fields as $field)
                        <div wire:key="ve-fld-{{ $selectedSectionId ?? 'new' }}-{{ $field->id }}">
                            @php
                                /* Token-aware fields ── the ones where {field} placeholders
                                 * make sense. Card bindings of the entry-loop section, plus
                                 * any explicit token-ish field name (token_*, *_token), plus
                                 * generic text/textarea when we're in template-design mode.
                                 *
                                 * The picker shows fields from:
                                 *  • the sectionable Template (template-design mode), OR
                                 *  • the source_template currently selected (entry-loop mode)
                                 */
                                $isTokenAware = in_array($field->name, [
                                    'card_image_token','card_title_token','card_subtitle_token','card_link_pattern','heading','subheading',
                                ], true);
                                $isLoopSection = ($selectedSection->section_type ?? null) === 'entry_loop';
                                $isTemplateDesign = $sectionableType === 'App\\Models\\Template';

                                if (! $isTokenAware && ($isLoopSection || $isTemplateDesign) && in_array($field->type, ['text','textarea','url','image'], true)) {
                                    $isTokenAware = true;
                                }

                                $tokenFields = [];
                                if ($isTokenAware) {
                                    try {
                                        $srcTemplate = null;
                                        if ($isLoopSection) {
                                            $sourceSlug = $sectionContent['source_template'] ?? null;
                                            if ($sourceSlug) {
                                                $srcTemplate = \App\Models\Template::where('slug', $sourceSlug)->first();
                                            }
                                        } elseif ($isTemplateDesign) {
                                            $srcTemplate = \App\Models\Template::find($sectionableId);
                                        }
                                        if ($srcTemplate) {
                                            $tokenFields = $srcTemplate->fields()
                                                ->orderBy('order')
                                                ->get(['name', 'label', 'type'])
                                                ->toArray();
                                        }
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <label class="flex items-center justify-between text-xs font-medium text-gray-600 mb-1">
                                <span>
                                    {{ $field->label }}
                                    @if($field->is_required)<span class="text-red-500">*</span>@endif
                                </span>
                                @if($isTokenAware && count($tokenFields))
                                    <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                                        <button type="button" @click="open = !open"
                                                class="inline-flex items-center gap-1 rounded bg-purple-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-700 hover:bg-purple-200">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M13.828 10.172a4 4 0 00-5.656 0L4.343 14.001a4 4 0 105.656 5.656l1.102-1.102M10.172 13.828a4 4 0 005.656 0l3.829-3.829a4 4 0 10-5.656-5.656l-1.102 1.102"/></svg>
                                            Insert field
                                        </button>
                                        <div x-show="open" x-transition style="display:none"
                                             class="absolute right-0 mt-1 w-64 max-h-72 overflow-y-auto z-30 rounded-lg border border-gray-200 bg-white shadow-lg p-1">
                                            <div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400 border-b">Available tokens</div>
                                            @foreach($tokenFields as $tf)
                                                @php
                                                    $token = '{' . $tf['name'] . ($tf['type'] === 'image' ? ':preview' : '') . '}';
                                                @endphp
                                                <button type="button"
                                                        @click="
                                                            const inp = document.getElementById('ve-field-{{ $field->name }}');
                                                            if (inp) {
                                                                const start = inp.selectionStart ?? inp.value.length;
                                                                const end = inp.selectionEnd ?? inp.value.length;
                                                                const before = inp.value.substring(0, start);
                                                                const after = inp.value.substring(end);
                                                                inp.value = before + @js($token) + after;
                                                                inp.dispatchEvent(new Event('input'));
                                                                inp.focus();
                                                                inp.selectionStart = inp.selectionEnd = start + @js(strlen($token));
                                                            }
                                                            open = false;
                                                        "
                                                        class="flex w-full items-center justify-between gap-2 rounded px-2 py-1.5 text-xs text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                                    <span class="truncate">{{ $tf['label'] ?? $tf['name'] }}</span>
                                                    <code class="text-[10px] text-gray-400">{{ $token }}</code>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </label>

                            @switch($field->type)
                                @case('text')
                                @case('url')
                                @case('email')
                                    @if($field->name === 'class' || str_ends_with($field->name, '_class'))
                                        <div wire:ignore x-data="{
                                            tags: (@js($sectionContent[$field->name] ?? '')).split(/\s+/).filter(t => t !== ''),
                                            inputVal: '',
                                            open: false,
                                            suggestions: [],
                                            copied: false,
                                            pasted: false,
                                            addTag() {
                                                this.inputVal.trim().split(/\s+/).forEach(part => {
                                                    const t = part.replace(/[,;]/g, '').trim();
                                                    if (t && !this.tags.includes(t)) this.tags.push(t);
                                                });
                                                this.inputVal = '';
                                                this.open = false;
                                                this.sync();
                                            },
                                            removeTag(tag) { this.tags = this.tags.filter(t => t !== tag); this.sync(); },
                                            sync() {
                                                $wire.set('sectionContent.{{ $field->name }}', this.tags.filter(t => t.trim()).join(' '));
                                            },
                                            copyAll() {
                                                const txt = this.tags.join(' ');
                                                navigator.clipboard.writeText(txt).then(() => {
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 1500);
                                                });
                                            },
                                            pasteAll() {
                                                navigator.clipboard.readText().then(txt => {
                                                    (txt || '').split(/\s+/).forEach(part => {
                                                        const t = part.replace(/[,;]/g, '').trim();
                                                        if (t && !this.tags.includes(t)) this.tags.push(t);
                                                    });
                                                    this.sync();
                                                    this.pasted = true;
                                                    setTimeout(() => this.pasted = false, 1500);
                                                });
                                            },
                                            clearAll() {
                                                if (!this.tags.length) return;
                                                this.tags = [];
                                                this.sync();
                                            },
                                            search() {
                                                const q = this.inputVal.trim().toLowerCase();
                                                if (!q) { this.open = false; return; }
                                                this.suggestions = (window._twClasses||[]).filter(c => c.startsWith(q) || c.includes(q)).slice(0,15);
                                                this.open = this.suggestions.length > 0;
                                            },
                                            pick(s) {
                                                if (!this.tags.includes(s)) { this.tags.push(s); this.sync(); }
                                                this.inputVal = '';
                                                this.open = false;
                                            }
                                        }">
                                            {{-- Copy / paste / clear toolbar — lets you move classes between fields --}}
                                            <div class="flex items-center justify-end gap-1 mb-1">
                                                <button type="button" @click="copyAll()"
                                                        :disabled="!tags.length"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                                        :title="tags.length ? 'Copy all classes' : 'Nothing to copy'">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                                </button>
                                                <button type="button" @click="pasteAll()"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded border border-gray-200 text-gray-600 hover:bg-gray-50"
                                                        title="Paste classes from clipboard (merges)">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    <span x-text="pasted ? 'Pasted!' : 'Paste'"></span>
                                                </button>
                                                <button type="button" @click="clearAll()"
                                                        :disabled="!tags.length"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 disabled:opacity-40 disabled:cursor-not-allowed"
                                                        title="Remove all classes">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Clear
                                                </button>
                                            </div>
                                            <div class="flex flex-wrap gap-1 p-2 border border-gray-300 rounded-lg min-h-[38px] cursor-text bg-white" @click="$refs.twInput.focus()">
                                                <template x-for="tag in tags" :key="tag">
                                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded text-xs font-mono">
                                                        <span x-text="tag"></span>
                                                        <button type="button" @click.stop="removeTag(tag)" class="text-purple-400 hover:text-purple-700 leading-none ml-0.5">&times;</button>
                                                    </span>
                                                </template>
                                                <input type="text" x-ref="twInput" x-model="inputVal"
                                                       @keydown.enter.prevent="addTag()"
                                                       @keydown.188.prevent="addTag()"
                                                       @input.debounce.200ms="search()"
                                                       @blur="setTimeout(() => open = false, 150)"
                                                       class="flex-1 min-w-20 outline-none text-xs font-mono bg-transparent"
                                                       placeholder="e.g. flex gap-4">
                                            </div>
                                            <div x-show="open" class="relative">
                                                <div class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-36 overflow-y-auto">
                                                    <template x-for="s in suggestions" :key="s">
                                                        <button type="button" @mousedown.prevent="pick(s)"
                                                                class="w-full text-left px-3 py-1.5 text-xs font-mono hover:bg-purple-50 hover:text-purple-700">
                                                            <span x-text="s"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <input type="{{ $field->type }}"
                                               id="ve-field-{{ $field->name }}"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                               placeholder="{{ $field->placeholder ?? $field->label }}">
                                    @endif
                                    @break

                                @case('textarea')
                                    <textarea id="ve-field-{{ $field->name }}"
                                              wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                              rows="3"
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                              placeholder="{{ $field->placeholder ?? $field->label }}"></textarea>
                                    @break

                                @case('number')
                                    <input type="number"
                                           wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    @break

                                @case('checkbox')
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model.live="sectionContent.{{ $field->name }}"
                                               class="w-4 h-4 text-purple-600 rounded">
                                        <span class="text-sm text-gray-700">{{ $field->placeholder ?? 'Enable' }}</span>
                                    </label>
                                    @break

                                @case('select')
                                    @php
                                        // Dynamic model-based select fields
                                        $dynamicOptions = null;
                                        if ($field->name === 'slider_id' && class_exists(\Modules\Slider\Models\Slider::class)) {
                                            $dynamicOptions = \Modules\Slider\Models\Slider::where('is_active', true)
                                                ->orderBy('name')->get()
                                                ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
                                                ->toArray();
                                        } elseif ($field->name === 'category_slug' && class_exists(\App\Models\BlogCategory::class)) {
                                            // Blog Loop section: populate category dropdown from BlogCategory taxonomy
                                            $dynamicOptions = \App\Models\BlogCategory::where('is_active', true)
                                                ->orderBy('order')->orderBy('name')->get()
                                                ->map(fn($c) => ['value' => $c->slug, 'label' => $c->name])
                                                ->toArray();
                                        }

                                        if ($dynamicOptions === null) {
                                            // Static options from field->options OR field->settings['options']
                                            $rawOptions = $field->options ?? null;
                                            if ($rawOptions) {
                                                $dynamicOptions = is_string($rawOptions) ? json_decode($rawOptions, true) : $rawOptions;
                                            } else {
                                                $opts = is_string($field->settings) ? json_decode($field->settings, true) : ($field->settings ?? []);
                                                $dynamicOptions = $opts['options'] ?? [];
                                            }
                                        }
                                        $dynamicOptions = $dynamicOptions ?: [];
                                    @endphp
                                    <select wire:model.live="sectionContent.{{ $field->name }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">— Select —</option>
                                        @foreach($dynamicOptions as $opt)
                                            <option value="{{ $opt['value'] ?? $opt }}">{{ $opt['label'] ?? $opt }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('template_picker')
                                    {{-- Dropdown of all database-backed, active templates.
                                         Saves the template SLUG into sectionContent so the
                                         loop section can use it directly when querying. --}}
                                    @php
                                        $availableTemplates = \App\Models\Template::query()
                                            ->where('is_active', true)
                                            ->where('requires_database', true)
                                            ->orderBy('name')
                                            ->get(['slug', 'name'])
                                            ->map(fn ($t) => ['value' => $t->slug, 'label' => $t->name])
                                            ->toArray();
                                    @endphp
                                    <select wire:model.live="sectionContent.{{ $field->name }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">— Pick a template —</option>
                                        @foreach($availableTemplates as $opt)
                                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('color')
                                    <div class="flex items-center gap-2">
                                        <input type="color"
                                               wire:model.live="sectionContent.{{ $field->name }}"
                                               class="w-10 h-10 rounded cursor-pointer border border-gray-300">
                                        <input type="text"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                               placeholder="#000000">
                                    </div>
                                    @break

                                @case('image')
                                    @if(!empty($sectionContent[$field->name]))
                                        <div class="relative inline-block mb-2">
                                            <img src="{{ $sectionContent[$field->name] }}" alt="" class="h-20 rounded-lg border border-gray-200 object-cover">
                                            <button type="button" wire:click="$set('sectionContent.{{ $field->name }}', '')"
                                                    class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
                                        </div>
                                    @endif
                                    <div class="flex gap-2">
                                        <label class="cursor-pointer flex items-center gap-1.5 px-2 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-200 text-xs transition" title="Upload new file">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            <input type="file" wire:model="sectionImageUploads.{{ $field->name }}" accept="image/*" class="hidden">
                                        </label>
                                        <button type="button"
                                                wire:click="openMediaLibrary('{{ $field->name }}')"
                                                class="flex items-center gap-1 px-2 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg border border-purple-200 text-xs transition" title="Browse media library">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <input type="text"
                                               id="ve-field-{{ $field->name }}"
                                               wire:model.live.debounce.500ms="sectionContent.{{ $field->name }}"
                                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-xs"
                                               placeholder="or paste URL / {field} token">
                                    </div>
                                    @break

                                @case('wysiwyg')
                                    {{-- CRITICAL: This <style> tag MUST live OUTSIDE the x-if template
                                         below. Browsers treat <template> content as inert — any <style>
                                         tags inside don't register with the document stylesheet. That's
                                         why ALL the previous CSS attempts mysteriously did nothing
                                         (overflow-y stayed `visible`, max-width never applied, etc.). --}}
                                    @once
                                        <style>
                                            .ve-fs-editor-host {
                                                overflow-y: auto !important;
                                                overflow-x: hidden !important;
                                            }
                                            .ve-fs-editor-host .editorjs-container,
                                            .ve-fs-editor-host .codex-editor,
                                            .ve-fs-editor-host .codex-editor--narrow,
                                            .ve-fs-editor-host .codex-editor__redactor,
                                            .ve-fs-editor-host .codex-editor--narrow .codex-editor__redactor {
                                                max-width: 1400px !important;
                                                width: 100% !important;
                                                margin-left: auto !important;
                                                margin-right: auto !important;
                                            }
                                            .ve-fs-editor-host .ce-block__content,
                                            .ve-fs-editor-host .ce-toolbar__content,
                                            .ve-fs-editor-host .codex-editor--narrow .ce-block__content,
                                            .ve-fs-editor-host .codex-editor--narrow .ce-toolbar__content {
                                                max-width: 100% !important;
                                                margin: 0 !important;
                                                width: 100% !important;
                                            }
                                        </style>
                                    @endonce
                                    <div x-data="{ fullscreen: false }">
                                        {{-- Collapsed: Just a button. Editor isn't mounted yet, no
                                             layout shenanigans. --}}
                                        <div x-show="!fullscreen"
                                             class="border border-dashed border-gray-300 rounded-lg bg-gray-50 px-4 py-6 text-center">
                                            <button type="button"
                                                    @click="fullscreen = true"
                                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                                </svg>
                                                Open editor (Fullscreen)
                                            </button>
                                            <p class="text-xs text-gray-500 mt-2">Edit this section's content in a dedicated fullscreen workspace.</p>
                                        </div>

                                        {{-- Fullscreen overlay: ONE fixed wrapper, overflow:auto on it
                                             directly. Header is sticky inside. Editor flows naturally
                                             and grows the wrapper, which scrolls with native wheel. --}}
                                        <template x-if="fullscreen">
                                            {{-- Explicit absolute layout instead of flex. EditorJS
                                                 measures its parent size on mount and doesn't always
                                                 re-measure when flex resolves, which left the editor
                                                 at height 0 and dead-zoned scroll. With top/bottom set
                                                 directly the dimensions are known immediately. --}}
                                            <div style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:1000; background:#ffffff;">
                                                {{-- Header: absolutely pinned to the top, 56px tall.
                                                     Style + Source operate on whichever liveHtml block
                                                     was last focused, via window._activeLiveHtmlTool. --}}
                                                <div style="position:absolute; top:0; left:0; right:0; height:56px; display:flex; align-items:center; justify-content:space-between; padding:0 16px; border-bottom:1px solid #e5e7eb; background:#f9fafb;">
                                                    <span class="text-sm font-semibold text-gray-700 truncate">{{ $field->label }}</span>
                                                    <div class="flex items-center gap-2">
                                                        {{-- Style mode toggle: operates on focused block --}}
                                                        <button type="button"
                                                                onclick="if (window._activeLiveHtmlTool) window._activeLiveHtmlTool.toggleStyleMode(); else alert('Click inside the HTML block first.')"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:border-gray-400 hover:bg-gray-50 rounded transition"
                                                                title="Style mode — click any element to edit its classes">
                                                            <span>🎨</span><span>Style</span>
                                                        </button>
                                                        {{-- Source view: opens the raw HTML editor modal --}}
                                                        <button type="button"
                                                                onclick="if (window._activeLiveHtmlTool) window._activeLiveHtmlTool.openSourceModal(); else alert('Click inside the HTML block first.')"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:border-gray-400 hover:bg-gray-50 rounded transition"
                                                                title="Edit raw HTML source">
                                                            <span>&lt;/&gt;</span><span>Source</span>
                                                        </button>

                                                        {{-- Plain Save: flush the editor's pending save WITHOUT
                                                             collapsing the fullscreen — lets the user keep
                                                             editing after a checkpoint. Same flushSave path
                                                             as Save & Close, just without the fullscreen
                                                             state flip. Brief visual feedback on the button. --}}
                                                        <button type="button"
                                                                onclick="
                                                                    (async () => {
                                                                        const btn = this;
                                                                        const label = btn.querySelector('span');
                                                                        const original = label ? label.textContent : null;
                                                                        if (document.activeElement && document.activeElement.blur) {
                                                                            document.activeElement.blur();
                                                                        }
                                                                        /* Collect EVERY editor's content into a patch and persist
                                                                           it to the DB in ONE saveContent() call. Relying on
                                                                           flushSave's Livewire .set alone updated the property but
                                                                           never wrote the section row, so the wysiwyg content was
                                                                           lost on save. */
                                                                        let ok = false;
                                                                        try {
                                                                            const eds = (typeof window.veCollectEditors === 'function') ? await window.veCollectEditors() : [];
                                                                            const patch = {};
                                                                            eds.forEach((e) =&gt; { const m = e.wireModel.match(/^sectionContent\.(.+)$/); if (m) { patch[m[1]] = e.json; } });
                                                                            const cmpEl = btn.closest('[wire\\:id]');
                                                                            const wire = cmpEl && window.Livewire ? window.Livewire.find(cmpEl.getAttribute('wire:id')) : null;
                                                                            if (wire) { await wire.saveContent(patch); ok = true; }
                                                                        } catch (e) { console.warn('[save] saveContent failed:', e); }
                                                                        if (label) {
                                                                            label.textContent = ok ? 'Saved ✓' : 'Save failed';
                                                                            btn.disabled = true;
                                                                            setTimeout(() =&gt; { label.textContent = original; btn.disabled = false; }, 1200);
                                                                        }
                                                                    })();
                                                                "
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-white border border-purple-300 hover:bg-purple-50 rounded transition shadow-sm disabled:opacity-70"
                                                                title="Save the current content but stay in fullscreen">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            <span>Save</span>
                                                        </button>

                                                        {{-- Save & Close: flush the editor's pending save
                                                             then collapse. Triggers blur on contenteditable
                                                             to flush, then dispatches flushSave on the
                                                             editorjs-field instance via its UID. --}}
                                                        <button type="button"
                                                                onclick="
                                                                    (async () => {
                                                                        const btn = this;
                                                                        // Flush pending text edits to the data model.
                                                                        if (document.activeElement && document.activeElement.blur) {
                                                                            document.activeElement.blur();
                                                                        }
                                                                        /* Collect EVERY editor's content and PERSIST it to the DB
                                                                           in one saveContent() call before collapsing fullscreen.
                                                                           flushSave alone only updated the Livewire property and
                                                                           never wrote the section row → content was lost. */
                                                                        try {
                                                                            const eds = (typeof window.veCollectEditors === 'function') ? await window.veCollectEditors() : [];
                                                                            const patch = {};
                                                                            eds.forEach((e) =&gt; { const m = e.wireModel.match(/^sectionContent\.(.+)$/); if (m) { patch[m[1]] = e.json; } });
                                                                            const cmpEl = btn.closest('[wire\\:id]');
                                                                            const wire = cmpEl && window.Livewire ? window.Livewire.find(cmpEl.getAttribute('wire:id')) : null;
                                                                            if (wire) { await wire.saveContent(patch); }
                                                                        } catch (e) { console.warn('[save-close] saveContent failed:', e); }
                                                                        // Now collapse fullscreen
                                                                        const wrapper = btn.closest('[x-data]');
                                                                        if (wrapper && wrapper._x_dataStack && wrapper._x_dataStack[0]) {
                                                                            wrapper._x_dataStack[0].fullscreen = false;
                                                                        }
                                                                    })();
                                                                "
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded transition shadow-sm">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            <span>Save &amp; Close</span>
                                                        </button>

                                                        {{-- Plain Close: discard any pending edits since
                                                             last autosave commit and collapse. --}}
                                                        <button type="button"
                                                                @click="fullscreen = false"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                                                                title="Close without saving pending changes">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                            <span>Close</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                {{-- Scroll region: absolutely positioned BELOW the 56px
                                                     header. Explicit top:56px bottom:0 gives a concrete
                                                     height the moment it paints, so overflow-y:auto can
                                                     compute its scrollHeight immediately. No flex, no
                                                     ambiguous percentage parent height. --}}
                                                <div class="ve-fs-editor-host"
                                                     data-no-unclip="true"
                                                     style="position:absolute; top:56px; left:0; right:0; bottom:0; overflow-y:auto; overflow-x:hidden; -webkit-overflow-scrolling:touch; overscroll-behavior:contain; padding:24px 24px 24px 80px; box-sizing:border-box;">
                                                    {{-- autosave OFF in the Visual Builder: content persists
                                                         only via the explicit Save / Save & Close button (no
                                                         30s background server save, which morphed pasted/
                                                         liveHtml blocks mid-edit). --}}
                                                    <x-editorjs-field
                                                        :name="'ve.' . $field->name"
                                                        :value="$sectionContent[$field->name] ?? ''"
                                                        wire-model="sectionContent.{{ $field->name }}"
                                                        :uid="'ejs-ve-' . $field->name . ($selectedSectionId ?? 'new')"
                                                        min-height="400px"
                                                        :autosave="false"
                                                    />
                                                </div>
                                                {{-- <style> block moved out of this template to the @once
                                                     block above the x-data wrapper. Inside a <template>
                                                     the browser treats children as inert and the CSS
                                                     rules never reach the document stylesheet. --}}
                                            </div>
                                        </template>
                                    </div>
                                    @break

                                @case('agents_picker')
                                    @php
                                        $allAgents = collect();
                                        if (class_exists(\App\Models\Agent::class)) {
                                            try {
                                                $allAgents = \App\Models\Agent::query()
                                                    ->where('active', true)
                                                    ->orderBy('order')
                                                    ->orderBy('name')
                                                    ->get();
                                            } catch (\Throwable $e) { /* table missing */ }
                                        }
                                        $apSelected = $sectionContent[$field->name] ?? [];
                                        if (is_string($apSelected)) {
                                            $decoded = json_decode($apSelected, true);
                                            $apSelected = is_array($decoded) ? $decoded : [];
                                        }
                                        if (! is_array($apSelected)) $apSelected = [];
                                        $apSelected = array_map('intval', array_values($apSelected));
                                        $apFieldKey = "sectionContent.{$field->name}";
                                    @endphp
                                    {{-- Alpine-managed multi-select. We bypass wire:model (which falls
                                         back to boolean mode when the initial value isn't an array) and
                                         instead push the entire updated array via @this.set(). --}}
                                    <div class="space-y-1.5"
                                         x-data="{
                                             search: '',
                                             selected: @js($apSelected),
                                             toggle(id) {
                                                 const idx = this.selected.indexOf(id);
                                                 if (idx === -1) this.selected.push(id);
                                                 else this.selected.splice(idx, 1);
                                                 $wire.set('{{ $apFieldKey }}', [...this.selected]);
                                             },
                                             clearAll() {
                                                 this.selected = [];
                                                 $wire.set('{{ $apFieldKey }}', []);
                                             },
                                         }">
                                        @if($allAgents->isEmpty())
                                            <div class="text-xs text-gray-500 italic px-2 py-3 bg-gray-50 rounded">
                                                No agents in the database. Add some from
                                                <a href="{{ route('admin.agents.index') }}" class="text-blue-600 underline" target="_blank">Agents admin</a>.
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="search"
                                                       placeholder="Filter agents..."
                                                       class="flex-1 text-xs border border-gray-300 rounded px-2 py-1.5">
                                                <button type="button" x-show="selected.length > 0" @click="clearAll()"
                                                        class="text-[11px] text-red-600 hover:underline px-1">Clear</button>
                                            </div>
                                            <div class="max-h-72 overflow-y-auto border border-gray-200 rounded bg-white">
                                                @foreach($allAgents as $agent)
                                                    @php $aid = (int) $agent->id; @endphp
                                                    <label
                                                        x-show="!search || `{{ str_replace('`', '', $agent->name . ' ' . ($agent->role ?? '')) }}`.toLowerCase().includes(search.toLowerCase())"
                                                        :class="selected.includes({{ $aid }}) ? 'bg-blue-50 border-blue-100' : ''"
                                                        class="flex items-center gap-2 px-2 py-1.5 border-b border-gray-100 last:border-0 hover:bg-blue-50 cursor-pointer text-xs">
                                                        <input type="checkbox"
                                                               :checked="selected.includes({{ $aid }})"
                                                               @click.stop="toggle({{ $aid }})"
                                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        @if($agent->getPhotoUrl())
                                                            <img src="{{ $agent->getPhotoUrl() }}" alt="{{ $agent->name }}" class="w-7 h-7 rounded-full object-cover">
                                                        @else
                                                            <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-gray-500"><i class="fa fa-user text-[10px]"></i></div>
                                                        @endif
                                                        <span class="font-medium text-gray-800">{{ $agent->name }}</span>
                                                        @if($agent->role)
                                                            <span class="text-gray-400">— {{ $agent->role }}</span>
                                                        @endif
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="text-[11px] text-gray-500 px-1" x-text="
                                                selected.length === 0
                                                    ? 'Nothing selected — fallback shows first N active agents.'
                                                    : (selected.length + ' selected. Display order matches the agents table (admin → Agents).')
                                            "></div>
                                        @endif
                                    </div>
                                    @break

                                @case('repeater')
                                    @php
                                        $rfSettings = $field->settings;
                                        if (is_string($rfSettings)) $rfSettings = json_decode($rfSettings, true);
                                        $rfSubFields = $rfSettings['sub_fields'] ?? [];
                                        $rfItems = $sectionContent[$field->name] ?? [];
                                        if (!is_array($rfItems)) $rfItems = [];
                                    @endphp
                                    <div class="space-y-2">
                                        @foreach($rfItems as $rfIdx => $rfItem)
                                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-medium text-gray-500">Item {{ $rfIdx + 1 }}</span>
                                                    <button type="button"
                                                            wire:click="removeRepeaterItem('{{ $field->name }}', {{ $rfIdx }})"
                                                            class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                                                </div>
                                                @foreach($rfSubFields as $sf)
                                                    <div>
                                                        <label class="block text-xs text-gray-500 mb-0.5">{{ $sf['label'] ?? $sf['name'] }}</label>
                                                        @if(($sf['type'] ?? 'text') === 'textarea')
                                                            <textarea wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                      rows="2"
                                                                      class="w-full border border-gray-300 rounded px-2 py-1 text-xs"></textarea>
                                                        @elseif(($sf['type'] ?? 'text') === 'image')
                                                            <input type="text"
                                                                   wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs"
                                                                   placeholder="Image URL">
                                                        @else
                                                            <input type="text"
                                                                   wire:model="sectionContent.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                        <button type="button"
                                                wire:click="addRepeaterItem('{{ $field->name }}')"
                                                class="w-full py-1.5 border border-dashed border-gray-300 rounded-lg text-xs text-gray-500 hover:border-purple-400 hover:text-purple-600 transition">
                                            + Add Item
                                        </button>
                                    </div>
                                    @break

                                @default
                                    <input type="text"
                                           wire:model="sectionContent.{{ $field->name }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @endswitch
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Panel Footer --}}
            @if($selectedTemplate && ($selectedSectionId || $showAddPanel))
                <div class="border-t border-gray-200 flex-shrink-0">
                    @if(!$showAddPanel)
                        {{-- Save as template --}}
                        <div class="px-3 py-2 border-b border-gray-100"
                             x-data="{ open: false, name: '{{ addslashes($sectionName) }}' }">
                            <button type="button"
                                    @click="open = !open"
                                    class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                                Save as reusable template
                            </button>
                            <div x-show="open" x-cloak class="mt-1.5 flex gap-1.5">
                                <input type="text"
                                       x-model="name"
                                       placeholder="Template name..."
                                       class="flex-1 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <button type="button"
                                        @click="$wire.saveAsTemplate(name); open = false"
                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium transition">
                                    Save
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="px-3 py-2.5 flex items-center justify-between">
                        @if($showAddPanel)
                            <button type="button"
                                    x-on:click="(async () => {
                                        const eds = (typeof window.veCollectEditors === 'function') ? await window.veCollectEditors() : [];
                                        const patch = {};
                                        eds.forEach((e) => { const m = e.wireModel.match(/^sectionContent\.(.+)$/); if (m) { patch[m[1]] = e.json; } });
                                        $wire.saveSection(patch);
                                    })()"
                                    class="flex-1 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="saveSection">Add Section</span>
                                <span wire:loading wire:target="saveSection">Adding...</span>
                            </button>
                        @else
                            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                <span wire:loading wire:target="updateSection">
                                    <svg class="w-3.5 h-3.5 animate-spin text-purple-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span class="text-purple-500">Saving…</span>
                                </span>
                                <span wire:loading.remove wire:target="updateSection" class="text-gray-400">Auto-save on</span>
                            </div>
                            <button wire:click="deleteSection({{ $selectedSectionId }})"
                                    onclick="return confirm('Delete this section?')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg text-xs transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif


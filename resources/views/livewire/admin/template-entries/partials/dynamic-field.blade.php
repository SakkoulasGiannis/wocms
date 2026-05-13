{{-- Dynamic Field Renderer --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ $field->label }}
        @if($field->is_required)
            <span class="text-red-500">*</span>
        @endif
        <span class="ml-2 text-xs font-mono text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
            $content->{{ $field->name }}
        </span>
    </label>

    @if($field->description)
        <p class="text-xs text-gray-500 mb-2">{{ $field->description }}</p>
    @endif

    @php
        // Fields that drive the slug (title/name/heading or anything flagged as URL identifier)
        // need a LIVE binding so the server-side updated() hook fires as the user types,
        // populating the slug field automatically. The slug field itself is also live so we can
        // detect manual edits (and stop auto-syncing). Other text fields stay on onchange to
        // avoid unnecessary round-trips.
        $isLiveField = $field->is_url_identifier
            || in_array($field->name, ['title', 'name', 'heading', 'slug'], true);
    @endphp
    @switch($field->type)
            @case('text')
            @case('email')
            @case('url')
                @if($isLiveField)
                    {{-- Live binding: oninput fires server update via Livewire on every keystroke,
                         debounced 350ms. Plus onblur as a safety net so the value is always synced
                         when the user tabs away. Uses @this.set with true (live) so updated() fires. --}}
                    <input type="{{ $field->type }}"
                           value="{{ $this->fieldValues[$field->name] ?? '' }}"
                           oninput="clearTimeout(this._slugT); const v = this.value; this._slugT = setTimeout(() => @this.set('fieldValues.{{ $field->name }}', v, true), 350)"
                           onblur="clearTimeout(this._slugT); @this.set('fieldValues.{{ $field->name }}', this.value, true)"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                           placeholder="{{ $field->default_value ?? '' }}">
                @else
                    <input type="{{ $field->type }}"
                           value="{{ $this->fieldValues[$field->name] ?? '' }}"
                           onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                           placeholder="{{ $field->default_value ?? '' }}">
                @endif
                @break

            @case('textarea')
                <textarea rows="4"
                          onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                          placeholder="{{ $field->default_value ?? '' }}">{{ $this->fieldValues[$field->name] ?? '' }}</textarea>
                @break

            @case('wysiwyg')
                <x-editorjs-field
                    :name="$field->name"
                    :value="$fieldValues[$field->name] ?? ''"
                    wire-model="fieldValues.{{ $field->name }}"
                    :uid="'ejs-field-' . $field->name . ($entryId ?? 'new')"
                />
                @break

            @case('grapejs')
                <div wire:ignore wire:key="grapejs-{{ $field->name }}-{{ $entryId ?? 'new' }}">
                    <!-- Fullscreen Toggle Button -->
                    <div class="mb-2 flex justify-end gap-2">
                        <button type="button"
                                onclick="openCodeEditorForField('{{ $field->name }}')"
                                class="inline-flex items-center px-3 py-1 bg-indigo-700 text-white text-sm rounded hover:bg-indigo-800 transition">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Edit Code
                        </button>
                        <button type="button"
                                onclick="toggleFullscreen('gjs-container-{{ $field->name }}')"
                                class="inline-flex items-center px-3 py-1 bg-gray-700 text-white text-sm rounded hover:bg-gray-800 transition">
                            <svg class="w-4 h-4 mr-1" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                            Full Page Mode
                        </button>
                    </div>
                    <!-- GrapeJS Container -->
                    <div id="gjs-container-{{ $field->name }}"
                         class="gjs-container-wrapper">
                        <div id="gjs-{{ $field->name }}" class="gjs-editor mt-10"
                             style="height: 600px;"></div>
                    </div>
                    <textarea id="gjs-data-{{ $field->name }}"
                              style="display:none;">{{ is_array($fieldValues[$field->name] ?? '') ? json_encode($fieldValues[$field->name]) : ($fieldValues[$field->name] ?? '') }}</textarea>
                </div>
                <script>
                    // Initialize GrapeJS for this field once everything is ready
                    // wire:ignore directive prevents Livewire from touching this element
                    (function() {
                        const fieldName = '{{ $field->name }}';

                        // Only initialize if not already initialized
                        if (window.grapeEditors && window.grapeEditors[fieldName]) {
                            console.log('GrapeJS editor already exists for:', fieldName);
                            return;
                        }

                        const initField = () => {
                            if (typeof window.initializeGrapeJSEditor === 'function') {
                                window.initializeGrapeJSEditor(fieldName);
                            } else {
                                setTimeout(initField, 50);
                            }
                        };

                        // Initialize after a small delay to ensure DOM is ready
                        setTimeout(initField, 100);
                    })();
                </script>
                @break

            @case('number')
            @case('integer')
                <input type="number"
                       value="{{ $this->fieldValues[$field->name] ?? '' }}"
                       onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                       placeholder="{{ $field->default_value ?? '' }}">
                @break

            @case('decimal')
            @case('float')
                <input type="number"
                       step="0.01"
                       value="{{ $this->fieldValues[$field->name] ?? '' }}"
                       onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                       placeholder="{{ $field->default_value ?? '' }}">
                @break

            @case('checkbox')
            @case('boolean')
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           id="checkbox-{{ $field->name }}"
                           {{ ($this->fieldValues[$field->name] ?? false) ? 'checked' : '' }}
                           onchange="@this.set('fieldValues.{{ $field->name }}', this.checked, false)"
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                </label>
                <script>
                    // Initialize checkbox value on load
                    document.addEventListener('livewire:init', () => {
                        const checkbox = document.getElementById('checkbox-{{ $field->name }}');
                        if (checkbox && !checkbox.checked) {
                            @this.set('fieldValues.{{ $field->name }}', false, false);
                        }
                    });
                </script>
                @break

            @case('date')
                <input type="date"
                       value="{{ $this->fieldValues[$field->name] ?? '' }}"
                       onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                @break

            @case('datetime')
            @case('datetime-local')
                <input type="datetime-local"
                       value="{{ $this->fieldValues[$field->name] ?? '' }}"
                       onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                @break

            @case('select')
                @php
                    $settings = is_string($field->settings) ? json_decode($field->settings, true) : $field->settings;
                    $options = [];

                    if (!empty($settings['source']) && $settings['source'] === 'eloquent') {
                        // Eloquent source - fetch from database
                        $modelClass = "App\\Models\\" . $settings['model'];
                        if (class_exists($modelClass)) {
                            $query = $modelClass::query();
                            if (!empty($settings['where'])) {
                                // Simple where condition support
                                $query->whereRaw($settings['where']);
                            }
                            $records = $query->get();
                            $valueCol = $settings['value_column'] ?? 'id';
                            $labelCol = $settings['label_column'] ?? 'name';

                            foreach ($records as $record) {
                                $options[$record->{$valueCol}] = $record->{$labelCol};
                            }
                        }
                    } else {
                        // Manual options - format: "value:label" per line
                        if (!empty($settings['options'])) {
                            $lines = explode("\n", $settings['options']);
                            foreach ($lines as $line) {
                                $line = trim($line);
                                if (empty($line)) continue;

                                if (strpos($line, ':') !== false) {
                                    list($value, $label) = explode(':', $line, 2);
                                    $options[trim($value)] = trim($label);
                                } else {
                                    $options[$line] = $line;
                                }
                            }
                        }
                    }
                @endphp

                <select onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                    <option value="">-- Select {{ $field->label }} --</option>
                    @foreach($options as $value => $label)
                        <option value="{{ $value }}" {{ ($this->fieldValues[$field->name] ?? '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @break

            @case('relation')
                @php
                    $settings = is_string($field->settings) ? json_decode($field->settings, true) : $field->settings;
                    $relatedRecords = [];

                    if (!empty($settings['model'])) {
                        $modelClass = "App\\Models\\" . $settings['model'];
                        if (class_exists($modelClass)) {
                            $query = $modelClass::query();

                            // Apply scopes if defined
                            if (!empty($settings['scope'])) {
                                $scope = $settings['scope'];
                                if (method_exists($modelClass, 'scope' . ucfirst($scope))) {
                                    $query->{$scope}();
                                }
                            }

                            // Apply where conditions
                            if (!empty($settings['where'])) {
                                $query->whereRaw($settings['where']);
                            }

                            $relatedRecords = $query->get();
                            $valueCol = $settings['value_column'] ?? 'id';
                            $labelCol = $settings['label_column'] ?? 'title';
                        }
                    }

                    $relationType = $settings['type'] ?? 'belongsTo';
                    $isMultiple = in_array($relationType, ['hasMany', 'belongsToMany']);
                @endphp

                @if($isMultiple)
                    {{-- Multiple selection for hasMany/belongsToMany --}}
                    <select multiple
                            size="5"
                            onchange="@this.set('fieldValues.{{ $field->name }}', Array.from(this.selectedOptions).map(o => o.value), false)"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                        @foreach($relatedRecords as $record)
                            @php
                                $currentValue = $this->fieldValues[$field->name] ?? [];
                                $isSelected = is_array($currentValue) && in_array($record->{$valueCol}, $currentValue);
                            @endphp
                            <option value="{{ $record->{$valueCol} }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $record->{$labelCol} }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</p>
                @else
                    {{-- Single selection for belongsTo/hasOne --}}
                    <select onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                        <option value="">-- Select {{ $field->label }} --</option>
                        @foreach($relatedRecords as $record)
                            <option value="{{ $record->{$valueCol} }}" {{ ($this->fieldValues[$field->name] ?? '') == $record->{$valueCol} ? 'selected' : '' }}>
                                {{ $record->{$labelCol} }}
                            </option>
                        @endforeach
                    </select>
                @endif
                @break

            @case('group')
                @php
                    $settings = is_string($field->settings) ? json_decode($field->settings, true) : $field->settings;
                    $groupValue = $this->fieldValues[$field->name] ?? [];
                    if (is_string($groupValue)) {
                        $groupValue = json_decode($groupValue, true) ?? [];
                    }

                    // Parse sub_fields string: "name:text:Name,description:textarea:Description"
                    $groupFields = [];
                    if (!empty($settings['sub_fields'])) {
                        $subFieldsStr = $settings['sub_fields'];
                        $fieldParts = explode(',', $subFieldsStr);
                        foreach ($fieldParts as $fieldPart) {
                            $parts = explode(':', trim($fieldPart));
                            if (count($parts) >= 3) {
                                $groupFields[] = [
                                    'name' => $parts[0],
                                    'type' => $parts[1],
                                    'label' => $parts[2],
                                    'required' => false
                                ];
                            } elseif (count($parts) == 2) {
                                // Backwards compatibility: name:type format
                                $groupFields[] = [
                                    'name' => $parts[0],
                                    'type' => $parts[1],
                                    'label' => ucfirst($parts[0]),
                                    'required' => false
                                ];
                            }
                        }
                    }
                @endphp

                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                    @foreach($groupFields as $subField)
                        <div class="mb-4 last:mb-0">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ $subField['label'] }}
                                @if($subField['required'] ?? false)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if($subField['type'] === 'text' || $subField['type'] === 'email' || $subField['type'] === 'url')
                                <input type="{{ $subField['type'] }}"
                                       value="{{ $groupValue[$subField['name']] ?? '' }}"
                                       onchange="let group = @this.fieldValues['{{ $field->name }}'] || {}; if(typeof group === 'string') group = JSON.parse(group); group['{{ $subField['name'] }}'] = this.value; @this.set('fieldValues.{{ $field->name }}', JSON.stringify(group), false)"
                                       class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                            @elseif($subField['type'] === 'textarea')
                                <textarea rows="3"
                                          onchange="let group = @this.fieldValues['{{ $field->name }}'] || {}; if(typeof group === 'string') group = JSON.parse(group); group['{{ $subField['name'] }}'] = this.value; @this.set('fieldValues.{{ $field->name }}', JSON.stringify(group), false)"
                                          class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">{{ $groupValue[$subField['name']] ?? '' }}</textarea>
                            @elseif($subField['type'] === 'checkbox')
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           {{ ($groupValue[$subField['name']] ?? false) ? 'checked' : '' }}
                                           onchange="let group = @this.fieldValues['{{ $field->name }}'] || {}; if(typeof group === 'string') group = JSON.parse(group); group['{{ $subField['name'] }}'] = this.checked; @this.set('fieldValues.{{ $field->name }}', JSON.stringify(group), false)"
                                           class="rounded border-gray-300 text-blue-600">
                                    <span class="ml-2 text-sm">{{ $subField['label'] }}</span>
                                </label>
                            @endif
                        </div>
                    @endforeach
                </div>
                @break

            @case('repeater')
                @php
                    $settings = is_string($field->settings) ? json_decode($field->settings, true) : $field->settings;
                    $repeaterValue = $this->fieldValues[$field->name] ?? [];
                    if (is_string($repeaterValue)) {
                        $repeaterValue = json_decode($repeaterValue, true) ?? [];
                    }
                    if (!is_array($repeaterValue)) {
                        $repeaterValue = [];
                    }

                    // Parse sub_fields string: "title:text:Title,description:textarea:Description"
                    $repeaterFields = [];
                    if (!empty($settings['sub_fields'])) {
                        $subFieldsStr = $settings['sub_fields'];
                        $fieldParts = explode(',', $subFieldsStr);
                        foreach ($fieldParts as $fieldPart) {
                            $parts = explode(':', trim($fieldPart));
                            if (count($parts) >= 3) {
                                $repeaterFields[] = [
                                    'name' => $parts[0],
                                    'type' => $parts[1],
                                    'label' => $parts[2],
                                    'required' => false
                                ];
                            } elseif (count($parts) == 2) {
                                // Backwards compatibility: name:type format
                                $repeaterFields[] = [
                                    'name' => $parts[0],
                                    'type' => $parts[1],
                                    'label' => ucfirst($parts[0]),
                                    'required' => false
                                ];
                            }
                        }
                    }

                    $minItems = $settings['min'] ?? 0;
                    $maxItems = $settings['max'] ?? 10;
                @endphp

                <div class="space-y-3" x-data="repeaterField_{{ $field->name }}()" wire:ignore.self>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-gray-700">Item <span x-text="index + 1"></span></h4>
                                <button type="button"
                                        @click="removeItem(index)"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    Remove
                                </button>
                            </div>

                            @foreach($repeaterFields as $subField)
                                <div class="mb-3 last:mb-0">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ $subField['label'] }}
                                        @if($subField['required'] ?? false)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if($subField['type'] === 'text' || $subField['type'] === 'email' || $subField['type'] === 'url')
                                        <input type="{{ $subField['type'] }}"
                                               :value="item['{{ $subField['name'] }}'] || ''"
                                               @change="updateItemField(index, '{{ $subField['name'] }}', $event.target.value)"
                                               class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                    @elseif($subField['type'] === 'textarea')
                                        <textarea rows="2"
                                                  :value="item['{{ $subField['name'] }}'] || ''"
                                                  @change="updateItemField(index, '{{ $subField['name'] }}', $event.target.value)"
                                                  class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"></textarea>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </template>

                    <button type="button"
                            @click="addItem"
                            :disabled="items.length >= {{ $maxItems }}"
                            class="inline-flex items-center px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Item
                    </button>
                </div>

                <script>
                    function repeaterField_{{ $field->name }}() {
                        return {
                            items: @json($repeaterValue),
                            init() {
                                // Ensure minimum items
                                while (this.items.length < {{ $minItems }}) {
                                    this.addItem();
                                }
                            },
                            addItem() {
                                if (this.items.length < {{ $maxItems }}) {
                                    const newItem = {};
                                    @foreach($repeaterFields as $subField)
                                        newItem['{{ $subField['name'] }}'] = '';
                                    @endforeach
                                    this.items.push(newItem);
                                    this.syncToLivewire();
                                }
                            },
                            removeItem(index) {
                                if (this.items.length > {{ $minItems }}) {
                                    this.items.splice(index, 1);
                                    this.syncToLivewire();
                                }
                            },
                            updateItemField(index, fieldName, value) {
                                this.items[index][fieldName] = value;
                                this.syncToLivewire();
                            },
                            syncToLivewire() {
                                @this.set('fieldValues.{{ $field->name }}', JSON.stringify(this.items), false);
                            }
                        }
                    }
                </script>
                @break

            @case('image')
                @php
                    $currentImageUrl = null;
                    if ($entryId && $entry && method_exists($entry, 'getFirstMediaUrl')) {
                        try { $currentImageUrl = $entry->getFirstMediaUrl($field->name); } catch (\Throwable $e) {}
                    }
                @endphp
                <div x-data="{
                        dragOver: false,
                        previewUrl: @js($currentImageUrl ?: ''),
                        fileName: '',
                        fileSize: 0,
                        handleFiles(files) {
                            const file = files && files[0];
                            if (!file || !file.type.startsWith('image/')) return;
                            this.fileName = file.name;
                            this.fileSize = file.size;
                            this.previewUrl = URL.createObjectURL(file);
                            window.pendingFileUploads = window.pendingFileUploads || {};
                            window.pendingFileUploads['{{ $field->name }}'] = file;
                        },
                        clearImage() {
                            this.previewUrl = '';
                            this.fileName = '';
                            this.fileSize = 0;
                            if (window.pendingFileUploads) delete window.pendingFileUploads['{{ $field->name }}'];
                            const inp = $refs.fileInput;
                            if (inp) inp.value = '';
                        }
                    }"
                    @dragover.prevent="dragOver = true"
                    @dragleave.prevent="dragOver = false"
                    @drop.prevent="dragOver = false; handleFiles($event.dataTransfer.files);"
                    @click="$refs.fileInput.click()"
                    :class="dragOver ? 'border-brand bg-brand/5' : (previewUrl ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100')"
                    class="cursor-pointer rounded-lg border-2 border-dashed p-6 text-center transition-colors">

                    <input type="file" accept="image/*" x-ref="fileInput" @change="handleFiles($event.target.files)" class="hidden">

                    <template x-if="!previewUrl">
                        <div>
                            <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <p class="mt-2 text-sm font-medium text-slate-700">Drag &amp; drop image here, or click to browse</p>
                            <p class="mt-1 text-xs text-slate-500">JPG, PNG, WebP — max 10 MB</p>
                        </div>
                    </template>

                    <template x-if="previewUrl">
                        <div class="flex items-center gap-4" @click.stop>
                            <img :src="previewUrl" alt="Preview" class="h-24 w-24 object-cover rounded-md ring-1 ring-slate-200">
                            <div class="flex-1 text-left">
                                <p class="text-sm font-medium text-slate-900" x-text="fileName || 'Current image'"></p>
                                <p x-show="fileSize > 0" class="text-xs text-slate-500" x-text="(fileSize / 1024 / 1024).toFixed(2) + ' MB · will upload on Save'"></p>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" @click="$refs.fileInput.click()" class="text-xs font-medium text-brand hover:text-brand-dark">Replace</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click="clearImage()" class="text-xs font-medium text-rose-600 hover:text-rose-700">Remove</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                @break

            @case('gallery')
                @php
                    $existingGallery = [];
                    if ($entryId && $entry && method_exists($entry, 'getMedia')) {
                        try {
                            foreach ($entry->getMedia($field->name) as $m) {
                                $existingGallery[] = [
                                    'id' => $m->id,
                                    'url' => $m->getFullUrl(),
                                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getFullUrl('thumb') : $m->getFullUrl(),
                                    'name' => $m->name,
                                ];
                            }
                        } catch (\Throwable $e) {}
                    }
                @endphp
                <div x-data="{
                        dragOver: false,
                        existing: @js($existingGallery),
                        pending: [],
                        removed: [],
                        addFiles(files) {
                            for (const f of Array.from(files || [])) {
                                if (!f.type.startsWith('image/')) continue;
                                this.pending.push({ name: f.name, size: f.size, url: URL.createObjectURL(f), file: f });
                            }
                            this.syncToWindow();
                        },
                        removePending(idx) {
                            this.pending.splice(idx, 1);
                            this.syncToWindow();
                        },
                        removeExisting(id) {
                            const found = this.existing.find(m => m.id === id);
                            if (!found) return;
                            this.existing = this.existing.filter(m => m.id !== id);
                            this.removed.push(id);
                            // Stash on window so saveEntry() in entry-form can hand it to Livewire
                            window.galleryRemoveIds = window.galleryRemoveIds || {};
                            window.galleryRemoveIds['{{ $field->name }}'] = JSON.stringify(this.removed);
                        },
                        syncToWindow() {
                            window.pendingFileUploads = window.pendingFileUploads || {};
                            window.pendingFileUploads['{{ $field->name }}'] = this.pending.map(p => p.file);
                        }
                    }">

                    {{-- Drop zone --}}
                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; addFiles($event.dataTransfer.files);"
                        @click="$refs.galleryInput.click()"
                        :class="dragOver ? 'border-brand bg-brand/5' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'"
                        class="cursor-pointer rounded-lg border-2 border-dashed p-6 text-center transition-colors">
                        <input type="file" accept="image/*" multiple x-ref="galleryInput" @change="addFiles($event.target.files)" class="hidden">
                        <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        <p class="mt-2 text-sm font-medium text-slate-700">Drag &amp; drop one or more images, or click to browse</p>
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, WebP — bulk upload supported</p>
                    </div>

                    {{-- Existing gallery --}}
                    <template x-if="existing.length > 0">
                        <div class="mt-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="`Existing (${existing.length})`"></p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                                <template x-for="item in existing" :key="item.id">
                                    <div class="group relative aspect-square overflow-hidden rounded-md ring-1 ring-slate-200 bg-slate-50">
                                        <img :src="item.thumb" :alt="item.name" loading="lazy" class="h-full w-full object-cover">
                                        <button type="button" @click="removeExisting(item.id)"
                                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-rose-700"
                                                title="Remove on save">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Pending (newly selected) --}}
                    <template x-if="pending.length > 0">
                        <div class="mt-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-emerald-700" x-text="`To upload on save (${pending.length})`"></p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                                <template x-for="(item, idx) in pending" :key="idx">
                                    <div class="group relative aspect-square overflow-hidden rounded-md ring-1 ring-emerald-300 bg-emerald-50">
                                        <img :src="item.url" :alt="item.name" class="h-full w-full object-cover">
                                        <button type="button" @click="removePending(idx)"
                                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-slate-700 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-slate-800"
                                                title="Cancel">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                @break

            @default
                <p class="text-sm text-gray-500 italic">
                    Field type '{{ $field->type }}' not yet implemented in dynamic renderer
                </p>
        @endswitch

        @error('fieldValues.' . $field->name)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        @error('uploadedFiles.' . $field->name)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
</div>

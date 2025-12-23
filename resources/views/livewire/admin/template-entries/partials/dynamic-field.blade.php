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

    @switch($field->type)
            @case('text')
            @case('email')
            @case('url')
                <input type="{{ $field->type }}"
                       value="{{ $this->fieldValues[$field->name] ?? '' }}"
                       onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                       placeholder="{{ $field->default_value ?? '' }}">
                @break

            @case('textarea')
                <textarea rows="4"
                          onchange="@this.set('fieldValues.{{ $field->name }}', this.value, false)"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                          placeholder="{{ $field->default_value ?? '' }}">{{ $this->fieldValues[$field->name] ?? '' }}</textarea>
                @break

            @case('wysiwyg')
                <div wire:ignore>
                    <input id="trix-{{ $field->name }}"
                           type="hidden"
                           value="{{ $fieldValues[$field->name] ?? '' }}">
                    <trix-editor input="trix-{{ $field->name }}"
                                 data-field-name="{{ $field->name }}"
                                 class="trix-content"></trix-editor>
                </div>
                <script>
                    document.addEventListener('livewire:init', () => {
                        const initTrix = () => {
                            const hiddenInput = document.querySelector('#trix-{{ $field->name }}');
                            const editor = document.querySelector('[input="trix-{{ $field->name }}"]');

                            if (editor && !editor.hasListener) {
                                // Update hidden input on change (for internal state)
                                editor.addEventListener('trix-change', function (e) {
                                    if (hiddenInput) {
                                        hiddenInput.value = this.value;
                                    }
                                });

                                // Sync to Livewire only on blur (when user leaves the editor)
                                editor.addEventListener('trix-blur', function (e) {
                                    @this.set('fieldValues.{{ $field->name }}', this.value, false);
                                });

                                editor.hasListener = true;
                            }
                        };
                        initTrix();
                        Livewire.hook('morph.updated', () => {
                            setTimeout(initTrix, 100);
                        });
                    });
                </script>
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
                              style="display:none;">{{ $fieldValues[$field->name] ?? '' }}</textarea>
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
                <input type="file"
                       accept="image/*"
                       id="file-{{ $field->name }}"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                {{-- Show upload progress --}}
                <div id="upload-progress-{{ $field->name }}" style="display: none;" class="mt-2">
                    <div class="flex items-center text-sm text-blue-600">
                        <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="upload-progress-text-{{ $field->name }}">Uploading...</span>
                    </div>
                </div>

                {{-- Show current image if exists --}}
                @if($entryId && $entry && method_exists($entry, 'getFirstMediaUrl') && $entry->getFirstMediaUrl($field->name))
                    <div class="mt-2">
                        <p class="text-xs text-gray-500 mb-1">Current image:</p>
                        <img src="{{ $entry->getFirstMediaUrl($field->name) }}" alt="Current image" class="max-w-xs rounded border">
                    </div>
                @endif

                {{-- Store file for upload on save --}}
                <script>
                    document.addEventListener('livewire:init', () => {
                        const fileInput = document.getElementById('file-{{ $field->name }}');
                        const progressDiv = document.getElementById('upload-progress-{{ $field->name }}');

                        if (fileInput) {
                            fileInput.addEventListener('change', function(e) {
                                const file = e.target.files[0];
                                console.log('=== File selected (will upload on save) ===');
                                console.log('Field: {{ $field->name }}');
                                console.log('File:', file?.name);
                                console.log('File size:', file?.size, 'bytes');

                                if (file) {
                                    // Store file for later upload (when user clicks Save)
                                    window.pendingFileUploads = window.pendingFileUploads || {};
                                    window.pendingFileUploads['{{ $field->name }}'] = file;

                                    // Show indication that file is selected
                                    if (progressDiv) {
                                        const progressText = document.getElementById('upload-progress-text-{{ $field->name }}');
                                        if (progressText) {
                                            progressText.textContent = 'File selected: ' + file.name + ' (will upload on save)';
                                        }
                                        progressDiv.style.display = 'block';
                                    }

                                    console.log('File stored for upload on save');
                                }
                            });
                        }
                    });
                </script>
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

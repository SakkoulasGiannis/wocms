<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $templateId ? 'Edit' : 'Create' }} Section Template
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $templateId ? 'Update section template settings and fields' : 'Create a new section template' }}
                </p>
            </div>
            <a href="{{ route('admin.section-templates.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">
                <i class="fa fa-arrow-left mr-2"></i>
                Back to List
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model.live="name"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Slug -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                        <input type="text" wire:model="slug"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">Used to identify the template (auto-generated from name)</p>
                        @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select wire:model="category"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="2"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- HTML Template -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">HTML Template</h2>

                <div>
                    <p class="text-sm text-gray-600 mb-2">
                        Use <code class="bg-gray-100 px-1 rounded">@{{ field_name }}</code> to reference field values.
                        Example: <code class="bg-gray-100 px-1 rounded">@{{ title }}</code>, <code class="bg-gray-100 px-1 rounded">@{{ image }}</code>
                    </p>
                    <textarea wire:model="html_template" rows="15"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"></textarea>
                    @error('html_template') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Fields -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Template Fields</h2>
                    <button wire:click="openAddFieldModal"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                        <i class="fa fa-plus mr-1"></i>
                        Add Field
                    </button>
                </div>

                @if(count($fields) > 0)
                    <div class="space-y-2">
                        @foreach($fields as $index => $field)
                            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $field['label'] }}</div>
                                    <div class="text-sm text-gray-500">
                                        <code class="bg-white px-1 rounded">@{{ $field['name'] }}</code>
                                        <span class="mx-1">•</span>
                                        <span>{{ $fieldTypes[$field['type']] ?? $field['type'] }}</span>
                                        @if($field['is_required'])
                                            <span class="mx-1">•</span>
                                            <span class="text-red-600">Required</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Move buttons -->
                                    <button wire:click="moveFieldUp({{ $index }})"
                                            @if($index === 0) disabled @endif
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                        <i class="fa fa-arrow-up"></i>
                                    </button>
                                    <button wire:click="moveFieldDown({{ $index }})"
                                            @if($index === count($fields) - 1) disabled @endif
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                        <i class="fa fa-arrow-down"></i>
                                    </button>

                                    <!-- Edit button -->
                                    <button wire:click="editField({{ $index }})"
                                            class="text-blue-600 hover:text-blue-700">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <!-- Delete button -->
                                    <button wire:click="deleteField({{ $index }})"
                                            wire:confirm="Are you sure you want to delete this field?"
                                            class="text-red-600 hover:text-red-700">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fa fa-cube text-4xl mb-2"></i>
                        <p>No fields added yet</p>
                        <p class="text-sm">Click "Add Field" to create your first field</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Save Button -->
            <div class="bg-white rounded-lg shadow p-6">
                <button wire:click="save"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                    <i class="fa fa-save mr-2"></i>
                    {{ $templateId ? 'Update' : 'Create' }} Template
                </button>
            </div>

            <!-- Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Settings</h3>

                <div class="space-y-4">
                    <!-- Is Active -->
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="is_active" id="is_active"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                    </div>

                    <!-- Order -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                        <input type="number" wire:model="order" min="0"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            @if($templateId && $template)
                <!-- Template Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Template Info</h3>

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">ID:</span>
                            <span class="text-gray-900">{{ $template->id }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Type:</span>
                            <span class="text-gray-900">
                                @if($template->is_system)
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs">System</span>
                                @else
                                    <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full text-xs">Custom</span>
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Created:</span>
                            <span class="text-gray-900">{{ $template->created_at->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Updated:</span>
                            <span class="text-gray-900">{{ $template->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Add/Edit Field Modal -->
    @if($showAddFieldModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeAddFieldModal">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ $editingFieldIndex !== null ? 'Edit' : 'Add' }} Field
                </h2>
            </div>

            <div class="p-6 space-y-4">
                <!-- Label -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label *</label>
                    <input type="text" wire:model="fieldForm.label"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('fieldForm.label') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Name (Field Key) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Name (Key) *</label>
                    <input type="text" wire:model="fieldForm.name"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm">
                    <p class="text-xs text-gray-500 mt-1">Used in template as @{{ field_name }}</p>
                    @error('fieldForm.name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Type *</label>
                    <select wire:model="fieldForm.type"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($fieldTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('fieldForm.type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Default Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Value</label>
                    <input type="text" wire:model="fieldForm.default_value"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Options (for select fields) -->
                @if($fieldForm['type'] === 'select')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Options (one per line)</label>
                        <textarea wire:model="fieldForm.options" rows="4"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Enter each option on a new line</p>
                    </div>
                @endif

                <!-- Is Required -->
                <div class="flex items-center">
                    <input type="checkbox" wire:model="fieldForm.is_required" id="field_is_required"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="field_is_required" class="ml-2 text-sm text-gray-700">Required field</label>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button wire:click="closeAddFieldModal"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                    Cancel
                </button>
                <button wire:click="saveField"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    {{ $editingFieldIndex !== null ? 'Update' : 'Add' }} Field
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

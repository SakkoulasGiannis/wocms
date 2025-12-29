@section('title', $templateId ? 'Edit Template' : 'Create Template')
@section('page-title', $templateId ? 'Edit Template' : 'Create Template')

<div>
    <form wire:submit.prevent="save">
        <div class="space-y-6">

            <!-- Actions Bar -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.templates.index') }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Templates
                </a>
                <div class="flex items-center space-x-3">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Template
                    </button>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Template Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model.live="name"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                               placeholder="e.g., Blog Post">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Slug <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model="slug"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                               placeholder="blog-post">
                        @error('slug')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-3">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model="useSlugPrefix"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Include template slug in URL
                                    <span class="text-xs text-gray-500">(entries will use /<strong>{{ $slug ?: 'template-slug' }}</strong>/{entry-slug})</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea wire:model="description"
                              rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                              placeholder="Brief description of this template..."></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Render Mode <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="render_mode"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                        <option value="full_page_grapejs">Full Page GrapeJS - Entire page with drag & drop builder</option>
                        <option value="sections">Page Sections - Build with multiple flexible sections</option>
                        <option value="simple_content">Simple Content - Basic WYSIWYG editor</option>
                    </select>
                    @error('render_mode')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">
                        <strong>Full Page GrapeJS:</strong> One big page builder editor.<br>
                        <strong>Sections:</strong> Multiple sections with GrapeJS, WYSIWYG, or preset templates (hero, features, etc).<br>
                        <strong>Simple:</strong> Just a basic WYSIWYG text editor.
                    </p>
                </div>

                <div class="mt-6">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model="is_active"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Template is active</span>
                    </label>
                </div>

                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model="is_public"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Publicly accessible (Frontend)</span>
                    </label>
                    <p class="mt-1 ml-6 text-sm text-gray-500">If unchecked, this template's content will only be accessible from the admin panel</p>
                </div>
            </div>

            <!-- Menu Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Menu Settings</h2>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model.live="show_in_menu"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Show in admin menu</span>
                    </label>
                    <p class="mt-1 ml-6 text-xs text-gray-500">
                        When enabled, this template will appear in the admin sidebar menu (e.g., Posts, Products, etc.)
                    </p>
                </div>

                @if($show_in_menu)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Menu Label <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   wire:model="menu_label"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                   placeholder="e.g., Posts, Products, Events">
                            @error('menu_label')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">The label that will appear in the sidebar menu</p>
                        </div>

                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Menu Icon
                            </label>

                            {{-- Icon Preview --}}
                            @if(!empty($icon))
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <span class="text-sm text-gray-600">Preview:</span>
                                    <x-hero-icon name="{{ $icon }}" class="w-8 h-8 text-gray-700" />
                                    <code class="text-xs bg-white px-2 py-1 rounded">{{ $icon }}</code>
                                </div>
                            @endif

                            {{-- Hero Icons Dropdown --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Choose an Icon
                                </label>
                                <select
                                    wire:model.live="icon"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                    <option value="">-- Select an icon --</option>
                                    @foreach(\App\Services\IconLibrary::getGroupedOptions() as $category => $icons)
                                        <optgroup label="{{ $category }}">
                                            @foreach($icons as $iconName => $label)
                                                <option value="{{ $iconName }}">{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select from Hero Icons library</p>
                            </div>

                            {{-- Custom Icon Name Input --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Or Enter Custom Icon Name
                                </label>
                                <input type="text"
                                       wire:model.live="icon"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                       placeholder="Emoji (🚀) or SVG path (M9 12l2...)">
                                @error('icon')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">
                                    Enter an emoji or Heroicons SVG path.
                                    <a href="https://heroicons.com" target="_blank" class="text-blue-600 hover:underline">Browse Heroicons →</a>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Menu Order
                            </label>
                            <input type="number"
                                   wire:model="menu_order"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                   placeholder="0">
                            @error('menu_order')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Lower numbers appear first in the menu</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Family Settings -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Family Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Parent Template
                        </label>
                        <select wire:model="parent_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                            <option value="">None (Root Template)</option>
                            @foreach($availableTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Select the parent template in the hierarchy (defaults to Home for new templates)
                        </p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model="allow_children"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">May pages using this template have children?</span>
                        </label>
                        <p class="mt-1 ml-6 text-xs text-gray-500">
                            When enabled, pages with this template can have child pages
                        </p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model="allow_new_pages"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Can this template be used for new pages?</span>
                        </label>
                        <p class="mt-1 ml-6 text-xs text-gray-500">
                            When disabled, this template cannot be selected when creating new pages
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Allowed Template(s) for Parents
                        </label>
                        <select wire:model="allowed_parent_templates"
                                multiple
                                size="5"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                            @foreach($availableTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Select templates allowed as parents. If none selected, any parent template is allowed. Hold Ctrl/Cmd to select multiple.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Allowed Template(s) for Children
                        </label>
                        <select wire:model="allowed_child_templates"
                                multiple
                                size="5"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                            @foreach($availableTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Select templates allowed as children. If none selected, any child template is allowed. Hold Ctrl/Cmd to select multiple.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Template Content Type -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Template Content</h2>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model.live="requires_database"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Requires Database</span>
                    </label>
                    <p class="mt-1 ml-6 text-xs text-gray-500">
                        When enabled, a database table and model will be created for this template
                    </p>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox"
                               wire:model.live="has_physical_file"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Use physical Blade file</span>
                    </label>
                    <p class="mt-1 ml-6 text-xs text-gray-500">
                        When enabled, a physical .blade.php file will be created in resources/views/
                    </p>
                </div>

                @if($has_physical_file)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            File Path
                        </label>
                        <input type="text"
                               wire:model="file_path"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                               placeholder="templates/blog-post.blade.php">
                        @error('file_path')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Path relative to resources/views/
                        </p>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            HTML Content
                        </label>
                        <textarea wire:model="html_content"
                                  rows="10"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 font-mono text-sm"
                                  placeholder="<div>@{{ $field_name }}</div>"></textarea>
                        @error('html_content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Use Blade syntax. Fields will be available as variables (e.g., @{{ $field_name }})
                        </p>
                    </div>
                @endif
            </div>

            <!-- Field Builder -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Template Fields</h2>
                    <button type="button"
                            wire:click="addField"
                            class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Field
                    </button>
                </div>

                @if(count($fields) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                        ⋮⋮
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Label
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Default
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                        Required
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                        Show in Table
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20" title="Searchable in index">
                                        🔍
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20" title="Filterable in index">
                                        🎯
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20" title="URL identifier (one per template)">
                                        👁️
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24" title="Column Position">
                                        📍
                                    </th>
                                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" wire:sortable="updateFieldOrder">
                                @foreach($fields as $index => $field)
                                    <tr wire:key="field-{{ $field['name'] ?? $index }}-{{ $index }}"
                                        wire:sortable.item="{{ $index }}"
                                        data-field-index="{{ $index }}"
                                        class="hover:bg-gray-50 cursor-move">
                                        <!-- Drag Handle -->
                                        <td class="px-3 py-3 whitespace-nowrap" wire:sortable.handle>
                                            <div class="flex items-center justify-center text-gray-400 hover:text-gray-600 cursor-grab active:cursor-grabbing">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                                                </svg>
                                            </div>
                                        </td>

                                        <!-- Field Name -->
                                        <td class="px-3 py-3">
                                            <input type="text"
                                                   wire:model.blur="fields.{{ $index }}.name"
                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border"
                                                   placeholder="field_name">
                                            @error("fields.{$index}.name")
                                                <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Label -->
                                        <td class="px-3 py-3">
                                            <input type="text"
                                                   wire:model="fields.{{ $index }}.label"
                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border"
                                                   placeholder="Label">
                                            @error("fields.{$index}.label")
                                                <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Type -->
                                        <td class="px-3 py-3">
                                            <select wire:model="fields.{{ $index }}.type"
                                                    class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border">
                                                @foreach($fieldTypes as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <!-- Default Value -->
                                        <td class="px-3 py-3">
                                            <input type="text"
                                                   wire:model="fields.{{ $index }}.default_value"
                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border"
                                                   placeholder="Default">
                                        </td>

                                        <!-- Description -->
                                        <td class="px-3 py-3">
                                            <input type="text"
                                                   wire:model="fields.{{ $index }}.description"
                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border"
                                                   placeholder="Help text...">
                                        </td>

                                        <!-- Required -->
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.is_required"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>

                                        <!-- Show in Table -->
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.show_in_table"
                                                   class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring-green-500">
                                        </td>

                                        <!-- Searchable -->
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.is_searchable"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                   title="Searchable in index list">
                                        </td>

                                        <!-- Filterable -->
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.is_filterable"
                                                   class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                   title="Filterable in index list">
                                        </td>

                                        <!-- URL Identifier -->
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   wire:model="fields.{{ $index }}.is_url_identifier"
                                                   wire:click="ensureSingleUrlIdentifier({{ $index }})"
                                                   class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                   title="URL identifier (only one per template)">
                                        </td>

                                        <!-- Column Position -->
                                        <td class="px-3 py-3">
                                            <select wire:model="fields.{{ $index }}.column_position"
                                                    class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5 p-2 border">
                                                <option value="main">Main</option>
                                                <option value="sidebar">Sidebar</option>
                                            </select>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-3 py-3 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                @if($index > 0)
                                                    <button type="button"
                                                            wire:click="moveFieldUp({{ $index }})"
                                                            class="text-gray-400 hover:text-gray-600"
                                                            title="Move up">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                        </svg>
                                                    </button>
                                                @else
                                                    <span class="w-5 h-5 inline-block"></span>
                                                @endif

                                                @if($index < count($fields) - 1)
                                                    <button type="button"
                                                            wire:click="moveFieldDown({{ $index }})"
                                                            class="text-gray-400 hover:text-gray-600"
                                                            title="Move down">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>
                                                @else
                                                    <span class="w-5 h-5 inline-block"></span>
                                                @endif

                                                <button type="button"
                                                        wire:click="removeField({{ $index }})"
                                                        class="text-red-600 hover:text-red-900"
                                                        title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Select Field Settings Row --}}
                                    @if($field['type'] === 'select')
                                        <tr wire:key="field-settings-{{ $index }}" class="bg-green-50">
                                            <td colspan="12" class="px-3 py-3">
                                                <div class="space-y-3">
                                                    <label class="block text-xs font-semibold text-gray-700">
                                                        Select Field Configuration
                                                    </label>

                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">Source Type</label>
                                                        <select wire:model="fields.{{ $index }}.settings.source"
                                                                class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border">
                                                            <option value="manual">Manual Options</option>
                                                            <option value="eloquent">Eloquent Model</option>
                                                        </select>
                                                    </div>

                                                    @if(($field['settings']['source'] ?? 'manual') === 'manual')
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Options (one per line: value:label)</label>
                                                            <textarea wire:model="fields.{{ $index }}.settings.options"
                                                                      rows="4"
                                                                      class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                      placeholder="1:Option One&#10;2:Option Two&#10;3:Option Three"></textarea>
                                                            <p class="text-xs text-gray-600 mt-1">
                                                                <strong>Format:</strong> value:label (one per line)
                                                            </p>
                                                        </div>
                                                    @else
                                                        <div class="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Model Name</label>
                                                                <input type="text"
                                                                       wire:model="fields.{{ $index }}.settings.model"
                                                                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                       placeholder="Page">
                                                                <p class="text-xs text-gray-500 mt-0.5">e.g., Page, Blog, Service</p>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Where Condition (optional)</label>
                                                                <input type="text"
                                                                       wire:model="fields.{{ $index }}.settings.where"
                                                                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                       placeholder="status = 'active'">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Value Column</label>
                                                                <input type="text"
                                                                       wire:model="fields.{{ $index }}.settings.value_column"
                                                                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                       placeholder="id">
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Label Column</label>
                                                                <input type="text"
                                                                       wire:model="fields.{{ $index }}.settings.label_column"
                                                                       class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                       placeholder="title">
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    {{-- Relation Field Settings Row --}}
                                    @if($field['type'] === 'relation')
                                        <tr wire:key="field-settings-{{ $index }}" class="bg-purple-50">
                                            <td colspan="12" class="px-3 py-3">
                                                <div class="space-y-3">
                                                    <label class="block text-xs font-semibold text-gray-700">
                                                        Relation Field Configuration
                                                    </label>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Model Name</label>
                                                            <input type="text"
                                                                   wire:model="fields.{{ $index }}.settings.model"
                                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                   placeholder="Page">
                                                            <p class="text-xs text-gray-500 mt-0.5">e.g., Page, Blog, Service</p>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Relation Type</label>
                                                            <select wire:model="fields.{{ $index }}.settings.type"
                                                                    class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border">
                                                                <option value="belongsTo">Belongs To (Single)</option>
                                                                <option value="hasMany">Has Many (Multiple)</option>
                                                                <option value="belongsToMany">Belongs To Many (Multiple)</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Scope (optional)</label>
                                                            <input type="text"
                                                                   wire:model="fields.{{ $index }}.settings.scope"
                                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                   placeholder="active">
                                                            <p class="text-xs text-gray-500 mt-0.5">e.g., active, published</p>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Where Condition (optional)</label>
                                                            <input type="text"
                                                                   wire:model="fields.{{ $index }}.settings.where"
                                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                                   placeholder="status = 'active'">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    {{-- Group Field Settings Row --}}
                                    @if($field['type'] === 'group')
                                        <tr wire:key="field-settings-{{ $index }}" class="bg-yellow-50">
                                            <td colspan="12" class="px-3 py-3">
                                                <div class="space-y-2">
                                                    <label class="block text-xs font-semibold text-gray-700">
                                                        Group Sub-fields Configuration
                                                    </label>
                                                    <input type="text"
                                                           wire:model="fields.{{ $index }}.settings.sub_fields"
                                                           class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                           placeholder="name:text:Name,description:textarea:Description,active:checkbox:Active">
                                                    <p class="text-xs text-gray-600">
                                                        <strong>Format:</strong> fieldname:type:label,fieldname:type:label
                                                        <br>
                                                        <strong>Example:</strong> name:text:Name,description:textarea:Description,active:checkbox:Active
                                                        <br>
                                                        <strong>Supported types:</strong> text, textarea, checkbox
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    {{-- Repeater Settings Row --}}
                                    @if($field['type'] === 'repeater')
                                        <tr wire:key="field-settings-{{ $index }}" class="bg-blue-50">
                                            <td colspan="12" class="px-3 py-3">
                                                <div class="space-y-3">
                                                    <label class="block text-xs font-semibold text-gray-700">
                                                        Repeater Sub-fields Configuration
                                                    </label>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">Sub-fields</label>
                                                        <input type="text"
                                                               wire:model="fields.{{ $index }}.settings.sub_fields"
                                                               class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border font-mono"
                                                               placeholder="title:text:Title,description:textarea:Description,icon:text:Icon">
                                                        <p class="text-xs text-gray-600 mt-1">
                                                            <strong>Format:</strong> fieldname:type:label,fieldname:type:label
                                                            <br>
                                                            <strong>Example:</strong> title:text:Title,description:textarea:Description,image:text:Image URL
                                                            <br>
                                                            <strong>Supported types:</strong> text, textarea, checkbox
                                                        </p>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Minimum Items</label>
                                                            <input type="number"
                                                                   wire:model="fields.{{ $index }}.settings.min"
                                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border"
                                                                   min="0"
                                                                   placeholder="0">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-600 mb-1">Maximum Items</label>
                                                            <input type="number"
                                                                   wire:model="fields.{{ $index }}.settings.max"
                                                                   class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2 px-3 border"
                                                                   min="1"
                                                                   placeholder="10">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm">No fields added yet</p>
                        <p class="mt-1 text-xs">Click "Add Field" to create your first template field</p>
                    </div>
                @endif
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.templates.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Template
                </button>
            </div>

        </div>
    </form>
</div>

@push('styles')
<style>
    .sortable-ghost {
        opacity: 0.5;
        background: #e0f2fe !important;
    }
    .sortable-drag {
        opacity: 0.8;
    }
    [wire\:sortable\.item] {
        transition: background-color 0.2s;
    }
    [wire\:sortable\.item]:hover {
        background-color: #f9fafb;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let sortableInstance = null;
let isUpdating = false;

function initializeSortable() {
    const sortableElement = document.querySelector('[wire\\:sortable]');

    // Only initialize once
    if (sortableElement && !sortableElement.hasAttribute('data-sortable-initialized-done')) {
        sortableElement.setAttribute('data-sortable-initialized-done', 'true');

        sortableInstance = new Sortable(sortableElement, {
            animation: 150,
            handle: '[wire\\:sortable\\.handle]',
            draggable: '[wire\\:sortable\\.item]',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                if (isUpdating) return;

                // Get all items in their NEW DOM order
                const items = Array.from(sortableElement.querySelectorAll('[wire\\:sortable\\.item]'));

                // Get their original indexes (from wire:sortable.item attribute)
                // This gives us the mapping: new position -> old index
                const newOrder = items.map(item => {
                    return parseInt(item.getAttribute('wire:sortable.item'));
                });

                console.log('Original indexes in new order:', newOrder);

                isUpdating = true;

                // Update Livewire
                @this.updateFieldOrder(newOrder).then(() => {
                    console.log('Order updated successfully');
                    isUpdating = false;
                }).catch((error) => {
                    console.error('Error updating order:', error);
                    isUpdating = false;
                });
            }
        });

        console.log('Sortable initialized');
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSortable);
} else {
    initializeSortable();
}

// For Livewire 3
document.addEventListener('livewire:navigated', initializeSortable);
</script>
@endpush

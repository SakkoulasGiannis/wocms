<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Section Templates</h1>
            <p class="mt-1 text-sm text-gray-600">Manage reusable section templates for your pages</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="exportAll" class="px-3 py-2 bg-green-50 hover:bg-green-100 text-green-700 font-medium rounded-lg border border-green-200 text-sm transition">
                <i class="fa fa-download mr-1"></i> Export All
            </button>
            <button wire:click="openImportModal" class="px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 font-medium rounded-lg border border-amber-200 text-sm transition">
                <i class="fa fa-upload mr-1"></i> Import
            </button>
            <button wire:click="openCreateModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition">
                <i class="fa fa-plus mr-2"></i>
                Quick Create
            </button>
            <a href="{{ route('admin.section-templates.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
                <i class="fa fa-plus mr-2"></i>
                Create Template
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- Templates Table -->
    @php
        $groupedTemplates = $templates->groupBy('category');
    @endphp

    @foreach($groupedTemplates as $category => $categoryTemplates)
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                <span class="bg-gray-100 px-3 py-1 rounded-full text-sm">
                    {{ $categories[$category] ?? ucfirst($category) }}
                </span>
                <span class="ml-2 text-gray-400 text-sm">({{ $categoryTemplates->count() }})</span>
            </h2>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Template
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fields
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($categoryTemplates as $template)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="flex items-center">
                                                <div class="font-medium text-gray-900">{{ $template->name }}</div>
                                                @if($template->is_system)
                                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">System</span>
                                                @endif
                                            </div>
                                            @if($template->description)
                                                <div class="text-sm text-gray-500 mt-1">{{ $template->description }}</div>
                                            @endif
                                            <div class="text-xs text-gray-400 mt-1">
                                                Slug: <code class="bg-gray-100 px-1 rounded">{{ $template->slug }}</code>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($template->fields as $field)
                                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-xs text-gray-700 rounded">
                                                {{ $field->label }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-gray-400 italic">No fields</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActive({{ $template->id }})"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $template->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                        @if($template->is_active)
                                            <i class="fa fa-check-circle mr-1"></i>Active
                                        @else
                                            <i class="fa fa-times-circle mr-1"></i>Inactive
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.section-templates.edit', $template->id) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fa fa-edit mr-1"></i>Edit
                                        </a>

                                        @if(!$template->is_system)
                                            <button wire:click="delete({{ $template->id }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $template->name }}'?"
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fa fa-trash mr-1"></i>Delete
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Protected</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if($templates->isEmpty())
        <div class="text-center py-12">
            <i class="fa fa-cube text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No section templates found</p>
            <p class="text-gray-400 text-sm mt-2">Run the seeder or create a custom template</p>
        </div>
    @endif

    <!-- Create Template Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeCreateModal">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Create Section Template with AI</h2>
                <p class="text-sm text-gray-600 mt-1">Generate sections from descriptions or convert HTML to Tailwind CSS 4.1</p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button wire:click="switchTab('ai')"
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'ai' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <i class="fa fa-magic mr-2"></i>AI Generator
                    </button>
                    <button wire:click="switchTab('manual')"
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $activeTab === 'manual' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <i class="fa fa-edit mr-2"></i>Manual
                    </button>
                </nav>
            </div>

            <!-- AI Tab -->
            @if($activeTab === 'ai')
            <div class="p-6 space-y-4">
                <!-- AI Success Message -->
                @if(session('ai_success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    <i class="fa fa-check-circle mr-2"></i>{{ session('ai_success') }}
                </div>
                @endif

                <!-- AI Error -->
                @if($aiError)
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <i class="fa fa-exclamation-circle mr-2"></i>{{ $aiError }}
                </div>
                @endif

                <!-- Input Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">What would you like to do?</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                                wire:click="$set('aiInputType', 'description')"
                                class="p-4 border-2 rounded-lg text-left {{ $aiInputType === 'description' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <div class="font-medium text-gray-900 mb-1">
                                <i class="fa fa-lightbulb mr-2 text-yellow-500"></i>Describe a Section
                            </div>
                            <div class="text-xs text-gray-500">Tell AI what you want and it will create it</div>
                        </button>
                        <button type="button"
                                wire:click="$set('aiInputType', 'html')"
                                class="p-4 border-2 rounded-lg text-left {{ $aiInputType === 'html' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <div class="font-medium text-gray-900 mb-1">
                                <i class="fa fa-code mr-2 text-blue-500"></i>Convert HTML
                            </div>
                            <div class="text-xs text-gray-500">Paste Bootstrap/HTML and convert to Tailwind</div>
                        </button>
                    </div>
                </div>

                <!-- AI Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        @if($aiInputType === 'description')
                            Describe your section
                        @else
                            Paste your HTML code
                        @endif
                    </label>

                    @if($aiInputType === 'description')
                        <textarea wire:model="aiInput"
                                  rows="6"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Example: Create a hero section with a large heading, subheading, CTA button, and a background gradient from blue to purple. Include an image on the right side for desktop."></textarea>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fa fa-info-circle mr-1"></i>
                            Be specific about layout, colors, components, and content placeholders
                        </p>
                    @else
                        <textarea wire:model="aiInput"
                                  rows="12"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                  placeholder='<div class="container">
  <div class="row">
    <div class="col-md-6">
      <h2>Heading</h2>
      <p>Description text</p>
      <a href="#" class="btn btn-primary">Click Me</a>
    </div>
  </div>
</div>'></textarea>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fa fa-info-circle mr-1"></i>
                            Paste any HTML (Bootstrap, Foundation, plain HTML, etc.)
                        </p>
                    @endif

                    @error('aiInput') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Generate Button -->
                <div class="flex justify-end">
                    <button wire:click="generateWithAI"
                            wire:loading.attr="disabled"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-lg shadow-sm transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="generateWithAI">
                            <i class="fa fa-magic mr-2"></i>Generate with AI
                        </span>
                        <span wire:loading wire:target="generateWithAI">
                            <i class="fa fa-spinner fa-spin mr-2"></i>Generating... (this may take 10-30 seconds)
                        </span>
                    </button>
                </div>
            </div>
            @endif

            <!-- Manual Tab -->
            @if($activeTab === 'manual')
            <div class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select wire:model="category" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- HTML Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HTML Template *</label>
                    <p class="text-xs text-gray-500 mb-2">
                        <i class="fa fa-info-circle mr-1"></i>
                        Use &#123;&#123;variable&#125;&#125; for placeholders. Example: &#123;&#123;heading&#125;&#125;, &#123;&#123;image&#125;&#125;, etc.
                    </p>
                    <textarea wire:model="html_template" rows="12" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"></textarea>
                    @error('html_template') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

                    @if($html_template && preg_match_all('/\{\{(\w+)\}\}/', $html_template, $matches))
                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs font-medium text-blue-900 mb-2">
                                <i class="fa fa-magic mr-1"></i>Detected {{ count(array_unique($matches[1])) }} placeholders:
                            </p>
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_unique($matches[1]) as $field)
                                    <span class="text-xs bg-white px-2 py-1 rounded border border-blue-200 text-blue-700">
                                        &#123;&#123;{{ $field }}&#125;&#125;
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-blue-600 mt-2">Fields will be auto-generated when you save</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-between">
                <button wire:click="closeCreateModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                    Cancel
                </button>
                <div class="flex gap-3">
                    @if($activeTab === 'manual' && $aiPreview)
                        <button wire:click="switchTab('ai')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                            <i class="fa fa-arrow-left mr-2"></i>Back to AI
                        </button>
                    @endif
                    <button wire:click="createTemplate" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        <i class="fa fa-save mr-2"></i>Create Template
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Import Modal --}}
    @if($showImportModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showImportModal', false)">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fa fa-upload mr-2 text-amber-500"></i>Import Section Templates</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">JSON Export File</label>
                    <input type="file" wire:model="importFile" accept=".json,.txt" class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                    @error('importFile') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="importFile" class="text-xs text-blue-600 mt-1"><i class="fa fa-spinner fa-spin"></i> Uploading...</div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="importOverwrite" class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Overwrite existing templates with same slug</span>
                </label>
                <p class="text-xs text-gray-500">Templates with the same slug will be skipped unless overwrite is enabled.</p>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                <button wire:click="$set('showImportModal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">Cancel</button>
                <button wire:click="importTemplates" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm" @if(!$importFile) disabled @endif>
                    <i class="fa fa-upload mr-1"></i> Import
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

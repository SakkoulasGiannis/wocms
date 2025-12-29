<div>
    <form wire:submit.prevent="save">
        {{-- Include external styles and scripts --}}
        @include('livewire.admin.template-entries.partials.entry-form-styles')
        @include('livewire.admin.template-entries.partials.entry-form-scripts')

        @if($template->has_seo)
            <div x-data="{ activeTab: 'content' }" class="space-y-6">
        @else
            <div class="space-y-6">
        @endif

            {{-- Success Notification (Toast) --}}
            <div id="success-notification"
                 style="display: none;"
                 class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span id="notification-message"></span>
            </div>

            <script>
                // Show notification function
                function showNotification(message) {
                    const notification = document.getElementById('success-notification');
                    const messageEl = document.getElementById('notification-message');
                    messageEl.textContent = message;
                    notification.style.display = 'flex';
                    setTimeout(() => {
                        notification.style.opacity = '0';
                        setTimeout(() => {
                            notification.style.display = 'none';
                            notification.style.opacity = '1';
                        }, 300);
                    }, 3000);
                }

                // Store pending file uploads
                window.pendingFileUploads = window.pendingFileUploads || {};

                // Upload file using Livewire's upload endpoint without triggering re-render
                async function uploadFileSilently(fieldName, file) {
                    console.log('Uploading file silently:', fieldName, file.name);

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const response = await fetch('{{ route("livewire.upload-file") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }

                        const data = await response.json();
                        console.log('Upload successful:', data);

                        // Set the uploaded file reference to Livewire component
                        @this.set('uploadedFiles.' + fieldName, data.path, false); // false = no re-render

                        return data;
                    } catch (error) {
                        console.error('Upload error:', error);
                        throw error;
                    }
                }

                // Generate SEO with AI
                async function generateSEO() {
                    console.log('=== generateSEO() called ===');

                    // Get button and icon elements
                    const btn = document.getElementById('seo-generate-btn');
                    const iconDefault = document.getElementById('seo-icon-default');
                    const iconLoading = document.getElementById('seo-icon-loading');
                    const btnText = document.getElementById('seo-btn-text');

                    // Set loading state
                    btn.disabled = true;
                    iconDefault.classList.add('hidden');
                    iconLoading.classList.remove('hidden');
                    btnText.textContent = '⏳ Generating...';

                    try {
                        await @this.call('generateSEOWithAI');
                        console.log('SEO generated successfully');

                        // Update input fields manually
                        const seoFields = @this.seoFields;
                        console.log('SEO Fields:', seoFields);

                        // Update all SEO input fields
                        if (seoFields.seo_title) document.getElementById('seo_title').value = seoFields.seo_title;
                        if (seoFields.seo_description) document.getElementById('seo_description').value = seoFields.seo_description;
                        if (seoFields.seo_keywords) document.getElementById('seo_keywords').value = seoFields.seo_keywords;
                        if (seoFields.seo_og_title) document.getElementById('seo_og_title').value = seoFields.seo_og_title;
                        if (seoFields.seo_og_description) document.getElementById('seo_og_description').value = seoFields.seo_og_description;
                        if (seoFields.seo_twitter_title) document.getElementById('seo_twitter_title').value = seoFields.seo_twitter_title;
                        if (seoFields.seo_twitter_description) document.getElementById('seo_twitter_description').value = seoFields.seo_twitter_description;

                        alert('✅ SEO metadata generated successfully! Review and save when ready.');
                    } catch (error) {
                        console.error('SEO generation failed:', error);
                        alert('❌ Σφάλμα: ' + error.message);
                    } finally {
                        // Reset to default state
                        btn.disabled = false;
                        iconDefault.classList.remove('hidden');
                        iconLoading.classList.add('hidden');
                        btnText.textContent = '🤖 Generate SEO with AI';
                    }
                }

                // Improve Content with AI
                async function improveContentWithAI() {
                    console.log('=== improveContentWithAI() called ===');

                    const promptInput = document.getElementById('ai-prompt-input');
                    const btn = document.getElementById('ai-improve-btn');
                    const iconDefault = document.getElementById('ai-improve-icon-default');
                    const iconLoading = document.getElementById('ai-improve-icon-loading');
                    const btnText = document.getElementById('ai-improve-btn-text');
                    const resultDiv = document.getElementById('ai-improve-result');
                    const messageEl = document.getElementById('ai-improve-message');

                    const prompt = promptInput.value.trim();

                    if (!prompt) {
                        alert('⚠️ Please enter a prompt for the AI');
                        return;
                    }

                    // Set loading state
                    btn.disabled = true;
                    iconDefault.classList.add('hidden');
                    iconLoading.classList.remove('hidden');
                    btnText.textContent = 'Improving...';
                    resultDiv.classList.add('hidden');

                    try {
                        // Sync GrapeJS first to get latest content
                        if (window.syncGrapeJS) {
                            window.syncGrapeJS();
                        }

                        await @this.call('improveContentWithAI', prompt);
                        console.log('Content improved successfully');

                        // Get updated field values from Livewire
                        const updatedFields = @this.fieldValues;
                        console.log('Updated fields:', updatedFields);

                        // Update all fields in the form
                        for (const [fieldName, fieldValue] of Object.entries(updatedFields)) {
                            console.log(`Updating field: ${fieldName}`, fieldValue);

                            const inputEl = document.getElementById(fieldName);

                            if (inputEl) {
                                console.log(`Found element for ${fieldName}, tag: ${inputEl.tagName}`);

                                // Handle different field types
                                if (inputEl.tagName === 'TEXTAREA') {
                                    inputEl.value = fieldValue || '';
                                    console.log(`Updated textarea ${fieldName}`);
                                } else if (inputEl.tagName === 'INPUT') {
                                    inputEl.value = fieldValue || '';
                                    console.log(`Updated input ${fieldName}`);
                                } else if (inputEl.classList.contains('wysiwyg-editor')) {
                                    // For WYSIWYG editors (TinyMCE/CKEditor)
                                    if (window.tinymce && window.tinymce.get(fieldName)) {
                                        window.tinymce.get(fieldName).setContent(fieldValue || '');
                                        console.log(`Updated TinyMCE ${fieldName}`);
                                    } else {
                                        inputEl.innerHTML = fieldValue || '';
                                        console.log(`Updated wysiwyg innerHTML ${fieldName}`);
                                    }
                                }

                                // Trigger change event to update Livewire
                                inputEl.dispatchEvent(new Event('change'));

                                // Also trigger input event for Livewire wire:model
                                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                            } else {
                                console.warn(`Could not find element with id: ${fieldName}`);
                            }

                            // Handle GrapeJS fields
                            if (window.grapeEditors && window.grapeEditors[fieldName]) {
                                try {
                                    console.log(`Updating GrapeJS field: ${fieldName}`);

                                    // If fieldValue is already an object, use it directly
                                    let grapesData = fieldValue;
                                    if (typeof fieldValue === 'string') {
                                        grapesData = JSON.parse(fieldValue);
                                    }

                                    if (grapesData.html) {
                                        window.grapeEditors[fieldName].setComponents(grapesData.html);
                                        console.log(`Set GrapeJS components for ${fieldName}`);
                                    }
                                    if (grapesData.css) {
                                        window.grapeEditors[fieldName].setStyle(grapesData.css);
                                        console.log(`Set GrapeJS styles for ${fieldName}`);
                                    }
                                    console.log(`GrapeJS ${fieldName} updated successfully`);
                                } catch (e) {
                                    console.error('Failed to update GrapeJS field:', fieldName, e);
                                }
                            }
                        }

                        // Show success message
                        messageEl.textContent = '✅ Content improved! Review the changes and save when ready.';
                        resultDiv.classList.remove('hidden');

                        // Clear the prompt
                        promptInput.value = '';

                    } catch (error) {
                        console.error('Content improvement failed:', error);
                        messageEl.textContent = '❌ Error: ' + error.message;
                        resultDiv.classList.remove('hidden');
                    } finally {
                        // Reset to default state
                        btn.disabled = false;
                        iconDefault.classList.remove('hidden');
                        iconLoading.classList.add('hidden');
                        btnText.textContent = 'Improve Content';
                    }
                }

                // Simple Save function with Livewire
                async function saveEntry(returnToList = false) {
                    console.log('=== saveEntry() called ===');

                    // Sync GrapeJS first
                    if (window.syncGrapeJS) {
                        console.log('Syncing GrapeJS...');
                        window.syncGrapeJS();
                    }

                    // Upload any pending files BEFORE saving
                    const pendingUploads = Object.keys(window.pendingFileUploads || {});
                    console.log('Pending uploads:', pendingUploads);

                    if (pendingUploads.length > 0) {
                        console.log('Uploading', pendingUploads.length, 'files...');

                        for (const fieldName of pendingUploads) {
                            const file = window.pendingFileUploads[fieldName];
                            if (file) {
                                console.log('Uploading file for field:', fieldName);

                                // Upload the file WITHOUT triggering re-render
                                await new Promise((resolve, reject) => {
                                    @this.upload('uploadedFiles.' + fieldName, file,
                                        (uploadedFilename) => {
                                            console.log('Upload complete:', fieldName, uploadedFilename);
                                            delete window.pendingFileUploads[fieldName];
                                            resolve();
                                        },
                                        (error) => {
                                            console.error('Upload failed:', fieldName, error);
                                            reject(error);
                                        },
                                        (event) => {
                                            // Progress callback - no logging to reduce console spam
                                        }
                                    );
                                });
                            }
                        }

                        console.log('All files uploaded!');

                        // Wait a bit for Livewire to process the uploads
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }

                    console.log('Getting uploadedFiles from Livewire...');
                    const uploadedFiles = @this.get('uploadedFiles');
                    console.log('Uploaded files:', uploadedFiles);
                    console.log('Uploaded files keys:', Object.keys(uploadedFiles || {}));

                    // Call Livewire save and handle response
                    @this.call('save').then(result => {
                        console.log('Save result:', result);

                        if (result && result.success) {
                            showNotification(result.message);

                            if (returnToList) {
                                setTimeout(() => {
                                    window.location.href = '{{ route("admin.template-entries.index", $template->slug) }}';
                                }, 500);
                            } else {
                                // Refresh the page to show the uploaded image
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                        }
                    }).catch(error => {
                        console.error('Save error:', error);
                        alert('Error saving: ' + error.message);
                    });
                }

                // Listen for notify event from Livewire (for other operations)
                document.addEventListener('livewire:init', () => {
                    Livewire.on('notify', (event) => {
                        const message = event.message || event[0]?.message || 'Success!';
                        showNotification(message);
                    });

                    // CRITICAL: Prevent Livewire from morphing GrapeJS containers
                    Livewire.hook('morph.updating', ({ el, component, toEl, skip }) => {
                        // Skip morphing for any element with wire:ignore that contains GrapeJS
                        if (el.hasAttribute && el.hasAttribute('wire:ignore')) {
                            const hasGrapeJS = el.querySelector && (
                                el.querySelector('[id^="gjs-"]') ||
                                el.id?.startsWith('gjs-')
                            );

                            if (hasGrapeJS) {
                                console.log('Skipping morph for GrapeJS element:', el.id || 'wrapper');
                                skip();
                                return;
                            }
                        }

                        // Skip morphing for GrapeJS containers and editors
                        if (el.id && (
                            el.id.startsWith('gjs-container-') ||
                            el.id.startsWith('gjs-') ||
                            el.id.startsWith('gjs-data-')
                        )) {
                            console.log('Skipping morph for GrapeJS ID:', el.id);
                            skip();
                            return;
                        }

                        // Skip morphing for elements inside GrapeJS containers
                        if (el.closest && el.closest('[id^="gjs-container-"]')) {
                            console.log('Skipping morph for element inside GrapeJS container');
                            skip();
                            return;
                        }
                    });

                    // Debug: Monitor file uploads
                    setTimeout(() => {
                        const fileInputs = document.querySelectorAll('input[type="file"]');
                        console.log('Found file inputs:', fileInputs.length);
                    }, 1000);
                });
            </script>

            <!-- Actions Bar -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.template-entries.index', $template->slug) }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to {{ $template->menu_label ?: $template->name }}
                </a>
                <div class="flex items-center gap-3">
                    @if($entryId && $this->frontendUrl)
                        <a href="{{ $this->frontendUrl }}"
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View on Frontend
                        </a>
                    @endif
                    @if($entryId)
                        <button type="button"
                                onclick="saveEntry(false)"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Update
                        </button>
                        <button type="button"
                                onclick="saveEntry(true)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                            </svg>
                            Update & Return
                        </button>
                    @else
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Create {{ Str::singular($template->name) }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Tabs Navigation (only if template has SEO) --}}
            @if($template->has_seo)
                <div class="border-b border-gray-200 bg-white rounded-t-lg shadow">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <button type="button" @click="activeTab = 'content'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'content', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'content'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Content
                        </button>
                        <button type="button" @click="activeTab = 'seo'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'seo', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'seo'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            SEO
                        </button>
                        <button type="button" @click="activeTab = 'settings'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'settings', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'settings'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </button>
                    </nav>
                </div>
            @endif

            {{-- Content Tab with Sidebar Layout --}}
            @if($template->has_seo)
                <div x-show="activeTab === 'content'" x-cloak>
            @endif

            {{-- Grid Layout: Main Content + Sidebar --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Content Area (2/3 width on large screens) --}}
                <div class="lg:col-span-2 space-y-6">

                        {{-- Sections Mode - Show Sections UI --}}
                        @if($template->render_mode === 'sections')
                            <!-- Page Sections Management -->
                            <div class="bg-white rounded-lg shadow p-6" wire:key="sections-management">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">Page Sections</h3>
                                    <button type="button"
                                            wire:click="addSection"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Add Section
                                    </button>
                                </div>

                                @if(session()->has('section-success'))
                                    <div
                                        class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                        {{ session('section-success') }}
                                    </div>
                                @endif

                                @if(session()->has('section-error'))
                                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                        {{ session('section-error') }}
                                    </div>
                                @endif

                                <!-- Section Form (Template-Based) -->
                                @if($showSectionForm)
                                    <div class="mb-6 border-2 border-blue-500 rounded-lg p-6 bg-blue-50">
                                        <h4 class="text-md font-semibold text-gray-900 mb-4">
                                            {{ $editingSectionIndex !== null ? 'Edit Section' : 'New Section' }}
                                        </h4>

                                        @if(!$selectedTemplateId && $editingSectionIndex === null)
                                            <!-- Step 1: Template Selection -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-3">Choose
                                                    Section Template</label>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                    @foreach($availableSectionTemplates as $template)
                                                        <button type="button"
                                                                wire:click="selectTemplate({{ $template->id }})"
                                                                class="p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-white transition text-left">
                                                            <div
                                                                class="font-semibold text-gray-900">{{ $template->name }}</div>
                                                            <div
                                                                class="text-xs text-gray-500 mt-1">{{ $template->description }}</div>
                                                            <div
                                                                class="text-xs text-blue-600 mt-2">{{ ucfirst($template->category) }}</div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <div class="mt-4">
                                                    <button type="button"
                                                            wire:click="cancelSectionEdit"
                                                            class="text-sm text-gray-600 hover:text-gray-900">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Step 2: Fill Template Fields -->
                                            @php
                                                $selectedTemplate = $availableSectionTemplates->firstWhere('id', $selectedTemplateId);
                                            @endphp

                                            @if($selectedTemplate)
                                                <div class="space-y-4">
                                                    <!-- Template Info -->
                                                    <div class="bg-white p-3 rounded border border-blue-200">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <span
                                                                    class="font-semibold text-gray-900">{{ $selectedTemplate->name }}</span>
                                                                <span
                                                                    class="ml-2 text-xs text-gray-500">{{ $selectedTemplate->description }}</span>
                                                            </div>
                                                            @if($editingSectionIndex === null)
                                                                <button type="button"
                                                                        wire:click="addSection"
                                                                        class="text-xs text-blue-600 hover:text-blue-800">
                                                                    Change Template
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Dynamic Fields -->
                                                    @foreach($selectedTemplate->fields as $field)
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                {{ $field->label }}
                                                                @if($field->is_required)
                                                                    <span class="text-red-500">*</span>
                                                                @endif
                                                            </label>
                                                            @if($field->description)
                                                                <p class="text-xs text-gray-500 mb-2">{{ $field->description }}</p>
                                                            @endif

                                                            @switch($field->type)
                                                                @case('text')
                                                                @case('url')
                                                                @case('email')
                                                                    <input type="{{ $field->type }}"
                                                                           wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                                                           placeholder="{{ $field->placeholder ?? $field->default_value }}">
                                                                    @break

                                                                @case('textarea')
                                                                    <textarea
                                                                        wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                        rows="3"
                                                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                                                        placeholder="{{ $field->placeholder ?? $field->default_value }}"></textarea>
                                                                    @break

                                                                @case('number')
                                                                    <input type="number"
                                                                           wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                                                    @break

                                                                @case('image')
                                                                    <input type="text"
                                                                           wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                                                           placeholder="Enter image URL">
                                                                    <p class="mt-1 text-xs text-gray-500">Enter full
                                                                        image URL or path</p>
                                                                    @break

                                                                @case('wysiwyg')
                                                                    <textarea
                                                                        wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                        rows="6"
                                                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 font-mono text-xs"
                                                                        placeholder="Enter HTML content"></textarea>
                                                                    @break

                                                                @case('repeater')
                                                                    <div
                                                                        class="text-sm text-gray-600 p-3 bg-yellow-50 rounded border border-yellow-200">
                                                                        ⚠️ Repeater field - Use JSON format for now.
                                                                        <textarea
                                                                            wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                            rows="4"
                                                                            class="w-full rounded-lg border-gray-300 shadow-sm mt-2 p-2 font-mono text-xs"
                                                                            placeholder='[{"key": "value"}]'></textarea>
                                                                    </div>
                                                                    @break

                                                                @default
                                                                    <input type="text"
                                                                           wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                                            @endswitch
                                                        </div>
                                                    @endforeach

                                                    <!-- Section Name Override -->
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Section
                                                            Name (optional)</label>
                                                        <input type="text"
                                                               wire:model="sectionForm.name"
                                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2"
                                                               placeholder="Leave empty to use template name">
                                                    </div>

                                                    <!-- Is Active -->
                                                    <div>
                                                        <label class="flex items-center">
                                                            <input type="checkbox"
                                                                   wire:model="sectionForm.is_active"
                                                                   class="rounded border-gray-300 text-blue-600 shadow-sm">
                                                            <span class="ml-2 text-sm text-gray-700">Section is active (visible on frontend)</span>
                                                        </label>
                                                    </div>

                                                    <!-- Actions -->
                                                    <div class="flex items-center space-x-3 pt-4 border-t">
                                                        <button type="button"
                                                                wire:click="saveSection"
                                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                                 viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Save Section
                                                        </button>
                                                        <button type="button"
                                                                wire:click="cancelSectionEdit"
                                                                class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endif

                                <!-- Sections List -->
                                @if(count($sections) > 0)
                                    <div class="space-y-3">
                                        @foreach($sections as $index => $section)
                                            <div
                                                class="border rounded-lg p-4 {{ $section['is_active'] ? 'bg-white' : 'bg-gray-100' }}">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-3">
                                                            <span
                                                                class="text-xs font-mono bg-gray-200 px-2 py-1 rounded">#{{ $index + 1 }}</span>
                                                            <h4 class="font-semibold text-gray-900">
                                                                {{ $section['name'] ?: 'Section' }}
                                                            </h4>
                                                            @php
                                                                $sectionTemplate = $availableSectionTemplates->firstWhere('id', $section['section_template_id'] ?? null);
                                                            @endphp
                                                            @if($sectionTemplate)
                                                                <span
                                                                    class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                        {{ $sectionTemplate->name }}
                                                    </span>
                                                            @else
                                                                <span
                                                                    class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                                        {{ $section['section_type'] }}
                                                    </span>
                                                            @endif
                                                            @if(!$section['is_active'])
                                                                <span
                                                                    class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inactive</span>
                                                            @endif
                                                        </div>
                                                        @if($sectionTemplate)
                                                            <p class="text-xs text-gray-500 mt-1">
                                                                {{ $sectionTemplate->description }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <!-- Actions -->
                                                    <div class="flex items-center space-x-2 ml-4">
                                                        <!-- Move Up -->
                                                        @if($index > 0)
                                                            <button type="button"
                                                                    wire:click="moveSectionUp({{ $index }})"
                                                                    class="p-1 text-gray-500 hover:text-blue-600"
                                                                    title="Move Up">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                     viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2" d="M5 15l7-7 7 7"/>
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        <!-- Move Down -->
                                                        @if($index < count($sections) - 1)
                                                            <button type="button"
                                                                    wire:click="moveSectionDown({{ $index }})"
                                                                    class="p-1 text-gray-500 hover:text-blue-600"
                                                                    title="Move Down">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                     viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                                </svg>
                                                            </button>
                                                        @endif

                                                        <!-- Toggle Active -->
                                                        <button type="button"
                                                                wire:click="toggleSection({{ $index }})"
                                                                class="p-1 text-gray-500 hover:text-yellow-600"
                                                                title="{{ $section['is_active'] ? 'Deactivate' : 'Activate' }}">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                 viewBox="0 0 24 24">
                                                                @if($section['is_active'])
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2"
                                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2"
                                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                @else
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          stroke-width="2"
                                                                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                                @endif
                                                            </svg>
                                                        </button>

                                                        <!-- Edit -->
                                                        <button type="button"
                                                                wire:click="editSection({{ $index }})"
                                                                class="p-1 text-gray-500 hover:text-blue-600"
                                                                title="Edit">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                 viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      stroke-width="2"
                                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                        </button>

                                                        <!-- Delete -->
                                                        <button type="button"
                                                                wire:click="deleteSection({{ $index }})"
                                                                onclick="return confirm('Are you sure you want to delete this section?')"
                                                                class="p-1 text-gray-500 hover:text-red-600"
                                                                title="Delete">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                 viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      stroke-width="2"
                                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p>No sections yet. Click "Add Section" to create your first section.</p>
                                    </div>
                                @endif
                            </div>

                        @else
                            {{-- All other render modes - Show fields with dynamic types --}}
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="space-y-6">
                                    @foreach($template->fields->where('column_position', 'main') as $field)
                                        @include('livewire.admin.template-entries.partials.dynamic-field', [
                                            'field' => $field,
                                            'entryId' => $entryId,
                                            'entry' => $entry
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endif

                </div>
                {{-- End Main Content Area --}}

                {{-- Right Sidebar (1/3 width on large screens) --}}
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-6">
                        @include('livewire.admin.template-entries.partials.entry-sidebar')
                    </div>
                </div>
                {{-- End Sidebar --}}

            </div>
            {{-- End Grid Layout --}}

            {{-- Close Content Tab --}}
            @if($template->has_seo)
                </div>
            @endif

            {{-- SEO Tab Content --}}
            @if($template->has_seo)
                <div x-show="activeTab === 'seo'" x-cloak>
                    @include('livewire.admin.template-entries.partials.seo-fields')
                </div>
            @endif

            {{-- Settings Tab Content --}}
            @if($template->has_seo)
                <div x-show="activeTab === 'settings'" x-cloak>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Page Settings</h3>

                        <div class="space-y-6">
                            {{-- Cache Settings --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Full Page Caching</h4>

                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Cache Override
                                        </label>
                                        <select wire:model="cacheEnabled"
                                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                            <option value="">Use Template Setting ({{ $template->enable_full_page_cache ? 'Enabled' : 'Disabled' }})</option>
                                            <option value="1">Force Enable</option>
                                            <option value="0">Force Disable</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Override the template's caching setting for this specific page.
                                        </p>
                                    </div>

                                    @if($template->enable_full_page_cache)
                                        <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                            <p class="text-xs text-blue-800">
                                                <strong>Template Setting:</strong> Full page caching is enabled with a cache duration of {{ $template->cache_ttl ?? 3600 }} seconds ({{ round(($template->cache_ttl ?? 3600) / 60) }} minutes).
                                            </p>
                                        </div>
                                    @else
                                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-xs text-gray-600">
                                                <strong>Template Setting:</strong> Full page caching is currently disabled for this template.
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.template-entries.index', $template->slug) }}"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <div class="flex items-center gap-3">
                    @if($entryId && $this->frontendUrl)
                        <a href="{{ $this->frontendUrl }}"
                           target="_blank"
                           class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View on Frontend
                        </a>
                    @endif
                    @if($entryId)
                        <button type="button"
                                onclick="saveEntry(false)"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Update
                        </button>
                        <button type="button"
                                onclick="saveEntry(true)"
                                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                            </svg>
                            Update & Return
                        </button>
                    @else
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Create {{ Str::singular($template->name) }}
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </form>

    {{-- Code editor modal --}}
    @include('livewire.admin.template-entries.partials.code-editor-modal')
</div>

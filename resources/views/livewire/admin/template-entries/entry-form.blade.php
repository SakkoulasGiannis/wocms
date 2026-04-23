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
                           rel="noopener"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View Page
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
                        <button type="button"
                                onclick="saveEntry(false)"
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

                        {{-- Template Fields --}}
                        @php
                            $mainFields = $template->fields->where('column_position', 'main');
                            // In sections mode, only show basic fields (text, email, url, image, select, checkbox, date, number)
                            // Hide heavy content fields (grapejs, wysiwyg, markdown) since content comes from sections
                            if ($template->render_mode === 'sections' || ($entry?->render_mode ?? null) === 'sections') {
                                $hiddenInSections = ['grapejs', 'wysiwyg', 'markdown', 'code'];
                                $mainFields = $mainFields->whereNotIn('type', $hiddenInSections);
                            }
                        @endphp
                        @if($mainFields->count() > 0)
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="space-y-6">
                                    @foreach($mainFields as $field)
                                        @include('livewire.admin.template-entries.partials.dynamic-field', [
                                            'field' => $field,
                                            'entryId' => $entryId,
                                            'entry' => $entry
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Sections Mode - Show Sections UI --}}
                        @if($template->render_mode === 'sections' || ($entry?->render_mode ?? null) === 'sections')
                            <!-- Warning: Generated Blade File Exists -->
                            @if($entryId && $this->hasGeneratedBladeFile())
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm text-yellow-700">
                                                <strong>Warning:</strong> A generated blade file exists for this page:
                                                <code class="bg-yellow-100 px-2 py-1 rounded text-xs">{{ $this->getGeneratedBladeFilePath() }}</code>
                                            </p>
                                            <p class="mt-2 text-xs text-yellow-600">
                                                This file will override the sections below. You should delete it to use sections.
                                            </p>
                                            <button type="button"
                                                    wire:click="deleteGeneratedBladeFile"
                                                    wire:confirm="Are you sure you want to delete the generated blade file?"
                                                    class="mt-3 inline-flex items-center px-3 py-1.5 border border-yellow-600 text-xs font-medium rounded text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete Generated File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Page Sections Management -->
                            <div class="bg-white rounded-lg shadow p-6" wire:key="sections-management">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900">Page Sections</h3>
                                    <div class="flex gap-2">
                                        @if($entryId)
                                            @php
                                                $mc = $template->model_class ?? 'Page';
                                                $fqcn = str_contains($mc, '\\') ? $mc : "App\\Models\\{$mc}";
                                                $sectionableTypeUrl = str_replace('\\', '-', $fqcn);
                                            @endphp
                                            <a href="{{ route('admin.page-sections.visual', ['sectionableType' => $sectionableTypeUrl, 'sectionableId' => $entryId]) }}"
                                               target="_blank"
                                               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Visual Editor
                                            </a>
                                        @endif
                                        <button type="button"
                                                onclick="@this.call('addSection')"
                                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Add Section
                                        </button>
                                    </div>
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
                                                            onclick="@this.call('cancelSectionEdit')"
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
                                                                        onclick="@this.call('addSection')"
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
                                                                    <div class="space-y-2">
                                                                        @if(!empty($sectionForm['field_data'][$field->name]))
                                                                            <div class="relative inline-block">
                                                                                <img src="{{ $sectionForm['field_data'][$field->name] }}" alt="Preview" class="h-24 rounded-lg border border-gray-200 object-cover">
                                                                                <button type="button" wire:click="$set('sectionForm.field_data.{{ $field->name }}', '')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">&times;</button>
                                                                            </div>
                                                                        @endif
                                                                        <div class="flex items-center gap-2">
                                                                            <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg border border-blue-200 text-sm transition">
                                                                                <i class="fa fa-upload"></i> Upload Image
                                                                                <input type="file" wire:model="sectionImageUploads.{{ $field->name }}" accept="image/*" class="hidden">
                                                                            </label>
                                                                            <span class="text-gray-400 text-xs">or</span>
                                                                            <input type="text"
                                                                                   wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                                   class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 text-sm"
                                                                                   placeholder="Enter image URL">
                                                                        </div>
                                                                        <div wire:loading wire:target="sectionImageUploads.{{ $field->name }}" class="text-xs text-blue-600">
                                                                            <i class="fa fa-spinner fa-spin"></i> Uploading...
                                                                        </div>
                                                                    </div>
                                                                    @break

                                                                @case('wysiwyg')
                                                                    <x-editorjs-field
                                                                        :name="'section.' . $field->name"
                                                                        :value="$sectionForm['field_data'][$field->name] ?? ''"
                                                                        wire-model="sectionForm.field_data.{{ $field->name }}"
                                                                        :uid="'ejs-section-' . $field->name . ($editingSectionIndex ?? 'new')"
                                                                    />
                                                                    @break

                                                                @case('repeater')
                                                                    @php
                                                                        $rfSettings = $field->settings;
                                                                        if (is_string($rfSettings)) $rfSettings = json_decode($rfSettings, true);
                                                                        $rfSubFields = $rfSettings['sub_fields'] ?? [];
                                                                        $rfItems = $sectionForm['field_data'][$field->name] ?? [];
                                                                        if (!is_array($rfItems)) $rfItems = [];
                                                                    @endphp

                                                                    <div class="space-y-3">
                                                                        @foreach($rfItems as $rfIdx => $rfItem)
                                                                            <div class="border border-gray-300 rounded-lg p-3 bg-white relative">
                                                                                <button type="button"
                                                                                        wire:click="removeRepeaterItem('{{ $field->name }}', {{ $rfIdx }})"
                                                                                        class="absolute top-2 right-2 text-red-400 hover:text-red-600 text-xs font-bold"
                                                                                        title="Remove">✕</button>
                                                                                <div class="text-xs text-gray-400 mb-2 font-semibold">#{{ $rfIdx + 1 }}</div>
                                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                                    @foreach($rfSubFields as $sf)
                                                                                        <div>
                                                                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $sf['label'] ?? $sf['name'] }}</label>
                                                                                            @if(($sf['type'] ?? 'text') === 'textarea')
                                                                                                <textarea wire:model="sectionForm.field_data.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                                                          class="w-full border border-gray-300 rounded px-2 py-1 text-sm" rows="2"></textarea>
                                                                                            @elseif(($sf['type'] ?? 'text') === 'image')
                                                                                                <div class="space-y-1">
                                                                                                    @if(!empty($sectionForm['field_data'][$field->name][$rfIdx][$sf['name']]))
                                                                                                        <div class="relative inline-block">
                                                                                                            <img src="{{ $sectionForm['field_data'][$field->name][$rfIdx][$sf['name']] }}" alt="" class="h-16 rounded border object-cover">
                                                                                                            <button type="button" wire:click="$set('sectionForm.field_data.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}', '')" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-xs">&times;</button>
                                                                                                        </div>
                                                                                                    @endif
                                                                                                    <div class="flex items-center gap-1">
                                                                                                        <label class="cursor-pointer inline-flex items-center gap-1 px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 text-xs transition">
                                                                                                            <i class="fa fa-upload"></i> Upload
                                                                                                            <input type="file" wire:model="sectionImageUploads.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}" accept="image/*" class="hidden">
                                                                                                        </label>
                                                                                                        <input type="text"
                                                                                                               wire:model="sectionForm.field_data.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                                                               class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs"
                                                                                                               placeholder="Image URL">
                                                                                                    </div>
                                                                                                    <div wire:loading wire:target="sectionImageUploads.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}" class="text-xs text-blue-600"><i class="fa fa-spinner fa-spin"></i></div>
                                                                                                </div>
                                                                                            @else
                                                                                                <input type="{{ $sf['type'] ?? 'text' }}"
                                                                                                       wire:model="sectionForm.field_data.{{ $field->name }}.{{ $rfIdx }}.{{ $sf['name'] }}"
                                                                                                       class="w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                                                                                       placeholder="{{ $sf['label'] ?? $sf['name'] }}">
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endforeach

                                                                        <button type="button"
                                                                                wire:click="addRepeaterItem('{{ $field->name }}')"
                                                                                class="text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1">
                                                                            + Add Item
                                                                        </button>
                                                                    </div>

                                                                    @if(empty($rfSubFields))
                                                                        <p class="text-xs text-amber-600 mt-2">No sub-fields defined. Edit this field in the Section Template to add sub-fields.</p>
                                                                    @endif
                                                                    @break

                                                                @default
                                                                    <input type="text"
                                                                           wire:model="sectionForm.field_data.{{ $field->name }}"
                                                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                                            @endswitch
                                                        </div>
                                                    @endforeach

                                                    {{-- Dynamic Slider Picker for hero-slider-home5 --}}
                                                    @if($selectedTemplate && in_array($selectedTemplate->slug, ['hero-slider-home5', 'hero-slider']))
                                                        @php
                                                            $availableSliders = \Modules\Slider\Models\Slider::where('is_active', true)->withCount('slides')->orderBy('name')->get();
                                                        @endphp
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                <i class="fa fa-images mr-1 text-blue-500"></i> Select Slider
                                                            </label>
                                                            <p class="text-xs text-gray-500 mb-2">Choose a slider from the Sliders module. Its slides will be used as the background images/videos.</p>
                                                            <select wire:model="sectionForm.field_data.slider_id"
                                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                                                                <option value="">-- Use default images --</option>
                                                                @foreach($availableSliders as $sl)
                                                                    <option value="{{ $sl->id }}">{{ $sl->name }} ({{ $sl->slides_count }} slides)</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endif

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
                                                                onclick="@this.call('saveSection')"
                                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                                 viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Save Section
                                                        </button>
                                                        <button type="button"
                                                                onclick="@this.call('cancelSectionEdit')"
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
                                    <div id="sections-list" class="space-y-3">
                                        @foreach($sections as $index => $section)
                                            <div data-section-index="{{ $index }}"
                                                class="border rounded-lg p-4 {{ $section['is_active'] ? 'bg-white' : 'bg-gray-100' }}">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-3">
                                                            <!-- Drag Handle -->
                                                            <div class="section-drag-handle cursor-move text-gray-400 hover:text-gray-600"
                                                                 title="Drag to reorder">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                                </svg>
                                                            </div>
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
                                                                    onclick="@this.call('moveSectionUp', {{ $index }})"
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
                                                                    onclick="@this.call('moveSectionDown', {{ $index }})"
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
                                                                onclick="@this.call('toggleSection', {{ $index }})"
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
                                                                onclick="@this.call('editSection', {{ $index }})"
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
                                                                onclick="if(confirm('Are you sure you want to delete this section?')) { @this.call('deleteSection', {{ $index }}) }"
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

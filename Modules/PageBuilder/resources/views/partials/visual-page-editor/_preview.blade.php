    {{-- ===== PREVIEW IFRAME ===== --}}
    <div class="flex-1 flex flex-col bg-gray-200 overflow-hidden"
         x-data="{
             showJsonModal: false,
             showImportModal: false,
             jsonContent: '',
             importContent: '',
             copied: false,
             viewport: 'desktop',
             viewports: {
                 mobile:  { width: '390px',  label: 'Mobile',  icon: 'phone' },
                 tablet:  { width: '768px',  label: 'Tablet',  icon: 'tablet' },
                 desktop: { width: '100%',   label: 'Desktop', icon: 'desktop' },
             },
             openJson() {
                 $wire.getPageJson().then(data => {
                     this.jsonContent = JSON.stringify(data, null, 2);
                     this.showJsonModal = true;
                 });
             },
             copyJson() {
                 navigator.clipboard.writeText(this.jsonContent);
                 this.copied = true;
                 setTimeout(() => this.copied = false, 2000);
             },
             submitImport() {
                 $wire.importJson(this.importContent);
                 this.showImportModal = false;
                 this.importContent = '';
             }
         }"
         x-on:notify.window="console.log($event.detail)"
         x-on:preview-patch.window="patchPreview($event.detail)"
         x-on:preview-visibility.window="patchVisibility($event.detail)"
         x-init="
             $data.patchPreview = function(detail) {
                 if (!detail || !detail.sectionId) return;
                 const frame = document.getElementById('preview-frame');
                 if (!frame?.contentWindow) { reloadPreview(); return; }
                 frame.contentWindow.postMessage({ type: 've-patch', sectionId: detail.sectionId, html: detail.html }, '*');
             };
             $data.patchVisibility = function(detail) {
                 if (!detail || !detail.sectionId) return;
                 const frame = document.getElementById('preview-frame');
                 if (!frame?.contentDocument) return;
                 const wrapper = frame.contentDocument.querySelector('[data-ve-section-id=\'' + detail.sectionId + '\']');
                 if (wrapper) wrapper.classList.toggle('ve-hidden', !detail.visible);
             };
         ">

        {{-- Preview toolbar --}}
        <div class="flex items-center gap-3 px-4 py-2 bg-gray-800 flex-shrink-0">
            <div class="flex items-center gap-1 bg-gray-700 rounded-lg px-3 py-1.5 flex-1 max-w-md">
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                </svg>
                <span class="text-gray-300 text-xs truncate">{{ config('app.url') }}{{ $previewUrl }}</span>
            </div>

            {{-- Build assets --}}
            <button wire:click="buildAssets"
                    wire:loading.attr="disabled"
                    title="Run npm run build"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition bg-gray-700 text-gray-400 hover:text-white hover:bg-gray-600 disabled:opacity-50">
                <svg wire:loading.remove wire:target="buildAssets" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <svg wire:loading wire:target="buildAssets" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span wire:loading.remove wire:target="buildAssets">Build</span>
                <span wire:loading wire:target="buildAssets">Building…</span>
            </button>

            {{-- Tailwind CDN toggle --}}
            <button wire:click="toggleTailwindCdn"
                    title="{{ $veTailwindCdn ? 'Tailwind CDN active — click to disable' : 'Enable Tailwind browser CDN in preview' }}"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition
                           {{ $veTailwindCdn ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-400 hover:text-white hover:bg-gray-600' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                Tailwind CDN
            </button>

            {{-- Undo / Redo --}}
            <div class="flex items-center bg-gray-700 rounded-lg p-0.5 gap-0.5">
                <button wire:click="undo"
                        class="flex items-center px-2.5 py-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-500 transition disabled:opacity-40"
                        title="Undo (Ctrl+Z)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button wire:click="redo"
                        class="flex items-center px-2.5 py-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-500 transition disabled:opacity-40"
                        title="Redo (Ctrl+Y)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>
            </div>

            {{-- Viewport switcher --}}
            <div class="flex items-center bg-gray-700 rounded-lg p-0.5 gap-0.5">
                {{-- Mobile --}}
                <button x-on:click="viewport = 'mobile'"
                        :class="viewport === 'mobile' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Mobile (390px)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </button>
                {{-- Tablet --}}
                <button x-on:click="viewport = 'tablet'"
                        :class="viewport === 'tablet' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Tablet (768px)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </button>
                {{-- Desktop --}}
                <button x-on:click="viewport = 'desktop'"
                        :class="viewport === 'desktop' ? 'bg-gray-500 text-white' : 'text-gray-400 hover:text-white'"
                        class="flex items-center gap-1 px-2.5 py-1.5 rounded text-xs transition"
                        title="Desktop (full width)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>

            {{-- JSON / Export / Import buttons --}}
            <div class="flex items-center gap-2 border-l border-gray-600 pl-3">
                {{-- Preview JSON --}}
                <button x-on:click="openJson()"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Preview JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    JSON
                </button>

                {{-- Export --}}
                <button wire:click="exportJson"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Export JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </button>

                {{-- Import --}}
                <button x-on:click="showImportModal = true; importContent = ''"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition"
                        title="Import JSON">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                    Import
                </button>
            </div>

            <button x-on:click="reloadPreview()" class="text-gray-400 hover:text-white transition" title="Reload preview">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <a href="{{ $previewUrl }}" target="_blank" class="text-gray-400 hover:text-white transition" title="Open in new tab">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>

        {{-- JSON Preview Modal --}}
        <div x-show="showJsonModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-6"
             style="background:rgba(0,0,0,0.7);">
            <div class="bg-gray-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-700">
                    <h3 class="text-white font-semibold text-sm">Page JSON</h3>
                    <div class="flex items-center gap-2">
                        <button x-on:click="copyJson()"
                                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white rounded text-xs transition">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied ✓</span>
                        </button>
                        <button x-on:click="showJsonModal = false" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-auto p-4">
                    <pre class="text-green-400 text-xs font-mono whitespace-pre-wrap" x-text="jsonContent"></pre>
                </div>
            </div>
        </div>

        {{-- Import Modal --}}
        <div x-show="showImportModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-6"
             style="background:rgba(0,0,0,0.7);">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl flex flex-col">
                <div class="flex items-center justify-between px-5 py-3 border-b">
                    <h3 class="font-semibold text-gray-800 text-sm">Import Page JSON</h3>
                    <button x-on:click="showImportModal = false" class="text-gray-400 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-3">Paste a valid page JSON below. This will replace all current sections.</p>
                    <textarea x-model="importContent"
                              rows="12"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder='{ "version": "1.0", "sections": [...] }'></textarea>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-2">
                    <button x-on:click="showImportModal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                    <button x-on:click="submitImport()"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition">
                        Import
                    </button>
                </div>
            </div>
        </div>

        {{-- Media Library Modal --}}
        @if($showMediaLibrary)
            @php $mediaFiles = $this->getMediaFiles(); @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-6"
                 style="background:rgba(0,0,0,0.7);">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl flex flex-col" style="max-height:80vh;">
                    <div class="flex items-center justify-between px-5 py-3 border-b flex-shrink-0">
                        <h3 class="font-semibold text-gray-800 text-sm">Media Library</h3>
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-medium transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Upload
                                <input type="file" wire:model="sectionImageUploads.{{ $mediaTargetField }}" accept="image/*" class="hidden">
                            </label>
                            <button wire:click="closeMediaLibrary" class="text-gray-400 hover:text-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        @if(empty($mediaFiles))
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm">No images found. Upload one above.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-4 gap-3">
                                @foreach($mediaFiles as $file)
                                    <button type="button"
                                            wire:click="selectMedia('{{ $file['url'] }}')"
                                            class="group relative aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-purple-500 transition">
                                        <img src="{{ $file['url'] }}"
                                             alt="{{ $file['name'] }}"
                                             class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition flex items-end">
                                            <p class="w-full px-1.5 py-1 bg-black/60 text-white text-[10px] truncate opacity-0 group-hover:opacity-100 transition">{{ $file['name'] }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Iframe --}}
        <div class="flex-1 min-w-0 relative overflow-auto bg-gray-300">
            <div x-show="iframeLoading"
                 class="absolute inset-0 bg-gray-100 flex items-center justify-center z-10 pointer-events-none">
                <div class="text-center text-gray-400">
                    <svg class="w-8 h-8 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="text-sm">Loading preview…</span>
                </div>
            </div>
            {{-- Wrapper centers the iframe and applies a drop shadow when not full-width --}}
            <div class="h-full flex flex-col items-center transition-all duration-300"
                 :class="viewport !== 'desktop' ? 'py-4' : ''"
                 :style="viewport !== 'desktop' ? 'min-height:100%' : 'height:100%'">
                <div class="transition-all duration-300 shadow-2xl bg-white h-full"
                     :style="'width:' + viewports[viewport].width + '; height:100%; ' + (viewport !== 'desktop' ? 'border-radius:12px; overflow:hidden;' : '')">
                    <iframe id="preview-frame"
                            src="{{ $previewUrl }}"
                            class="border-0 w-full h-full"
                            x-on:load="iframeLoading = false"
                            style="display:block;"></iframe>
                </div>
                <div x-show="viewport !== 'desktop'"
                     class="mt-2 text-xs text-gray-500 font-medium"
                     x-text="viewports[viewport].label + ' — ' + viewports[viewport].width"></div>
            </div>
        </div>
    </div>

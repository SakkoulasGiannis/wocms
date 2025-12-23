<div class="h-screen flex flex-col bg-white">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $totalMedia }} {{ Str::plural('item', $totalMedia) }}
                    <span class="mx-2">•</span>
                    {{ number_format($totalSize / 1024 / 1024, 2) }} MB total
                </p>
            </div>
            @if(!$pickerMode)
                <div class="flex items-center space-x-2">
                    <button onclick="document.getElementById('file-upload').click()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Upload Files</span>
                    </button>
                    <input type="file"
                           id="file-upload"
                           wire:model="uploads"
                           multiple
                           class="hidden">
                </div>
            @endif
        </div>
    </div>

    <!-- Toolbar -->
    <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Bulk Actions -->
                @if(count($selectedMedia) > 0)
                    <div class="flex items-center space-x-2">
                        <select wire:model="bulkAction" class="text-sm border-gray-300 rounded-lg">
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button wire:click="executeBulkAction"
                                class="text-sm bg-white border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50">
                            Apply
                        </button>
                        <span class="text-sm text-gray-600">{{ count($selectedMedia) }} selected</span>
                        <button wire:click="deselectAll" class="text-sm text-blue-600 hover:text-blue-800">
                            Clear
                        </button>
                    </div>
                @else
                    <div class="flex items-center space-x-2">
                        <button wire:click="selectAll" class="text-sm text-blue-600 hover:text-blue-800">
                            Select All
                        </button>
                    </div>
                @endif

                <!-- Filter by Type -->
                <div class="flex items-center space-x-2 border-l border-gray-300 pl-4">
                    <button wire:click="$set('filterType', 'all')"
                            class="text-sm px-3 py-1.5 rounded-lg {{ $filterType === 'all' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        All
                    </button>
                    <button wire:click="$set('filterType', 'image')"
                            class="text-sm px-3 py-1.5 rounded-lg {{ $filterType === 'image' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        Images
                    </button>
                    <button wire:click="$set('filterType', 'video')"
                            class="text-sm px-3 py-1.5 rounded-lg {{ $filterType === 'video' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        Videos
                    </button>
                    <button wire:click="$set('filterType', 'document')"
                            class="text-sm px-3 py-1.5 rounded-lg {{ $filterType === 'document' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                        Documents
                    </button>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Search -->
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search media..."
                           class="pl-9 pr-4 py-1.5 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- View Mode Toggle -->
                <div class="flex items-center bg-white border border-gray-300 rounded-lg">
                    <button wire:click="$set('viewMode', 'grid')"
                            class="p-2 {{ $viewMode === 'grid' ? 'bg-gray-100 text-blue-600' : 'text-gray-600' }} rounded-l-lg">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                    <button wire:click="$set('viewMode', 'list')"
                            class="p-2 {{ $viewMode === 'list' ? 'bg-gray-100 text-blue-600' : 'text-gray-600' }} rounded-r-lg border-l border-gray-300">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 overflow-hidden flex">
        <!-- Media Grid/List -->
        <div class="flex-1 overflow-y-auto p-6"
             x-data="{
                 dragOver: false,
                 handleDrop(e) {
                     this.dragOver = false;
                     @this.uploads = Array.from(e.dataTransfer.files);
                 }
             }"
             x-on:dragover.prevent="dragOver = true"
             x-on:dragleave.prevent="dragOver = false"
             x-on:drop.prevent="handleDrop($event)"
             :class="{ 'border-4 border-dashed border-blue-400 bg-blue-50': dragOver }">

            @if(session()->has('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($media->isEmpty())
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No media files</h3>
                    <p class="text-gray-500 mb-4">Upload your first file to get started</p>
                    <button onclick="document.getElementById('file-upload').click()"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        Upload Files
                    </button>
                </div>
            @else
                <!-- Grid View -->
                @if($viewMode === 'grid')
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($media as $item)
                            <div wire:key="media-{{ $item->id }}"
                                 wire:click="toggleSelection({{ $item->id }})"
                                 class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer border-2 transition
                                        {{ in_array($item->id, $selectedMedia) ? 'border-blue-500 ring-2 ring-blue-500' : 'border-transparent hover:border-gray-300' }}">

                                <!-- Checkbox -->
                                <div class="absolute top-2 left-2 z-10">
                                    <div class="w-5 h-5 rounded border-2 bg-white flex items-center justify-center
                                                {{ in_array($item->id, $selectedMedia) ? 'border-blue-500 bg-blue-500' : 'border-gray-400 group-hover:border-blue-500' }}">
                                        @if(in_array($item->id, $selectedMedia))
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                </div>

                                <!-- Info Icon (clickable) -->
                                <button onclick="event.stopPropagation(); @this.call('showDetailsPanel', {{ $item->id }})"
                                        type="button"
                                        class="absolute top-2 right-2 z-10 w-7 h-7 bg-white rounded-full shadow opacity-0 group-hover:opacity-100 transition flex items-center justify-center hover:bg-gray-100">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>

                                <!-- Preview -->
                                @if(str_starts_with($item->mime_type, 'image/'))
                                    <img src="{{ $item->getUrl() }}"
                                         alt="{{ $item->name }}"
                                         class="w-full h-full object-cover">
                                @elseif(str_starts_with($item->mime_type, 'video/'))
                                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                        <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-200 p-2">
                                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-gray-500 font-medium text-center">
                                            {{ strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION)) }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Filename overlay -->
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition">
                                    <p class="text-xs text-white truncate">{{ $item->name }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- List View -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-12 px-4 py-3"></th>
                                    <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">File</th>
                                    <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Type</th>
                                    <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Size</th>
                                    <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Date</th>
                                    <th class="w-12 px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($media as $item)
                                    <tr wire:key="media-list-{{ $item->id }}"
                                        wire:click="toggleSelection({{ $item->id }})"
                                        class="hover:bg-gray-50 cursor-pointer {{ in_array($item->id, $selectedMedia) ? 'bg-blue-50' : '' }}">
                                        <td class="px-4 py-3">
                                            <div class="w-5 h-5 rounded border-2 bg-white flex items-center justify-center
                                                        {{ in_array($item->id, $selectedMedia) ? 'border-blue-500 bg-blue-500' : 'border-gray-400' }}">
                                                @if(in_array($item->id, $selectedMedia))
                                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                                    @if(str_starts_with($item->mime_type, 'image/'))
                                                        <img src="{{ $item->getUrl() }}"
                                                             alt="{{ $item->name }}"
                                                             class="w-full h-full object-cover">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $item->file_name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ strtoupper(pathinfo($item->file_name, PATHINFO_EXTENSION)) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ number_format($item->size / 1024, 2) }} KB
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $item->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <button onclick="event.stopPropagation(); @this.call('showDetailsPanel', {{ $item->id }})"
                                                    type="button"
                                                    class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $media->links() }}
                </div>
            @endif
        </div>

        <!-- Details Sidebar -->
        @if($showDetails && $detailsMedia)
            <div class="w-80 border-l border-gray-200 bg-white overflow-y-auto">
                <div class="p-6">
                    <!-- Close Button -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Details</h3>
                        <button wire:click="closeDetails" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Preview -->
                    <div class="mb-6">
                        @if(str_starts_with($detailsMedia->mime_type, 'image/'))
                            <img src="{{ $detailsMedia->getUrl() }}"
                                 alt="{{ $detailsMedia->name }}"
                                 class="w-full rounded-lg border border-gray-200">
                        @else
                            <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- File Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase">Filename</label>
                            <p class="text-sm text-gray-900 mt-1 break-all">{{ $detailsMedia->name }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase">File Type</label>
                            <p class="text-sm text-gray-900 mt-1">{{ $detailsMedia->mime_type }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase">File Size</label>
                            <p class="text-sm text-gray-900 mt-1">{{ number_format($detailsMedia->size / 1024, 2) }} KB</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase">Uploaded</label>
                            <p class="text-sm text-gray-900 mt-1">{{ $detailsMedia->created_at->format('F d, Y g:i A') }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase">URL</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <input type="text"
                                       value="{{ $detailsMedia->getUrl() }}"
                                       readonly
                                       class="flex-1 text-xs border-gray-300 rounded px-2 py-1 bg-gray-50">
                                <button onclick="navigator.clipboard.writeText('{{ $detailsMedia->getUrl() }}')"
                                        class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 pt-6 border-t border-gray-200 space-y-2">
                        <a href="{{ $detailsMedia->getUrl() }}"
                           download
                           class="w-full bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download</span>
                        </a>
                        <button wire:click="deleteMedia({{ $detailsMedia->id }})"
                                onclick="return confirm('Are you sure you want to delete this file?')"
                                class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Delete Permanently</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Picker Mode Footer -->
    @if($pickerMode)
        <div class="border-t border-gray-200 bg-white px-6 py-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    {{ count($selectedMedia) }} {{ Str::plural('file', count($selectedMedia)) }} selected
                </p>
                <div class="flex items-center space-x-2">
                    <button wire:click="$dispatch('closeMediaPicker')"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="selectMediaForPicker"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            {{ empty($selectedMedia) ? 'disabled' : '' }}>
                        Select Files
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Upload Progress Indicator -->
    <div wire:loading wire:target="uploads" class="fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Uploading files...</span>
    </div>
</div>

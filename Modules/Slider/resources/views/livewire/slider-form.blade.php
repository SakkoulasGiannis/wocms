<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sliderId ? 'Edit Slider' : 'Create Slider' }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $sliderId ? 'Update slider details and manage slides' : 'Create a new image slider' }}</p>
        </div>
        <a href="{{ route('admin.slider.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-arrow-left mr-2"></i>Back to Sliders
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <form wire:submit="save">
        <!-- Slider Details -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Slider Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model.live.debounce.300ms="name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Slider name">
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                        <input type="text" wire:model="slug" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="slider-slug">
                        @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional description"></textarea>
                    @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                </div>
            </div>
        </div>

        <!-- Slider Settings -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fa fa-sliders-h mr-2 text-gray-400"></i>Slider Settings</h2>
                <p class="mt-1 text-sm text-gray-500">Control how this slider looks and behaves when rendered.</p>
            </div>
            <div class="p-6 space-y-6">

                {{-- Layout --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Layout</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                            <select wire:model="height" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="h-screen">Full Screen</option>
                                <option value="h-[600px]">600px</option>
                                <option value="h-[500px]">500px</option>
                                <option value="h-[400px]">400px</option>
                                <option value="h-[300px]">300px</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content Max Width</label>
                            <select wire:model="contentMaxWidth" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="max-w-2xl">Narrow (2xl)</option>
                                <option value="max-w-3xl">Medium (3xl)</option>
                                <option value="max-w-4xl">Wide (4xl)</option>
                                <option value="max-w-5xl">Extra Wide (5xl)</option>
                                <option value="max-w-full">Full Width</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Text Position</label>
                            <select wire:model="textPosition" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="center">Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Images</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Image Fit</label>
                            <select wire:model="imageFit" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="cover">Cover (crop to fill)</option>
                                <option value="contain">Contain (show all)</option>
                                <option value="fill">Fill (stretch)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Overlay Color</label>
                            <select wire:model="overlayColor" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="black">Black</option>
                                <option value="brand">Brand</option>
                                <option value="white">White</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Overlay Opacity</label>
                            <select wire:model="overlayOpacity" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="0">None (0%)</option>
                                <option value="0.3">Light (30%)</option>
                                <option value="0.5">Medium (50%)</option>
                                <option value="0.7">Dark (70%)</option>
                                <option value="0.9">Very Dark (90%)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Behavior --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Behavior</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transition Effect</label>
                            <select wire:model="transitionEffect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="fade">Fade</option>
                                <option value="slide">Slide</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Autoplay</label>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" wire:model.live="autoplay" id="autoplay" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="autoplay" class="text-sm text-gray-700">Enable autoplay</label>
                            </div>
                        </div>
                        @if($autoplay)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Autoplay Interval (ms)</label>
                            <input type="number" wire:model="autoplayInterval" min="1000" max="30000" step="500"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="5000">
                            <p class="text-xs text-gray-500 mt-1">1000ms = 1 second</p>
                            @error('autoplayInterval') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Navigation --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Navigation</h3>
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="showArrows" id="show_arrows" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="show_arrows" class="text-sm font-medium text-gray-700">Show Arrow Buttons</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="showDots" id="show_dots" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="show_dots" class="text-sm font-medium text-gray-700">Show Dot Indicators</label>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Slides -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fa fa-images mr-2 text-gray-400"></i>Slides ({{ count($slides) }})
                </h2>
                <button type="button" wire:click="addSlide" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fa fa-plus mr-1"></i>Add Slide
                </button>
            </div>

            <div class="p-6">
                @if(count($slides) === 0)
                <div class="text-center py-8">
                    <i class="fa fa-image text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No slides yet. Click "Add Slide" to get started.</p>
                </div>
                @endif

                <div class="space-y-4">
                    @foreach($slides as $index => $slide)
                    <div wire:key="slide-{{ $index }}" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                    {{ $index + 1 }}
                                </span>
                                <h3 class="font-medium text-gray-700">Slide #{{ $index + 1 }}</h3>
                            </div>
                            <button type="button" wire:click="removeSlide({{ $index }})"
                                    wire:confirm="Remove this slide?"
                                    class="text-red-500 hover:text-red-700 text-sm">
                                <i class="fa fa-trash mr-1"></i>Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Media Upload -->
                            <div>
                                {{-- Media Type Selector --}}
                                <label class="block text-sm font-medium text-gray-700 mb-1">Media Type</label>
                                <select wire:model.live="slides.{{ $index }}.media_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm mb-2">
                                    <option value="image">🖼️ Image</option>
                                    <option value="video">🎬 Video (MP4)</option>
                                    <option value="youtube">▶️ YouTube</option>
                                </select>

                                {{-- Image upload (always shown as poster/thumbnail) --}}
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ ($slide['media_type'] ?? 'image') === 'image' ? 'Image' : 'Poster / Thumbnail' }}
                                </label>
                                @if(!empty($slide['image_url']))
                                <div class="mb-2 relative group">
                                    <img src="{{ $slide['image_url'] }}" alt="Slide image" class="w-full h-24 object-cover rounded-lg border">
                                </div>
                                @endif
                                <input type="file" wire:model="newImages.{{ $index }}" accept="image/*"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <div wire:loading wire:target="newImages.{{ $index }}" class="text-xs text-blue-600 mt-1">
                                    <i class="fa fa-spinner fa-spin mr-1"></i>Uploading...
                                </div>
                                @error('newImages.' . $index) <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                                {{-- Video file upload --}}
                                @if(($slide['media_type'] ?? 'image') === 'video')
                                <div class="mt-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Video File (MP4/WebM)</label>
                                    @if(!empty($slide['video_file_url']))
                                    <div class="mb-1 text-xs text-green-600"><i class="fa fa-check-circle mr-1"></i>Video uploaded</div>
                                    @endif
                                    <input type="file" wire:model="newVideos.{{ $index }}" accept="video/mp4,video/webm,video/ogg"
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                    <div wire:loading wire:target="newVideos.{{ $index }}" class="text-xs text-purple-600 mt-1">
                                        <i class="fa fa-spinner fa-spin mr-1"></i>Uploading video...
                                    </div>
                                    @error('newVideos.' . $index) <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                                @endif

                                {{-- YouTube URL --}}
                                @if(($slide['media_type'] ?? 'image') === 'youtube')
                                <div class="mt-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">YouTube URL</label>
                                    <input type="text" wire:model="slides.{{ $index }}.video_url"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                           placeholder="https://www.youtube.com/watch?v=...">
                                    @error('slides.' . $index . '.video_url') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>
                                @endif
                            </div>

                            <!-- Text Fields -->
                            <div class="md:col-span-2 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                        <input type="text" wire:model="slides.{{ $index }}.title" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Slide title">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Link URL</label>
                                        <input type="text" wire:model="slides.{{ $index }}.link" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="https://...">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                        <input type="text" wire:model="slides.{{ $index }}.description" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Short description">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Button Text</label>
                                        <input type="text" wire:model="slides.{{ $index }}.button_text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Learn More">
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="slides.{{ $index }}.is_active" id="slide_active_{{ $index }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="slide_active_{{ $index }}" class="text-xs text-gray-600">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.slider.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition">
                Cancel
            </a>
            <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition disabled:opacity-50">
                <span wire:loading.remove wire:target="save">
                    <i class="fa fa-save mr-2"></i>{{ $sliderId ? 'Update Slider' : 'Create Slider' }}
                </span>
                <span wire:loading wire:target="save">
                    <i class="fa fa-spinner fa-spin mr-2"></i>Saving...
                </span>
            </button>
        </div>

        <!-- Usage / Embed Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg shadow mb-6 p-5 mt-6">
            <h2 class="text-sm font-semibold text-blue-800 mb-2"><i class="fa fa-code mr-1"></i> How to use this Slider</h2>
            <div class="space-y-2 text-sm text-blue-900">
                <div>
                    <span class="font-medium">Via PageSection:</span>
                    <span class="ml-1 text-blue-700">Add a section with type <code class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded text-xs">hero_slider</code> to any page in the admin.</span>
                </div>
                <div>
                    <span class="font-medium">Blade component:</span>
                    <code class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs select-all">&lt;x-section-hero-slider :section="$section" /&gt;</code>
                </div>
                <div>
                    <span class="font-medium">Blade include:</span>
                    <code class="ml-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs select-all">@@include('components.section-hero-slider', ['section' => $section])</code>
                </div>
            </div>
            <p class="text-xs text-blue-600 mt-2"><i class="fa fa-info-circle mr-1"></i> Sliders render through the PageSection system. Add a "Hero Slider" section to a page and select this slider's slides.</p>
        </div>
    </form>
</div>

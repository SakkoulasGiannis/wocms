<div class="px-4 sm:px-0">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sliders</h1>
            <p class="mt-1 text-sm text-gray-600">Manage image sliders for your website</p>
        </div>
        <a href="{{ route('admin.slider.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-plus mr-2"></i>
            Create Slider
        </a>
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

    <!-- Sliders Table -->
    @if($sliders->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slider</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slides</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($sliders as $slider)
                <tr wire:key="slider-{{ $slider->id }}" class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $slider->name }}</div>
                        @if($slider->description)
                            <div class="text-sm text-gray-500 mt-1">{{ Str::limit($slider->description, 80) }}</div>
                        @endif
                        <div class="text-xs text-gray-400 mt-1">
                            Slug: <code class="bg-gray-100 px-1 rounded">{{ $slider->slug }}</code>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fa fa-images mr-1"></i>{{ $slider->slides_count }} slides
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button wire:click="toggleActive({{ $slider->id }})"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $slider->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                            @if($slider->is_active)
                                <i class="fa fa-check-circle mr-1"></i>Active
                            @else
                                <i class="fa fa-times-circle mr-1"></i>Inactive
                            @endif
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.slider.edit', $slider->id) }}"
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fa fa-edit mr-1"></i>Edit
                            </a>
                            <button wire:click="delete({{ $slider->id }})"
                                    wire:confirm="Are you sure you want to delete '{{ $slider->name }}'? All slides will be removed."
                                    class="text-red-600 hover:text-red-900">
                                <i class="fa fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fa fa-images text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No sliders found</p>
        <p class="text-gray-400 text-sm mt-2">Create your first slider to get started</p>
        <a href="{{ route('admin.slider.create') }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fa fa-plus mr-2"></i>Create Slider
        </a>
    </div>
    @endif
</div>

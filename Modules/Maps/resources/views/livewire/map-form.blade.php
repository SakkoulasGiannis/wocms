<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">{{ $mapId ? 'Edit Map' : 'Create Map' }}</h1></div>
        <a href="{{ route('admin.maps.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition"><i class="fa fa-arrow-left mr-2"></i>Back</a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <form wire:submit="save">
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Map Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Title *</label><input type="text" wire:model.live.debounce.300ms="title" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">@error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input type="text" wire:model="slug" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
            </div>
            <div class="mt-4"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea wire:model="description" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea></div>
        </div>

        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Map Settings</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Default Lat</label><input type="text" wire:model="default_lat" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Default Lng</label><input type="text" wire:model="default_lng" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Zoom</label><input type="number" wire:model="default_zoom" min="1" max="20" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Min Zoom</label><input type="number" wire:model="min_zoom" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Max Zoom</label><input type="number" wire:model="max_zoom" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
            </div>
            <div class="flex items-center gap-6 mt-4">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="show_controls" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Show Controls</span></label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="show_search" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Show Search</span></label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="show_legend" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Show Legend</span></label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="active" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Active</span></label>
            </div>
        </div>

        <!-- Markers -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900"><i class="fa fa-map-marker-alt mr-2 text-gray-400"></i>Markers ({{ count($markers) }})</h2>
                <button type="button" wire:click="addMarker" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg"><i class="fa fa-plus mr-1"></i>Add Marker</button>
            </div>
            @foreach($markers as $index => $marker)
            <div wire:key="marker-{{ $index }}" class="border border-gray-200 rounded-lg p-4 mb-3 bg-gray-50">
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Marker #{{ $index + 1 }}</span>
                    <button type="button" wire:click="removeMarker({{ $index }})" class="text-red-500 hover:text-red-700 text-sm"><i class="fa fa-trash"></i></button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div><label class="block text-xs text-gray-600 mb-1">Latitude</label><input type="text" wire:model="markers.{{ $index }}.lat" class="w-full rounded-lg border-gray-300 text-sm"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Longitude</label><input type="text" wire:model="markers.{{ $index }}.lng" class="w-full rounded-lg border-gray-300 text-sm"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Title</label><input type="text" wire:model="markers.{{ $index }}.title" class="w-full rounded-lg border-gray-300 text-sm"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Color</label><input type="color" wire:model="markers.{{ $index }}.color" class="w-full h-9 rounded-lg border-gray-300"></div>
                </div>
            </div>
            @endforeach
            @if(empty($markers))
            <p class="text-gray-400 text-sm text-center py-4">No markers added yet.</p>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.maps.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"><i class="fa fa-save mr-2"></i>{{ $mapId ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>

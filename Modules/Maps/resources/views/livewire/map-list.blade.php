<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Maps</h1>
            <p class="mt-1 text-sm text-gray-600">Manage interactive maps</p>
        </div>
        <a href="{{ route('admin.maps.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition"><i class="fa fa-plus mr-2"></i>Create Map</a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    @if($maps->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Map</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Markers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-40">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($maps as $map)
                <tr wire:key="map-{{ $map->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $map->title }}</div>
                        <div class="text-xs text-gray-400">{{ $map->slug }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800"><i class="fa fa-map-marker-alt mr-1"></i>{{ $map->markers_count }} markers</span>
                    </td>
                    <td class="px-6 py-4">
                        <button wire:click="toggleActive({{ $map->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $map->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $map->active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('admin.maps.edit', $map->id) }}" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fa fa-edit"></i></a>
                        <button wire:click="delete({{ $map->id }})" wire:confirm="Delete '{{ $map->title }}'?" class="text-red-600 hover:text-red-900"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fa fa-map text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No maps found</p>
    </div>
    @endif
</div>

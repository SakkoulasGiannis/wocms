<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Image Maps</h1>
            <p class="mt-1 text-sm text-gray-600">Manage interactive image maps with hotspots</p>
        </div>
        <a href="{{ route('admin.imagemaps.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition"><i class="fa fa-plus mr-2"></i>Create Image Map</a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    @if($imageMaps->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image Map</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hotspots</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-40">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($imageMaps as $map)
                <tr wire:key="imagemap-{{ $map->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($map->getFirstMediaUrl('image', 'thumb'))
                                <img src="{{ $map->getFirstMediaUrl('image', 'thumb') }}" alt="" class="w-16 h-12 object-cover rounded border border-gray-200">
                            @else
                                <div class="w-16 h-12 bg-gray-100 rounded border border-gray-200 flex items-center justify-center"><i class="fa fa-image text-gray-300"></i></div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ $map->title }}</div>
                                <div class="text-xs text-gray-400">{{ $map->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @php $shapesCount = count($map->items['shapes'] ?? $map->items ?? []); @endphp
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800">{{ $shapesCount }} shapes</span>
                    </td>
                    <td class="px-6 py-4">
                        <button wire:click="toggleActive({{ $map->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $map->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $map->active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('admin.imagemaps.edit', $map->id) }}" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fa fa-edit"></i></a>
                        <button wire:click="delete({{ $map->id }})" wire:confirm="Delete '{{ $map->title }}'?" class="text-red-600 hover:text-red-900"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-12">
        <i class="fa fa-image text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No image maps found</p>
    </div>
    @endif
</div>

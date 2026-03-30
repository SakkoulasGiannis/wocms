<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Properties</h1>
            <p class="mt-1 text-sm text-gray-600">Manage property listings</p>
        </div>
        <a href="{{ route('admin.properties.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-plus mr-2"></i>Add Property
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search properties..." class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select wire:model.live="filterType" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Types</option>
                @foreach($propertyTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($properties->count())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Property</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type / Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Active</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-40">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($properties as $property)
                <tr wire:key="property-{{ $property->id }}" class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($property->getFirstMediaUrl('featured_image', 'thumb'))
                                <img src="{{ $property->getFirstMediaUrl('featured_image', 'thumb') }}" class="w-12 h-12 rounded-lg object-cover mr-3">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center mr-3"><i class="fa fa-building text-gray-400"></i></div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ $property->title }}</div>
                                @if($property->bedrooms || $property->bathrooms || $property->area)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if($property->bedrooms)<span class="mr-2"><i class="fa fa-bed mr-1"></i>{{ $property->bedrooms }}</span>@endif
                                        @if($property->bathrooms)<span class="mr-2"><i class="fa fa-bath mr-1"></i>{{ $property->bathrooms }}</span>@endif
                                        @if($property->area)<span><i class="fa fa-ruler-combined mr-1"></i>{{ $property->area }}m&sup2;</span>@endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ $propertyTypes[$property->property_type] ?? $property->property_type }}</span>
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 ml-1">{{ $statuses[$property->status] ?? $property->status }}</span>
                    </td>
                    <td class="px-6 py-4 font-medium">{{ $property->formatted_price }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $property->city }}{{ $property->address ? ', ' . $property->address : '' }}</td>
                    <td class="px-6 py-4">
                        <button wire:click="toggleActive({{ $property->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $property->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $property->active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('admin.properties.edit', $property->id) }}" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fa fa-edit"></i></a>
                        <button wire:click="delete({{ $property->id }})" wire:confirm="Delete '{{ $property->title }}'?" class="text-red-600 hover:text-red-900"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $properties->links() }}</div>
    @else
    <div class="text-center py-12">
        <i class="fa fa-building text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No properties found</p>
    </div>
    @endif
</div>

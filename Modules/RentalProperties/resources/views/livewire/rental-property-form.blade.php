<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">{{ $propertyId ? 'Edit Rental Property' : 'Create Rental Property' }}</h1></div>
        <a href="{{ route('admin.rentals.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition"><i class="fa fa-arrow-left mr-2"></i>Back</a>
    </div>
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    <form wire:submit="save">
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Title *</label><input type="text" wire:model.live.debounce.300ms="title" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">@error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug</label><input type="text" wire:model="slug" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
            </div>
            <div class="mt-4"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea wire:model="description" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Type</label><select wire:model="property_type" class="w-full rounded-lg border-gray-300">@foreach($propertyTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select wire:model="status" class="w-full rounded-lg border-gray-300">@foreach($statuses as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Monthly Rent *</label><input type="number" step="0.01" wire:model="price" class="w-full rounded-lg border-gray-300">@error('price') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror</div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency</label><input type="text" wire:model="currency" maxlength="3" class="w-full rounded-lg border-gray-300"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Area (m²)</label><input type="number" step="0.01" wire:model="area" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Land Size</label><input type="number" step="0.01" wire:model="land_size" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Bedrooms</label><input type="number" wire:model="bedrooms" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Bathrooms</label><input type="number" wire:model="bathrooms" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Rooms</label><input type="number" wire:model="rooms" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Garages</label><input type="number" wire:model="garages" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Floor</label><input type="number" wire:model="floor" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Year Built</label><input type="number" wire:model="year_built" class="w-full rounded-lg border-gray-300"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Location</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><input type="text" wire:model="address" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">City</label><input type="text" wire:model="city" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">State</label><input type="text" wire:model="state" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Country</label><input type="text" wire:model="country" class="w-full rounded-lg border-gray-300"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Media</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                    @if($currentFeaturedImage)<img src="{{ $currentFeaturedImage }}" class="w-full h-40 object-cover rounded-lg mb-2">@endif
                    <input type="file" wire:model="featuredImageUpload" accept="image/*" class="w-full text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gallery</label>
                    @if(!empty($currentGallery))<div class="flex flex-wrap gap-2 mb-2">@foreach($currentGallery as $img)<div class="relative group"><img src="{{ $img['url'] }}" class="w-20 h-20 object-cover rounded-lg"><button type="button" wire:click="removeGalleryImage({{ $img['id'] }})" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hidden group-hover:flex items-center justify-center">&times;</button></div>@endforeach</div>@endif
                    <input type="file" wire:model="galleryUploads" accept="image/*" multiple class="w-full text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700">
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">SEO & Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label><input type="text" wire:model="meta_title" class="w-full rounded-lg border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label><input type="text" wire:model="meta_description" class="w-full rounded-lg border-gray-300"></div>
            </div>
            <div class="flex items-center gap-6 mt-4">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="active" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Active</span></label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="featured" class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Featured</span></label>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.rentals.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium"><i class="fa fa-save mr-2"></i>{{ $propertyId ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>

<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $categoryId ? 'Edit Category' : 'Add Category' }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $categoryId ? 'Update category details' : 'Create a new blog category' }}</p>
        </div>
        <a href="{{ route('admin.blog.categories.index') }}"
           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm transition">
            ← Back
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-3xl space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
            <input type="text" wire:model.live.debounce.500ms="name"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Slug <span class="text-red-500">*</span></label>
            <input type="text" wire:model.live="slug"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">
            <p class="mt-1 text-xs text-gray-500">Used in URLs: <code>/blog/category/{slug}</code>. Auto-filled from the name until you edit it.</p>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Parent category</label>
            <select wire:model="parent_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">— None (top level) —</option>
                @foreach($this->parentOptions as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea wire:model="description" rows="3"
                      class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Order</label>
                <input type="number" wire:model="order" min="0"
                       class="mt-1 block w-32 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <p class="mt-1 text-xs text-gray-500">Lower numbers come first.</p>
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center">
                    <input type="checkbox" wire:model="is_active"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t flex items-center gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
                {{ $categoryId ? 'Save changes' : 'Create category' }}
            </button>
            <a href="{{ route('admin.blog.categories.index') }}"
               class="px-5 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
        </div>
    </form>
</div>

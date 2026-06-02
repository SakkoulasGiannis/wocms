<div class="px-4 sm:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Blog Tags</h1>
        <p class="mt-1 text-sm text-gray-600">
            Tags are created automatically when you type them on a blog post. Use this screen to rename or delete tags.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="mb-3 flex items-center justify-between gap-3">
        <div class="relative max-w-md flex-1">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search tags…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M16.65 11.325A5.325 5.325 0 1 1 6 11.325a5.325 5.325 0 0 1 10.65 0z"/></svg>
            </span>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <label for="tagPerPage" class="text-gray-600">Per page</label>
            <select id="tagPerPage" wire:model.live="perPage"
                    class="rounded-lg border-gray-300 text-sm py-1.5 pl-3 pr-8 focus:border-blue-500 focus:ring-blue-500">
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($tags->total() === 0)
            <div class="text-center py-12 text-gray-500">
                @if(trim($search) !== '')
                    No tags match “{{ $search }}”.
                @else
                    No tags yet. They'll appear here once you tag a blog post.
                @endif
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tag</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tags as $tag)
                        <tr wire:key="tag-{{ $tag->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm">
                                @if($editingId === $tag->id)
                                    <input type="text" wire:model="editName"
                                           class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-64"
                                           wire:keydown.enter="saveEdit">
                                    @error('editName') <span class="ml-2 text-xs text-red-600">{{ $message }}</span> @enderror
                                @else
                                    <span class="font-medium text-gray-900">{{ $tag->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $tag->slug }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $tag->blogs_count }}</td>
                            <td class="px-6 py-3 text-sm text-right space-x-2">
                                @if($editingId === $tag->id)
                                    <button type="button" wire:click="saveEdit"
                                            class="text-green-700 hover:text-green-900">Save</button>
                                    <button type="button" wire:click="cancelEdit"
                                            class="text-gray-500 hover:text-gray-700">Cancel</button>
                                @else
                                    <button type="button" wire:click="startEdit({{ $tag->id }})"
                                            class="text-blue-600 hover:text-blue-800">Rename</button>
                                    <button type="button" wire:click="delete({{ $tag->id }})"
                                            wire:confirm="Delete tag “{{ $tag->name }}”? It will be removed from {{ $tag->blogs_count }} post(s)."
                                            class="text-red-600 hover:text-red-800">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-3 border-t">{{ $tags->links() }}</div>
        @endif
    </div>
</div>

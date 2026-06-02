<div class="px-4 sm:px-0">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Blog Categories</h1>
            <p class="mt-1 text-sm text-gray-600">Hierarchical categories used to organise blog posts.</p>
        </div>
        <a href="{{ route('admin.blog.categories.create') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            + Add Category
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <div class="relative max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search categories…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M16.65 11.325A5.325 5.325 0 1 1 6 11.325a5.325 5.325 0 0 1 10.65 0z"/></svg>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if(empty($rows))
            <div class="text-center py-12 text-gray-500">
                @if($isSearch)
                    No categories match “{{ $search }}”.
                @else
                    No categories yet — create your first one.
                @endif
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rows as $r)
                        @php $c = $r['model']; $d = (int) $r['depth']; @endphp
                        <tr wire:key="cat-{{ $c->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900">
                                <span style="padding-left: {{ $d * 18 }}px">
                                    @if($d > 0)<span class="text-gray-300">└─</span>@endif
                                    <span class="font-medium">{{ $c->name }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $c->slug }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $c->blogs->count() }}</td>
                            <td class="px-6 py-3 text-sm">
                                @if($c->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-right">
                                <a href="{{ route('admin.blog.categories.edit', $c->id) }}"
                                   class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                                <button type="button"
                                        wire:click="delete({{ $c->id }})"
                                        wire:confirm="Delete category “{{ $c->name }}”? Any sub-categories will move to the top level. Posts keep their other categories."
                                        class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

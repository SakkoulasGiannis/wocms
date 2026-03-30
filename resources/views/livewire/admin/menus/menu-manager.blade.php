<div class="px-4 sm:px-0">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Menu Manager</h1>
            <p class="mt-1 text-sm text-gray-600">Create and manage navigation menus</p>
        </div>
        <button wire:click="openCreateMenu" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition">
            <i class="fa fa-plus mr-2"></i>New Menu
        </button>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <!-- Menu Selector -->
    @if($menus->count() > 0)
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Select menu:</label>
            <select wire:model.live="selectedMenuId" wire:change="selectMenu($event.target.value)" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->name }} @if($menu->location)({{ $menu->location }})@endif</option>
                @endforeach
            </select>
            <button wire:click="openEditMenu" class="text-sm text-blue-600 hover:text-blue-800"><i class="fa fa-edit mr-1"></i>Edit Menu</button>
            <button wire:click="deleteMenu" wire:confirm="Delete this menu and all its items?" class="text-sm text-red-600 hover:text-red-800"><i class="fa fa-trash mr-1"></i>Delete</button>
        </div>
    </div>
    @endif

    @if($selectedMenuId)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Menu Items Tree -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Menu Structure</h2>
                    <p class="text-xs text-gray-500 mt-1">Drag items to reorder. Drag right to create submenus.</p>
                </div>
                <div class="p-4">
                    @if(count($menuItems) > 0)
                    <ul id="menu-sortable" class="space-y-1">
                        @foreach($menuItems as $item)
                            @include('livewire.admin.menus.partials.menu-item-node', ['item' => $item, 'depth' => 0])
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center py-8">
                        <i class="fa fa-bars text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No items in this menu. Add items from the right panel.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Add Items Panel -->
        <div class="space-y-4">
            <!-- Homepage -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Homepage</h3>
                <button wire:click="addHomepageItem" class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition">
                    <i class="fa fa-home mr-2"></i>Add Home Link
                </button>
            </div>

            <!-- Custom Link -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Custom Link</h3>
                <form wire:submit="addCustomItem" class="space-y-2">
                    <input type="text" wire:model.live="customTitle" placeholder="Title" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <input type="text" wire:model.live="customUrl" placeholder="URL (e.g. /about)" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select wire:model="customTarget" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="_self">Same Window</option>
                        <option value="_blank">New Tab</option>
                    </select>
                    @error('customTitle') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    @error('customUrl') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    <button type="submit" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                        <i class="fa fa-plus mr-1"></i>Add Custom Link
                    </button>
                </form>
            </div>

            <!-- Templates -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Templates</h3>
                <div class="max-h-48 overflow-y-auto space-y-1 mb-3">
                    @foreach($availableTemplates as $template)
                    <label class="flex items-center gap-2 p-1.5 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTemplates" value="{{ $template->id }}" class="rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">{{ $template->menu_label ?: $template->name }}</span>
                        <span class="text-xs text-gray-400">/{{ $template->slug }}</span>
                    </label>
                    @endforeach
                </div>
                @if(count($selectedTemplates) > 0)
                <button wire:click="addTemplateItems" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    <i class="fa fa-plus mr-1"></i>Add Selected ({{ count($selectedTemplates) }})
                </button>
                @endif
            </div>

            <!-- Search Pages/Entries -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Search Pages & Entries</h3>
                <input type="text" wire:model.live.debounce.300ms="entrySearch" placeholder="Search by title..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-2">
                @if(!empty($searchResults))
                <div class="max-h-48 overflow-y-auto space-y-1">
                    @foreach($searchResults as $result)
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm text-gray-900 truncate">{{ $result['title'] }}</div>
                            <div class="text-xs text-gray-400">{{ $result['type'] }} &middot; {{ $result['url'] }}</div>
                        </div>
                        <button wire:click="addSearchResult({{ $result['index'] }})" class="ml-2 px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded-lg shrink-0">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                @elseif(strlen($entrySearch ?? '') >= 2)
                <p class="text-xs text-gray-400 text-center py-2">No results found</p>
                @else
                <p class="text-xs text-gray-400 text-center py-2">Type at least 2 characters to search</p>
                @endif
            </div>

            <!-- Item Editor -->
            @if($showItemEditor)
            <div class="bg-white rounded-lg shadow p-4 border-2 border-blue-300">
                <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fa fa-edit mr-1"></i>Edit Item</h3>
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                        <input type="text" wire:model="itemTitle" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('itemTitle') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">URL</label>
                        <input type="text" wire:model="itemUrl" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Target</label>
                        <select wire:model="itemTarget" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="_self">Same Window</option>
                            <option value="_blank">New Tab</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">CSS Class</label>
                        <input type="text" wire:model="itemCssClass" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional">
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button wire:click="updateItem" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Save</button>
                        <button wire:click="$set('showItemEditor', false)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg">Cancel</button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @elseif($menus->count() === 0)
    <div class="text-center py-12">
        <i class="fa fa-bars text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No menus yet</p>
        <p class="text-gray-400 text-sm mt-2">Create your first menu to get started</p>
        <button wire:click="openCreateMenu" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"><i class="fa fa-plus mr-2"></i>Create Menu</button>
    </div>
    @endif

    <!-- Create/Edit Menu Modal -->
    @if($showMenuModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showMenuModal', false)">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $editingMenuId ? 'Edit Menu' : 'Create Menu' }}</h2>
            </div>
            <form wire:submit="saveMenu">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Menu Name *</label>
                        <input type="text" wire:model="menuName" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Main Navigation">
                        @error('menuName') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select wire:model="menuLocation" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">None</option>
                            <option value="header">Header</option>
                            <option value="footer">Footer</option>
                            <option value="sidebar">Sidebar</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Assign to a theme location for automatic display</p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showMenuModal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">{{ $editingMenuId ? 'Update' : 'Create' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('livewire:init', () => {
    initSortable();

    Livewire.hook('morph.updated', ({ el }) => {
        setTimeout(initSortable, 150);
    });

    Livewire.hook('commit', ({ succeed }) => {
        succeed(({ effects }) => {
            setTimeout(initSortable, 200);
        });
    });
});

function initSortable() {
    const el = document.getElementById('menu-sortable');
    if (!el) return;

    // Destroy existing sortable instances before reinitializing
    if (el.sortableInstance) {
        el.sortableInstance.destroy();
    }
    el.querySelectorAll('.nested-sortable').forEach(function(nested) {
        if (nested.sortableInstance) {
            nested.sortableInstance.destroy();
        }
    });

    // Initialize root sortable
    el.sortableInstance = new Sortable(el, {
        group: 'menu-items',
        animation: 150,
        handle: '.drag-handle',
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onEnd: function() {
            saveOrder();
        }
    });

    // Initialize nested sortables
    el.querySelectorAll('.nested-sortable').forEach(function(nested) {
        nested.sortableInstance = new Sortable(nested, {
            group: 'menu-items',
            animation: 150,
            handle: '.drag-handle',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd: function() {
                saveOrder();
            }
        });
    });
}

function saveOrder() {
    const el = document.getElementById('menu-sortable');
    if (!el) return;

    function getOrder(ul) {
        const items = [];
        ul.querySelectorAll(':scope > li').forEach(function(li) {
            const id = parseInt(li.dataset.id);
            const nested = li.querySelector(':scope > .nested-sortable');
            items.push({
                id: id,
                children: nested ? getOrder(nested) : []
            });
        });
        return items;
    }

    const order = getOrder(el);
    Livewire.find(el.closest('[wire\\:id]').getAttribute('wire:id')).call('updateItemOrder', order);
}
</script>
@endpush

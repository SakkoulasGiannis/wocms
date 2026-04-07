<li data-id="{{ $item['id'] }}" class="menu-item">
    <div class="flex items-center p-3 bg-white border border-gray-200 rounded-lg mb-1 hover:border-blue-300 transition {{ !$item['is_active'] ? 'opacity-50' : '' }}">
        <span class="drag-handle cursor-move mr-3 text-gray-400 hover:text-gray-600">
            <i class="fa fa-grip-vertical"></i>
        </span>
        <div class="flex-1 min-w-0">
            <span class="font-medium text-gray-900 text-sm">{{ $item['title'] }}</span>
            <span class="text-xs text-gray-400 ml-2">
                @if($item['type'] === 'homepage')
                    <i class="fa fa-home mr-1"></i>/
                @elseif($item['type'] === 'template')
                    <i class="fa fa-file mr-1"></i>{{ $item['resolved_url'] }}
                @else
                    {{ $item['resolved_url'] }}
                @endif
            </span>
            @if($item['type'] !== 'custom' && $item['type'] !== 'homepage')
                <span class="inline-flex ml-2 px-1.5 py-0.5 text-xs rounded bg-blue-50 text-blue-600">{{ $item['type'] }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2 ml-2">
            <button onclick="Livewire.find(this.closest('[wire\\:id]').getAttribute('wire:id')).call('toggleItemActive', {{ $item['id'] }})"
                    class="text-xs {{ $item['is_active'] ? 'text-green-600' : 'text-gray-400' }}" title="Toggle active">
                <i class="fa {{ $item['is_active'] ? 'fa-eye' : 'fa-eye-slash' }}"></i>
            </button>
            <button onclick="Livewire.find(this.closest('[wire\\:id]').getAttribute('wire:id')).call('editItem', {{ $item['id'] }})"
                    class="text-xs text-blue-600 hover:text-blue-800" title="Edit">
                <i class="fa fa-edit"></i>
            </button>
            <button onclick="if(confirm('Delete this item and its children?')) Livewire.find(this.closest('[wire\\:id]').getAttribute('wire:id')).call('deleteItem', {{ $item['id'] }})"
                    class="text-xs text-red-600 hover:text-red-800" title="Delete">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
    <ul class="nested-sortable pl-8 min-h-[4px]">
        @if(!empty($item['children']))
            @foreach($item['children'] as $child)
                @include('livewire.admin.menus.partials.menu-item-node', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        @endif
    </ul>
</li>

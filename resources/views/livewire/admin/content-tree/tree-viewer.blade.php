@section('page-title', 'Content Tree')

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Content Tree</h2>
            <p class="text-sm text-gray-600 mt-1">Visual representation of your site structure</p>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tree -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            @if($rootNodes->count() > 0)
                <div class="space-y-2">
                    @foreach($rootNodes as $node)
                        @include('livewire.admin.content-tree.partials.tree-node', ['node' => $node, 'level' => 0])
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No content yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Start by creating content using templates.</p>
                </div>
            @endif
        </div>
    </div>
</div>

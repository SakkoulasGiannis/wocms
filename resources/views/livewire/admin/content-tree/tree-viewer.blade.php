@section('page-title', 'Content Tree Builder')

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Content Tree Builder</h2>
            <p class="text-sm text-gray-600 mt-1">Visual page builder with sections management</p>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Two Column Layout -->
    <div class="grid grid-cols-12 gap-6">
        <!-- Left Panel: Content Tree -->
        <div class="col-span-4">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700">Content Tree</h3>
                </div>
                <div class="p-4 max-h-[calc(100vh-250px)] overflow-y-auto">
                    @if($templates->count() > 0)
                        <div class="space-y-1">
                            @foreach($templates as $template)
                                @include('livewire.admin.content-tree.partials.template-node', ['template' => $template])
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No templates yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Start by creating templates.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Panel: Section Builder -->
        <div class="col-span-8">
            @if($selectedNodeId)
                @livewire('admin.content-tree.section-builder', ['nodeId' => $selectedNodeId], key('section-builder-'.$selectedNodeId))
            @else
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Select a page to manage sections</h3>
                        <p class="mt-2 text-sm text-gray-500">Click on any page in the content tree to view and edit its sections.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="px-4 sm:px-0">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fab fa-wordpress mr-2 text-blue-600"></i>WordPress Sync</h1>
        <p class="mt-1 text-sm text-gray-600">Import content from a WordPress site via REST API</p>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <!-- Tabs -->
    <div class="mb-4 border-b border-gray-200">
        <nav class="flex gap-4">
            <button wire:click="$set('activeTab', 'settings')"
                    class="pb-3 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fa fa-cog mr-1"></i> Settings
            </button>
            <button wire:click="fetchPosts"
                    class="pb-3 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'posts' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                    @if(!$wpUrl) disabled title="Configure WordPress URL first" @endif>
                <i class="fa fa-file-alt mr-1"></i> Posts
                @if($wpPostsTotal) <span class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $wpPostsTotal }}</span> @endif
            </button>
            <button wire:click="fetchPages"
                    class="pb-3 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'pages' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                    @if(!$wpUrl) disabled title="Configure WordPress URL first" @endif>
                <i class="fa fa-copy mr-1"></i> Pages
                @if($wpPagesTotal) <span class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">{{ $wpPagesTotal }}</span> @endif
            </button>
        </nav>
    </div>

    <!-- Settings Tab -->
    @if($activeTab === 'settings')
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">WordPress Connection</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">WordPress Site URL *</label>
                <input type="url" wire:model="wpUrl" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="https://example.com">
                <p class="text-xs text-gray-500 mt-1">The WordPress site must have REST API enabled (default in WP 4.7+)</p>
                @error('wpUrl') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="wpUsername" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="admin">
                    <p class="text-xs text-gray-500 mt-1">Needed for draft/private content</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Password <span class="text-gray-400">(optional)</span></label>
                    <input type="password" wire:model="wpAppPassword" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="xxxx xxxx xxxx xxxx">
                    <p class="text-xs text-gray-500 mt-1">Generate in WP Admin > Users > Application Passwords</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="saveSettings" wire:loading.attr="disabled" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveSettings"><i class="fa fa-save mr-1"></i> Save & Test</span>
                    <span wire:loading wire:target="saveSettings"><i class="fa fa-spinner fa-spin mr-1"></i> Testing...</span>
                </button>

                @if($connectionStatus)
                    @if($connectionStatus['success'])
                        <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm">
                            <i class="fa fa-check-circle mr-1"></i> Connected: {{ $connectionStatus['name'] ?? 'OK' }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 bg-red-50 text-red-700 rounded-full text-sm">
                            <i class="fa fa-times-circle mr-1"></i> {{ $connectionStatus['error'] ?? 'Connection failed' }}
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Posts Tab -->
    @if($activeTab === 'posts')
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-gray-900">WordPress Posts ({{ $wpPostsTotal }})</h2>
                <button wire:click="fetchPosts" class="text-sm text-blue-600 hover:text-blue-800"><i class="fa fa-sync-alt mr-1"></i> Refresh</button>
            </div>
            <div class="flex items-center gap-2">
                @if(count($wpPosts))
                    <button wire:click="selectAllPosts" class="text-xs text-blue-600 hover:underline">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button wire:click="deselectAllPosts" class="text-xs text-gray-500 hover:underline">Deselect</button>
                    <span class="text-gray-300 mx-1">|</span>
                    <span class="text-xs text-gray-500">{{ count($selectedPosts) }} selected</span>
                @endif
                <button wire:click="syncSelectedPosts"
                        wire:loading.attr="disabled"
                        wire:confirm="Import {{ count($selectedPosts) }} selected posts into Blog?"
                        @if(empty($selectedPosts)) disabled @endif
                        class="ml-3 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="syncSelectedPosts"><i class="fa fa-download mr-1"></i> Import Selected</span>
                    <span wire:loading wire:target="syncSelectedPosts"><i class="fa fa-spinner fa-spin mr-1"></i> Importing...</span>
                </button>
            </div>
        </div>

        <div wire:loading.flex wire:target="fetchPosts" class="p-8 justify-center">
            <div class="text-center text-gray-500"><i class="fa fa-spinner fa-spin text-2xl mb-2"></i><p>Fetching posts...</p></div>
        </div>

        @if(count($wpPosts))
        <table class="min-w-full divide-y divide-gray-200" wire:loading.remove wire:target="fetchPosts">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($wpPosts as $post)
                <tr class="hover:bg-gray-50 {{ in_array($post['id'], $selectedPosts) ? 'bg-blue-50' : '' }}">
                    <td class="px-4 py-3">
                        <input type="checkbox"
                               {{ in_array($post['id'], $selectedPosts) ? 'checked' : '' }}
                               wire:click="togglePost({{ $post['id'] }})"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{!! $post['title']['rendered'] ?? '' !!}</div>
                        @if(!empty($post['excerpt']['rendered']))
                            <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit(strip_tags($post['excerpt']['rendered']), 80) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($post['date'])->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $post['status'] === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($post['status']) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $post['slug'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @elseif(!$isSyncing)
        <div class="p-8 text-center text-gray-500" wire:loading.remove wire:target="fetchPosts">
            <p>No posts found or still loading.</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Pages Tab -->
    @if($activeTab === 'pages')
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-gray-900">WordPress Pages ({{ $wpPagesTotal }})</h2>
                <button wire:click="fetchPages" class="text-sm text-blue-600 hover:text-blue-800"><i class="fa fa-sync-alt mr-1"></i> Refresh</button>
            </div>
            <div class="flex items-center gap-2">
                @if(count($wpPages))
                    <button wire:click="selectAllPages" class="text-xs text-blue-600 hover:underline">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button wire:click="deselectAllPages" class="text-xs text-gray-500 hover:underline">Deselect</button>
                    <span class="text-gray-300 mx-1">|</span>
                    <span class="text-xs text-gray-500">{{ count($selectedPages) }} selected</span>
                @endif
                <button wire:click="syncSelectedPages"
                        wire:loading.attr="disabled"
                        wire:confirm="Import {{ count($selectedPages) }} selected pages?"
                        @if(empty($selectedPages)) disabled @endif
                        class="ml-3 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="syncSelectedPages"><i class="fa fa-download mr-1"></i> Import Selected</span>
                    <span wire:loading wire:target="syncSelectedPages"><i class="fa fa-spinner fa-spin mr-1"></i> Importing...</span>
                </button>
            </div>
        </div>

        <div wire:loading.flex wire:target="fetchPages" class="p-8 justify-center">
            <div class="text-center text-gray-500"><i class="fa fa-spinner fa-spin text-2xl mb-2"></i><p>Fetching pages...</p></div>
        </div>

        @if(count($wpPages))
        <table class="min-w-full divide-y divide-gray-200" wire:loading.remove wire:target="fetchPages">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($wpPages as $page)
                <tr class="hover:bg-gray-50 {{ in_array($page['id'], $selectedPages) ? 'bg-blue-50' : '' }}">
                    <td class="px-4 py-3">
                        <input type="checkbox"
                               {{ in_array($page['id'], $selectedPages) ? 'checked' : '' }}
                               wire:click="togglePage({{ $page['id'] }})"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{!! $page['title']['rendered'] ?? '' !!}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($page['date'])->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $page['status'] === 'publish' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($page['status']) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $page['slug'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @elseif(!$isSyncing)
        <div class="p-8 text-center text-gray-500" wire:loading.remove wire:target="fetchPages">
            <p>No pages found or still loading.</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Sync Log -->
    @if(count($syncLog) > 0)
    <div class="mt-4 bg-gray-900 rounded-lg shadow p-4 max-h-64 overflow-y-auto">
        <h3 class="text-sm font-semibold text-gray-400 mb-2"><i class="fa fa-terminal mr-1"></i> Sync Log</h3>
        @foreach($syncLog as $log)
            <div class="text-sm font-mono {{ match($log['type']) {
                'success' => 'text-green-400',
                'error' => 'text-red-400',
                'warning' => 'text-yellow-400',
                'skip' => 'text-gray-500',
                default => 'text-blue-300',
            } }}">
                <span class="text-gray-600">[{{ $log['time'] }}]</span> {{ $log['message'] }}
            </div>
        @endforeach
    </div>
    @endif
</div>

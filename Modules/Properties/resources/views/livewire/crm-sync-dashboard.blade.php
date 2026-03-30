<div class="px-4 sm:px-0">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fa fa-sync-alt mr-2 text-indigo-600"></i>CRM Property Sync</h1>
        <p class="mt-1 text-sm text-gray-600">Sync properties from CRM (crm.kretaeiendom.com)</p>
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
                    class="pb-3 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'settings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fa fa-cog mr-1"></i> Settings
            </button>
            <button wire:click="fetchCounts"
                    class="pb-3 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'sync' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                    @if(!$crmUrl || !$crmToken) disabled title="Configure CRM API first" @endif>
                <i class="fa fa-cloud-download-alt mr-1"></i> Sync
            </button>
        </nav>
    </div>

    <!-- Settings Tab -->
    @if($activeTab === 'settings')
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">CRM API Connection</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CRM API URL *</label>
                <input type="url" wire:model="crmUrl" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://crm.kretaeiendom.com">
                @error('crmUrl') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">API Token *</label>
                <input type="password" wire:model="crmToken" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Bearer token">
                <p class="text-xs text-gray-500 mt-1">The API bearer token for authentication</p>
                @error('crmToken') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button wire:click="saveSettings" wire:loading.attr="disabled" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveSettings"><i class="fa fa-save mr-1"></i> Save & Test</span>
                    <span wire:loading wire:target="saveSettings"><i class="fa fa-spinner fa-spin mr-1"></i> Testing...</span>
                </button>

                @if($connectionStatus)
                    @if($connectionStatus['success'])
                        <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm">
                            <i class="fa fa-check-circle mr-1"></i> Connected
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 bg-red-50 text-red-700 rounded-full text-sm">
                            <i class="fa fa-times-circle mr-1"></i> {{ $connectionStatus['error'] ?? 'Connection failed' }}
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Info -->
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2"><i class="fa fa-info-circle mr-1"></i> Automatic Sync</h3>
            <p class="text-sm text-gray-600">Properties are automatically synced every hour via cron. You can also trigger a manual sync from the Sync tab.</p>
            <p class="text-sm text-gray-600 mt-1">CLI: <code class="bg-gray-200 px-1.5 py-0.5 rounded text-xs">php artisan properties:sync --type=all</code></p>
            @if($lastSyncAt)
                <p class="text-sm text-gray-500 mt-2"><i class="fa fa-clock mr-1"></i> Last sync: {{ $lastSyncAt }}</p>
            @endif
        </div>
    </div>
    @endif

    <!-- Sync Tab -->
    @if($activeTab === 'sync')
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Sync Properties</h2>

        <!-- Counts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Sales in CRM</p>
                        <p class="text-2xl font-bold text-blue-800">{{ $salesCount }}</p>
                    </div>
                    <i class="fa fa-home text-3xl text-blue-200"></i>
                </div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-600 font-medium">Rentals in CRM</p>
                        <p class="text-2xl font-bold text-green-800">{{ $rentalsCount }}</p>
                    </div>
                    <i class="fa fa-key text-3xl text-green-200"></i>
                </div>
            </div>
        </div>

        @if($lastSyncAt)
            <p class="text-sm text-gray-500 mb-4"><i class="fa fa-clock mr-1"></i> Last sync: {{ $lastSyncAt }}</p>
        @endif

        <!-- Sync Buttons -->
        <div class="flex flex-wrap gap-3">
            <button wire:click="syncSales" wire:loading.attr="disabled" wire:target="syncSales,syncRentals,syncAll"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                <span wire:loading.remove wire:target="syncSales"><i class="fa fa-download mr-1"></i> Sync Sales</span>
                <span wire:loading wire:target="syncSales"><i class="fa fa-spinner fa-spin mr-1"></i> Syncing Sales...</span>
            </button>

            <button wire:click="syncRentals" wire:loading.attr="disabled" wire:target="syncSales,syncRentals,syncAll"
                    class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                <span wire:loading.remove wire:target="syncRentals"><i class="fa fa-download mr-1"></i> Sync Rentals</span>
                <span wire:loading wire:target="syncRentals"><i class="fa fa-spinner fa-spin mr-1"></i> Syncing Rentals...</span>
            </button>

            <button wire:click="syncAll" wire:loading.attr="disabled" wire:target="syncSales,syncRentals,syncAll"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                <span wire:loading.remove wire:target="syncAll"><i class="fa fa-sync-alt mr-1"></i> Sync All</span>
                <span wire:loading wire:target="syncAll"><i class="fa fa-spinner fa-spin mr-1"></i> Syncing All...</span>
            </button>
        </div>
    </div>
    @endif

    <!-- Sync Log -->
    @if(count($syncLog) > 0)
    <div class="mt-4 bg-gray-900 rounded-lg shadow p-4 max-h-80 overflow-y-auto">
        <h3 class="text-sm font-semibold text-gray-400 mb-2"><i class="fa fa-terminal mr-1"></i> Sync Log</h3>
        @foreach($syncLog as $log)
            <div class="text-sm font-mono {{ match($log['type']) {
                'success' => 'text-green-400',
                'error' => 'text-red-400',
                'warning' => 'text-yellow-400',
                default => 'text-blue-300',
            } }}">
                <span class="text-gray-600">[{{ $log['time'] }}]</span> {{ $log['message'] }}
            </div>
        @endforeach
    </div>
    @endif
</div>

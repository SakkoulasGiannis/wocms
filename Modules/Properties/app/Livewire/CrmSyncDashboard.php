<?php

namespace Modules\Properties\Livewire;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Modules\Properties\Services\CrmApiClient;

class CrmSyncDashboard extends Component
{
    public string $crmUrl = '';

    public string $crmToken = '';

    public ?array $connectionStatus = null;

    public string $activeTab = 'settings';

    public bool $isSyncing = false;

    public array $syncLog = [];

    public ?string $lastSyncAt = null;

    public int $salesCount = 0;

    public int $rentalsCount = 0;

    public function mount(): void
    {
        $this->crmUrl = Setting::get('crm_api_url', '');
        $this->crmToken = Setting::get('crm_api_token', '');
        $this->lastSyncAt = Setting::get('crm_last_sync_at', null);

        if ($this->crmUrl && $this->crmToken) {
            $this->connectionStatus = $this->getClient()->testConnection();
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'crmUrl' => 'required|url',
            'crmToken' => 'required|string|min:10',
        ]);

        Setting::set('crm_api_url', $this->crmUrl, 'integrations');
        Setting::set('crm_api_token', $this->crmToken, 'integrations', true);

        $this->connectionStatus = $this->getClient()->testConnection();

        if ($this->connectionStatus['success']) {
            session()->flash('success', 'Connected to CRM API successfully!');
        } else {
            session()->flash('error', 'Connection failed: '.($this->connectionStatus['error'] ?? 'Unknown error'));
        }
    }

    public function testConnection(): void
    {
        $this->connectionStatus = $this->getClient()->testConnection();
    }

    public function syncSales(): void
    {
        $this->doSync('sales');
    }

    public function syncRentals(): void
    {
        $this->doSync('rentals');
    }

    public function syncAll(): void
    {
        $this->doSync('all');
    }

    protected function doSync(string $type): void
    {
        $this->activeTab = 'sync';
        $this->isSyncing = true;
        $this->syncLog = [];

        $client = $this->getClient();
        if (! $client->isConfigured()) {
            $this->addLog('error', 'CRM API is not configured. Save settings first.');
            $this->isSyncing = false;

            return;
        }

        try {
            $this->addLog('info', 'Starting sync ('.$type.')...');

            Artisan::call('properties:sync', ['--type' => $type]);
            $output = Artisan::output();

            foreach (explode("\n", trim($output)) as $line) {
                if (! empty(trim($line))) {
                    $this->addLog('info', $line);
                }
            }

            Setting::set('crm_last_sync_at', now()->toDateTimeString(), 'integrations');
            $this->lastSyncAt = now()->toDateTimeString();

            $this->addLog('success', 'Sync completed successfully!');
        } catch (\Exception $e) {
            $this->addLog('error', 'Sync failed: '.$e->getMessage());
        }

        $this->isSyncing = false;
    }

    public function fetchCounts(): void
    {
        $this->activeTab = 'sync';
        $client = $this->getClient();

        $sales = $client->getSales(1, 1);
        $this->salesCount = $sales['success'] ? ($sales['total'] ?? 0) : 0;

        $rentals = $client->getRentals(1, 1);
        $this->rentalsCount = $rentals['success'] ? ($rentals['total'] ?? 0) : 0;
    }

    protected function addLog(string $type, string $message): void
    {
        $this->syncLog[] = [
            'type' => $type,
            'message' => $message,
            'time' => now()->format('H:i:s'),
        ];
    }

    protected function getClient(): CrmApiClient
    {
        return new CrmApiClient;
    }

    public function render()
    {
        return view('properties::livewire.crm-sync-dashboard')
            ->layout('layouts.admin-clean')
            ->title('CRM Property Sync');
    }
}

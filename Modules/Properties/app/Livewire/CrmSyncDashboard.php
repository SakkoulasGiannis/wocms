<?php

namespace Modules\Properties\Livewire;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Modules\Properties\Services\CrmApiClient;
use Modules\RentalProperties\Services\HostawayClient;

class CrmSyncDashboard extends Component
{
    public string $crmUrl = '';

    public string $crmToken = '';

    public string $hostawayAccountId = '';

    public string $hostawayApiKey = '';

    public string $hostawayWebhookToken = '';

    public bool $rentalBookingEnabled = false;

    public ?array $hostawayStatus = null;

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
        $this->hostawayAccountId = Setting::get('hostaway_account_id', '');
        $this->hostawayApiKey = Setting::get('hostaway_api_key', '');
        $this->hostawayWebhookToken = Setting::get('hostaway_webhook_token', '');
        $this->rentalBookingEnabled = filter_var(Setting::get('rental_booking_enabled', false), FILTER_VALIDATE_BOOLEAN);

        if ($this->crmUrl && $this->crmToken) {
            $this->connectionStatus = $this->getClient()->testConnection();
        }
    }

    public function saveHostawaySettings(): void
    {
        $this->validate([
            'hostawayAccountId' => 'required|string',
            'hostawayApiKey' => 'required|string|min:10',
        ]);

        Setting::set('hostaway_account_id', $this->hostawayAccountId, 'integrations');
        Setting::set('hostaway_api_key', $this->hostawayApiKey, 'integrations', true);
        Setting::set('hostaway_webhook_token', $this->hostawayWebhookToken, 'integrations', true);
        Setting::set('rental_booking_enabled', $this->rentalBookingEnabled ? '1' : '0', 'integrations');

        // New credentials → drop any cached access token so the next call re-mints.
        \Illuminate\Support\Facades\Cache::forget('hostaway_access_token');

        $this->hostawayStatus = (new HostawayClient)->testConnection();

        if ($this->hostawayStatus['success']) {
            session()->flash('success', $this->hostawayStatus['message']);
        } else {
            session()->flash('error', $this->hostawayStatus['message']);
        }
    }

    public function testHostaway(): void
    {
        $this->hostawayStatus = (new HostawayClient)->testConnection();
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

<?php

namespace Modules\Properties\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmApiClient
{
    protected string $baseUrl;

    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(Setting::get('crm_api_url', ''), '/');
        $this->token = Setting::get('crm_api_token', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->token);
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(30)
            ->withToken($this->token)
            ->withHeaders(['Accept' => 'application/json']);
    }

    /**
     * Test connection to CRM API
     */
    public function testConnection(): array
    {
        try {
            $response = $this->request()->get($this->baseUrl.'/api/properties/sales', [
                'per_page' => 1,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'error' => 'HTTP '.$response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch sale listings from CRM
     */
    public function getSales(int $page = 1, int $perPage = 100): array
    {
        return $this->fetchProperties('/api/properties/sales', $page, $perPage);
    }

    /**
     * Fetch rental listings from CRM
     */
    public function getRentals(int $page = 1, int $perPage = 100): array
    {
        return $this->fetchProperties('/api/properties/rentals', $page, $perPage);
    }

    /**
     * Fetch all pages of a given endpoint
     */
    public function getAllSales(): array
    {
        return $this->fetchAllPages('/api/properties/sales');
    }

    /**
     * Fetch all pages of rentals
     */
    public function getAllRentals(): array
    {
        return $this->fetchAllPages('/api/properties/rentals');
    }

    /**
     * Download media file from URL
     */
    public function downloadMedia(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
            if ($response->successful()) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'crm_media_');
                file_put_contents($tmpFile, $response->body());

                return $tmpFile;
            }
        } catch (\Exception $e) {
            Log::warning('CRM Sync: Failed to download media: '.$url, ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function fetchProperties(string $endpoint, int $page = 1, int $perPage = 100): array
    {
        try {
            $response = $this->request()->get($this->baseUrl.$endpoint, [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if ($response->successful()) {
                $json = $response->json();

                return [
                    'success' => true,
                    'data' => $json['data'] ?? $json,
                    'current_page' => $json['current_page'] ?? $page,
                    'last_page' => $json['last_page'] ?? 1,
                    'total' => $json['total'] ?? count($json['data'] ?? $json),
                ];
            }

            return ['success' => false, 'error' => 'HTTP '.$response->status(), 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    protected function fetchAllPages(string $endpoint): array
    {
        $allItems = [];
        $page = 1;

        do {
            $result = $this->fetchProperties($endpoint, $page, 100);

            if (! $result['success']) {
                return $result;
            }

            $allItems = array_merge($allItems, $result['data']);
            $lastPage = $result['last_page'];
            $page++;
        } while ($page <= $lastPage);

        return ['success' => true, 'data' => $allItems, 'total' => count($allItems)];
    }
}

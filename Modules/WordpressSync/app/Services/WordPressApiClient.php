<?php

namespace Modules\WordpressSync\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressApiClient
{
    protected string $baseUrl;

    protected string $username;

    protected string $appPassword;

    public function __construct()
    {
        $this->baseUrl = rtrim(Setting::get('wp_sync_url', ''), '/');
        $this->username = Setting::get('wp_sync_username', '');
        $this->appPassword = Setting::get('wp_sync_app_password', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl);
    }

    protected function apiUrl(string $endpoint): string
    {
        return $this->baseUrl.'/wp-json/wp/v2/'.ltrim($endpoint, '/');
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(30)->withHeaders([
            'Accept' => 'application/json',
        ]);

        // Add auth if credentials exist (needed for drafts, private content)
        if ($this->username && $this->appPassword) {
            $client = $client->withBasicAuth($this->username, $this->appPassword);
        }

        return $client;
    }

    /**
     * Test connection to WordPress site
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl.'/wp-json');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'name' => $data['name'] ?? 'Unknown',
                    'description' => $data['description'] ?? '',
                    'url' => $data['url'] ?? $this->baseUrl,
                ];
            }

            return ['success' => false, 'error' => 'HTTP '.$response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch posts from WordPress
     */
    public function getPosts(int $page = 1, int $perPage = 20, string $status = 'publish'): array
    {
        try {
            $response = $this->request()->get($this->apiUrl('posts'), [
                'page' => $page,
                'per_page' => $perPage,
                'status' => $status,
                '_embed' => 'wp:featuredmedia,wp:term',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'total' => (int) $response->header('X-WP-Total', 0),
                    'totalPages' => (int) $response->header('X-WP-TotalPages', 0),
                ];
            }

            return ['success' => false, 'error' => 'HTTP '.$response->status(), 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Fetch pages from WordPress
     */
    public function getPages(int $page = 1, int $perPage = 20, string $status = 'publish'): array
    {
        try {
            $response = $this->request()->get($this->apiUrl('pages'), [
                'page' => $page,
                'per_page' => $perPage,
                'status' => $status,
                '_embed' => 'wp:featuredmedia',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'total' => (int) $response->header('X-WP-Total', 0),
                    'totalPages' => (int) $response->header('X-WP-TotalPages', 0),
                ];
            }

            return ['success' => false, 'error' => 'HTTP '.$response->status(), 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Fetch categories from WordPress
     */
    public function getCategories(int $perPage = 100): array
    {
        try {
            $response = $this->request()->get($this->apiUrl('categories'), [
                'per_page' => $perPage,
            ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'HTTP '.$response->status(), 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Fetch tags from WordPress
     */
    public function getTags(int $perPage = 100): array
    {
        try {
            $response = $this->request()->get($this->apiUrl('tags'), [
                'per_page' => $perPage,
            ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'HTTP '.$response->status(), 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Download media file from URL
     */
    public function downloadMedia(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
            if ($response->successful()) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'wpmedia_');
                file_put_contents($tmpFile, $response->body());

                return $tmpFile;
            }
        } catch (\Exception $e) {
            Log::warning('WP Sync: Failed to download media: '.$url, ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Extract featured image URL from embedded post data
     */
    public function getFeaturedImageUrl(array $post): ?string
    {
        return $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
    }

    /**
     * Extract tag names from embedded post data
     */
    public function getTagNames(array $post): array
    {
        $terms = $post['_embedded']['wp:term'] ?? [];
        $tags = [];
        foreach ($terms as $termGroup) {
            foreach ($termGroup as $term) {
                if (($term['taxonomy'] ?? '') === 'post_tag') {
                    $tags[] = $term['name'];
                }
            }
        }

        return $tags;
    }

    /**
     * Extract category names from embedded post data
     */
    public function getCategoryNames(array $post): array
    {
        $terms = $post['_embedded']['wp:term'] ?? [];
        $categories = [];
        foreach ($terms as $termGroup) {
            foreach ($termGroup as $term) {
                if (($term['taxonomy'] ?? '') === 'category') {
                    $categories[] = $term['name'];
                }
            }
        }

        return $categories;
    }
}

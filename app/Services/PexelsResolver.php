<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolves a single real stock photo from Pexels for a keyword. Used by the
 * visual builder's AI generator to replace placehold.co placeholders with real
 * imagery. The API key lives in the CMS settings (Integrations → Pexels API
 * Key, stored encrypted). Every returned URL is validated to the Pexels CDN
 * host, and any failure (no key, HTTP error, malformed body, zero results)
 * degrades gracefully to null so image generation never breaks.
 */
class PexelsResolver
{
    private const ALLOWED_HOST = 'images.pexels.com';

    private const TIMEOUT = 8;

    private const CACHE_TTL = 86400;

    public function enabled(): bool
    {
        return $this->key() !== '';
    }

    private function key(): string
    {
        return trim((string) Setting::get('pexels_api_key', ''));
    }

    /**
     * Resolve one Pexels photo for a keyword.
     *
     * @return array{url:string, photographer:string, alt:string}|null
     */
    public function resolve(string $keyword, string $orientation = 'landscape'): ?array
    {
        $query = trim((string) preg_replace('/\s+/', ' ', $keyword));
        if ($query === '' || ! $this->enabled()) {
            return null;
        }

        $query = mb_substr($query, 0, 100);
        $orientation = in_array($orientation, ['landscape', 'portrait', 'square'], true) ? $orientation : 'landscape';
        $cacheKey = 'pexels:'.md5($orientation.'|'.mb_strtolower($query));

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, fn (): ?array => $this->fetch($query, $orientation));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{url:string, photographer:string, alt:string}|null
     */
    private function fetch(string $query, string $orientation): ?array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $this->key()])
                ->timeout(self::TIMEOUT)
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $query,
                    'per_page' => 5,
                    'orientation' => $orientation,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $photos = $response->json('photos');
        if (! is_array($photos)) {
            return null;
        }

        foreach ($photos as $photo) {
            if (! is_array($photo)) {
                continue;
            }
            $src = is_array($photo['src'] ?? null) ? $photo['src'] : [];
            foreach (['large2x', 'large', 'original', 'medium'] as $size) {
                $url = $src[$size] ?? null;
                if (is_string($url) && $this->isAllowed($url)) {
                    return [
                        'url' => $url,
                        'photographer' => (string) ($photo['photographer'] ?? ''),
                        'alt' => trim((string) ($photo['alt'] ?? '')) ?: $query,
                    ];
                }
            }
        }

        return null;
    }

    private function isAllowed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === 'https' && is_string($host) && strtolower($host) === self::ALLOWED_HOST;
    }
}

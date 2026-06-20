<?php

namespace Modules\RentalProperties\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the Hostaway public API (https://api.hostaway.com/v1).
 *
 * Credentials (Account ID + API Key) are stored in Settings and entered by an
 * admin — never hard-coded. The OAuth access token (long-lived) and calendar
 * responses are cached so the frontend can fetch availability cheaply without
 * hammering Hostaway's rate limit.
 */
class HostawayClient
{
    private const BASE_URL = 'https://api.hostaway.com/v1';

    private const TOKEN_CACHE_KEY = 'hostaway_access_token';

    private string $accountId;

    private string $apiKey;

    public function __construct()
    {
        // Admin-entered Settings take precedence; fall back to env-backed config.
        $this->accountId = (string) (Setting::get('hostaway_account_id', '') ?: config('hostaway.account_id', ''));
        $this->apiKey = (string) (Setting::get('hostaway_api_key', '') ?: config('hostaway.api_key', ''));
    }

    public function isConfigured(): bool
    {
        return $this->accountId !== '' && $this->apiKey !== '';
    }

    /**
     * Shared secret for verifying inbound Hostaway webhooks.
     */
    public function webhookToken(): string
    {
        return (string) (Setting::get('hostaway_webhook_token', '') ?: config('hostaway.webhook_token', ''));
    }

    /**
     * Invalidate every cached calendar range for a listing by bumping its
     * cache version (works on cache drivers without tag support).
     */
    public function bustCalendar(string $listingId): void
    {
        $verKey = "hostaway_cal_ver:{$listingId}";
        Cache::forever($verKey, (int) Cache::get($verKey, 0) + 1);
    }

    private function calendarVersion(string $listingId): int
    {
        return (int) Cache::get("hostaway_cal_ver:{$listingId}", 0);
    }

    /**
     * Obtain (and cache) a Hostaway Bearer access token. Hostaway tokens live
     * ~24 months; we cache for 30 days and re-mint on demand. Returns null when
     * not configured or the auth call fails (never caches a failure).
     */
    public function accessToken(bool $forceRefresh = false): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        if (! $forceRefresh) {
            $cached = Cache::get(self::TOKEN_CACHE_KEY);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        try {
            $res = Http::asForm()->acceptJson()->timeout(20)->post(self::BASE_URL.'/accessTokens', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->accountId,
                'client_secret' => $this->apiKey,
                'scope' => 'general',
            ]);

            if (! $res->successful()) {
                Log::warning('Hostaway token request failed: '.$res->status().' '.substr($res->body(), 0, 300));

                return null;
            }

            $token = (string) $res->json('access_token');
            if ($token === '') {
                return null;
            }

            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addDays(30));

            return $token;
        } catch (\Throwable $e) {
            Log::warning('Hostaway token request exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Fetch the availability calendar for a listing between two dates.
     * Re-mints the token once on a 401. Successful responses cache for 20 min.
     *
     * @return array{success: bool, error?: string, days: array<int, array<string, mixed>>}
     */
    public function getCalendar(string $listingId, string $start, string $end): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => 'not_configured', 'days' => []];
        }

        $cacheKey = "hostaway_cal:{$listingId}:v{$this->calendarVersion($listingId)}:{$start}:{$end}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->requestCalendar($listingId, $start, $end);

        if ($result['success']) {
            Cache::put($cacheKey, $result, now()->addMinutes(20));
        }

        return $result;
    }

    /**
     * @return array{success: bool, error?: string, days: array<int, array<string, mixed>>}
     */
    private function requestCalendar(string $listingId, string $start, string $end, bool $retried = false): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'auth_failed', 'days' => []];
        }

        try {
            $res = Http::withToken($token)->acceptJson()->timeout(20)
                ->get(self::BASE_URL."/listings/{$listingId}/calendar", [
                    'startDate' => $start,
                    'endDate' => $end,
                ]);

            if ($res->status() === 401 && ! $retried) {
                $this->accessToken(true); // re-mint and retry once

                return $this->requestCalendar($listingId, $start, $end, true);
            }

            if (! $res->successful()) {
                Log::warning("Hostaway calendar failed for {$listingId}: ".$res->status().' '.substr($res->body(), 0, 300));

                return ['success' => false, 'error' => 'http_'.$res->status(), 'days' => []];
            }

            // Direct bookings go through Hostaway's "booking engine" channel, which
            // has its own markup multiplier on the listing (e.g. 0.95 = 5% off the
            // base calendar rate). Apply it to nightly prices so what we show and
            // quote matches what Hostaway expects for a direct reservation.
            $markup = $this->bookingEngineMarkup($listingId);

            $rows = $res->json('result') ?? [];
            $days = array_values(array_map(static function (array $d) use ($markup): array {
                $price = isset($d['price']) ? round((float) $d['price'] * $markup, 2) : null;

                return [
                    'date' => $d['date'] ?? null,
                    'isAvailable' => (int) ($d['isAvailable'] ?? 0) === 1,
                    'status' => $d['status'] ?? null,
                    'price' => $price,
                    'minimumStay' => isset($d['minimumStay']) ? (int) $d['minimumStay'] : null,
                ];
            }, is_array($rows) ? $rows : []));

            return ['success' => true, 'days' => $days];
        } catch (\Throwable $e) {
            Log::warning("Hostaway calendar exception for {$listingId}: ".$e->getMessage());

            return ['success' => false, 'error' => 'exception', 'days' => []];
        }
    }

    /**
     * Fetch a listing's settings (capacity, fees, min/max nights, currency).
     * Cached 6h. Returns the raw Hostaway `result` array or null.
     *
     * @return array<string, mixed>|null
     */
    public function getListing(string $listingId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = "hostaway_listing:{$listingId}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        try {
            $res = Http::withToken($token)->acceptJson()->timeout(20)->get(self::BASE_URL."/listings/{$listingId}");
            if ($res->status() === 401) {
                $token = $this->accessToken(true);
                $res = Http::withToken($token)->acceptJson()->timeout(20)->get(self::BASE_URL."/listings/{$listingId}");
            }
            if (! $res->successful()) {
                return null;
            }
            $result = $res->json('result');
            if (! is_array($result)) {
                return null;
            }
            Cache::put($cacheKey, $result, now()->addHours(6));

            return $result;
        } catch (\Throwable $e) {
            Log::warning("Hostaway listing fetch failed for {$listingId}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * The listing's direct/booking-engine price multiplier (default 1.0 = none).
     * e.g. 0.95 means direct-booking nightly prices are 5% below the base rate.
     */
    public function bookingEngineMarkup(string $listingId): float
    {
        $listing = $this->getListing($listingId);
        $markup = (float) ($listing['bookingEngineMarkup'] ?? 1);

        return $markup > 0 ? $markup : 1.0;
    }

    /**
     * Create a reservation in Hostaway. Used by the Request-to-Book flow.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, error?: string, message?: string, reservation?: array<string, mixed>}
     */
    public function createReservation(array $payload): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'auth_failed', 'message' => 'Could not authenticate with Hostaway.'];
        }

        try {
            $res = Http::withToken($token)->acceptJson()->timeout(30)
                ->post(self::BASE_URL.'/reservations?forceOverbooking=0', $payload);

            $body = $res->json();
            if (! $res->successful() || (($body['status'] ?? null) === 'fail')) {
                Log::warning('Hostaway reservation failed: '.$res->status().' '.substr($res->body(), 0, 500));

                return [
                    'success' => false,
                    'error' => 'http_'.$res->status(),
                    'message' => $body['message'] ?? 'Reservation could not be created.',
                ];
            }

            return ['success' => true, 'reservation' => $body['result'] ?? []];
        } catch (\Throwable $e) {
            Log::warning('Hostaway reservation exception: '.$e->getMessage());

            return ['success' => false, 'error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Test connectivity: mint a token and confirm it works.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Hostaway Account ID and API Key are required.'];
        }

        $token = $this->accessToken(true);

        return $token
            ? ['success' => true, 'message' => 'Connected to Hostaway successfully.']
            : ['success' => false, 'message' => 'Could not authenticate with Hostaway. Check the Account ID and API Key.'];
    }
}

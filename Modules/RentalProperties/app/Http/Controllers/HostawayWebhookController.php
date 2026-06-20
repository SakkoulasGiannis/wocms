<?php

namespace Modules\RentalProperties\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\RentalProperties\Services\HostawayClient;

/**
 * Receives Hostaway webhooks (calendar / reservation changes) and invalidates
 * the cached availability for the affected listing so the frontend calendar
 * reflects updates promptly. Foundation for the upcoming booking flow.
 */
class HostawayWebhookController extends Controller
{
    public function __invoke(Request $request, HostawayClient $hostaway): JsonResponse
    {
        $expected = $hostaway->webhookToken();

        if ($expected === '') {
            return response()->json(['success' => false, 'error' => 'webhook_not_configured'], 503);
        }

        if (! hash_equals($expected, $this->presentedToken($request))) {
            return response()->json(['success' => false, 'error' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        $listingId = $this->extractListingId($payload);

        if ($listingId !== null) {
            $hostaway->bustCalendar($listingId);
        }

        Log::info('Hostaway webhook received', [
            'event' => $payload['event'] ?? ($payload['object'] ?? 'unknown'),
            'listingId' => $listingId,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Token may arrive as ?token=, an X-Hostaway-Token header, or a Bearer token.
     */
    private function presentedToken(Request $request): string
    {
        return (string) ($request->query('token')
            ?: $request->header('X-Hostaway-Token')
            ?: $request->bearerToken()
            ?: '');
    }

    /**
     * Best-effort extraction of the Hostaway listing id from a webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function extractListingId(array $payload): ?string
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        foreach (['listingMapId', 'listingId', 'id'] as $key) {
            if (! empty($data[$key])) {
                return (string) $data[$key];
            }
        }

        return null;
    }
}

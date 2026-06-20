<?php

namespace Modules\RentalProperties\Payments\Providers;

use Illuminate\Http\Request;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Payments\Contracts\PaymentProvider;
use Modules\RentalProperties\Payments\PaymentResult;

/**
 * Viva.com (Smart Checkout) — SKELETON, not implemented yet.
 *
 * TODO when implementing:
 *  - OAuth2 token from accounts(.demo).vivapayments.com using client_id/secret
 *    (config('payments.providers.viva')).
 *  - startCheckout(): create a payment order
 *    (POST /checkout/v2/orders) with amount = $booking->amount_due (in cents),
 *    customer + merchantTrns/customerTrns = $booking->uuid, and redirect the
 *    guest to https://www.vivapayments.com/web/checkout?ref={orderCode}.
 *  - handleCallback(): verify the transaction via the Transactions API and the
 *    Viva webhook (verification key), then return PaymentResult::paid().
 */
class VivaProvider implements PaymentProvider
{
    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return (array) config('payments.providers.viva', []);
    }

    public function key(): string
    {
        return 'viva';
    }

    public function label(): string
    {
        return 'Viva.com';
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['client_id']) && ! empty($c['client_secret']);
    }

    public function startCheckout(Booking $booking): PaymentResult
    {
        return PaymentResult::failed('Viva.com payments are not enabled yet.');
    }

    public function handleCallback(Request $request, Booking $booking): PaymentResult
    {
        return PaymentResult::failed('Viva.com payments are not enabled yet.');
    }
}

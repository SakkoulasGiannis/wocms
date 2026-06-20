<?php

namespace Modules\RentalProperties\Payments\Providers;

use Illuminate\Http\Request;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Payments\Contracts\PaymentProvider;
use Modules\RentalProperties\Payments\PaymentResult;

/**
 * PayPal (Orders v2) — SKELETON, not implemented yet.
 *
 * TODO when implementing:
 *  - OAuth2 token from api-m(.sandbox).paypal.com using client_id/secret.
 *  - startCheckout(): POST /v2/checkout/orders (intent CAPTURE, amount =
 *    $booking->amount_due, custom_id = $booking->uuid), redirect to the
 *    returned approve link.
 *  - handleCallback(): capture the order (POST /v2/checkout/orders/{id}/capture)
 *    and verify the webhook signature, then PaymentResult::paid().
 */
class PayPalProvider implements PaymentProvider
{
    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return (array) config('payments.providers.paypal', []);
    }

    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['client_id']) && ! empty($c['client_secret']);
    }

    public function startCheckout(Booking $booking): PaymentResult
    {
        return PaymentResult::failed('PayPal payments are not enabled yet.');
    }

    public function handleCallback(Request $request, Booking $booking): PaymentResult
    {
        return PaymentResult::failed('PayPal payments are not enabled yet.');
    }
}

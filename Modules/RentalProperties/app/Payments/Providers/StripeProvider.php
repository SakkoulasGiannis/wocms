<?php

namespace Modules\RentalProperties\Payments\Providers;

use Illuminate\Http\Request;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Payments\Contracts\PaymentProvider;
use Modules\RentalProperties\Payments\PaymentResult;

/**
 * Stripe (Checkout / Payment Intents) — SKELETON, not implemented yet.
 *
 * TODO when implementing:
 *  - startCheckout(): create a Checkout Session (or PaymentIntent for embedded
 *    Elements) with amount = $booking->amount_due, metadata.booking = uuid,
 *    success_url/cancel_url → the booking return route. Redirect to the session
 *    URL (card data goes straight to Stripe — never our server).
 *  - handleCallback(): verify the `checkout.session.completed` /
 *    `payment_intent.succeeded` webhook with webhook_secret, then
 *    PaymentResult::paid().
 */
class StripeProvider implements PaymentProvider
{
    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return (array) config('payments.providers.stripe', []);
    }

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        $c = $this->config();

        return ! empty($c['secret_key']) && ! empty($c['publishable_key']);
    }

    public function startCheckout(Booking $booking): PaymentResult
    {
        return PaymentResult::failed('Stripe payments are not enabled yet.');
    }

    public function handleCallback(Request $request, Booking $booking): PaymentResult
    {
        return PaymentResult::failed('Stripe payments are not enabled yet.');
    }
}

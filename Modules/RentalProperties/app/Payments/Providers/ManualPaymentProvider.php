<?php

namespace Modules\RentalProperties\Payments\Providers;

use Illuminate\Http\Request;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Payments\Contracts\PaymentProvider;
use Modules\RentalProperties\Payments\PaymentResult;

/**
 * No online charge: the booking is reserved as pending and staff follow up with
 * a payment link / arrangement. This is the default until a real gateway (Viva,
 * PayPal, Stripe) is implemented.
 */
class ManualPaymentProvider implements PaymentProvider
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Manual / pay later';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function startCheckout(Booking $booking): PaymentResult
    {
        return PaymentResult::pending('Your booking is reserved. Our team will email you a secure link to complete payment.');
    }

    public function handleCallback(Request $request, Booking $booking): PaymentResult
    {
        return PaymentResult::pending();
    }
}

<?php

namespace Modules\RentalProperties\Payments\Contracts;

use Illuminate\Http\Request;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Payments\PaymentResult;

/**
 * A pluggable payment gateway (Viva, PayPal, Stripe, …). Implementations turn a
 * pending Booking into a payment and report the result back. Card data must go
 * straight to the provider — never through our server (PCI).
 */
interface PaymentProvider
{
    public function key(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * Begin payment for a booking — typically returns a redirect URL to the
     * provider's hosted checkout, or a pending result for manual handling.
     */
    public function startCheckout(Booking $booking): PaymentResult;

    /**
     * Handle the provider's return redirect or webhook and report whether the
     * booking was paid.
     */
    public function handleCallback(Request $request, Booking $booking): PaymentResult;
}

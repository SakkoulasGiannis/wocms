<?php

namespace Modules\RentalProperties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\RentalProperties\Models\Booking;
use Modules\RentalProperties\Models\RentalProperty;
use Modules\RentalProperties\Payments\PaymentManager;
use Modules\RentalProperties\Payments\PaymentResult;
use Modules\RentalProperties\Services\BookingService;
use Modules\RentalProperties\Services\HostawayClient;

/**
 * Provider-agnostic booking checkout. A pending Booking is created, then handed
 * to the active payment provider (manual stub for now; Viva/PayPal/Stripe later).
 * On a successful payment the confirmed Hostaway reservation is created.
 */
class BookingCheckoutController extends Controller
{
    public function __construct(
        private BookingService $booking,
        private PaymentManager $payments,
        private HostawayClient $hostaway,
    ) {}

    /**
     * Validate, persist a pending booking, and start the provider checkout.
     */
    public function start(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        if (! $this->bookingEnabled()) {
            return response()->json(['success' => false, 'message' => 'Online booking is temporarily unavailable. Please contact us.'], 403);
        }

        $property = $this->resolveRental($slug);
        if (empty($property->hostaway_id)) {
            return response()->json(['success' => false, 'message' => 'Online booking is not available for this property.'], 422);
        }

        $data = $request->validate([
            'checkin' => 'required|date',
            'checkout' => 'required|date',
            'adults' => 'nullable|integer|min:1|max:50',
            'children' => 'nullable|integer|min:0|max:50',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:40',
            'message' => 'nullable|string|max:1000',
        ]);

        // Re-quote server-side — never trust client-sent prices/availability.
        $quote = $this->booking->quote(
            $property,
            $data['checkin'],
            $data['checkout'],
            (int) ($data['adults'] ?? 1),
            (int) ($data['children'] ?? 0),
        );

        if (empty($quote['ok'])) {
            return response()->json(['success' => false, 'message' => $quote['errors'][0] ?? 'These dates are no longer available.'], 422);
        }

        $booking = Booking::create([
            'rental_property_id' => $property->id,
            'hostaway_id' => (string) $property->hostaway_id,
            'checkin' => $quote['checkin'],
            'checkout' => $quote['checkout'],
            'nights' => $quote['nights'],
            'adults' => $quote['adults'],
            'children' => $quote['children'],
            'guest_name' => $data['name'],
            'guest_email' => $data['email'],
            'guest_phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'currency' => $quote['currency'],
            'accommodation' => $quote['accommodation'],
            'cleaning_fee' => $quote['cleaningFee'],
            'extra_guest_fee' => $quote['extraGuestFee'],
            'total' => $quote['total'],
            'amount_due' => $this->payments->amountDue((float) $quote['total']),
            'status' => Booking::STATUS_PENDING,
            'payment_provider' => $this->payments->activeKey(),
        ]);

        $result = $this->payments->provider()->startCheckout($booking);

        if (! $result->ok) {
            $booking->update(['status' => Booking::STATUS_FAILED, 'payment_status' => $result->status]);

            return response()->json(['success' => false, 'message' => $result->message ?? 'Payment could not be started.'], 502);
        }

        if ($result->reference) {
            $booking->update(['payment_ref' => $result->reference]);
        }

        // Redirect to the provider's hosted checkout, or to the booking page for
        // manual/pending handling.
        return response()->json([
            'success' => true,
            'redirect' => $result->redirectUrl ?: route('booking.show', $booking),
        ]);
    }

    /**
     * Booking summary / status page (also the manual "pending payment" landing).
     */
    public function show(Booking $booking): \Illuminate\View\View
    {
        return view('rentalproperties::booking.show', ['booking' => $booking]);
    }

    /**
     * Provider return redirect — verify payment, confirm on success.
     */
    public function paymentReturn(Request $request, Booking $booking): \Illuminate\Http\RedirectResponse
    {
        $provider = $this->payments->provider($booking->payment_provider);
        $result = $provider->handleCallback($request, $booking);

        if ($result->status === 'paid') {
            $this->markPaidAndReserve($booking, $result);
        }

        return redirect()->route('booking.show', $booking);
    }

    /**
     * Provider server-to-server webhook (no CSRF). Excluded in bootstrap/app.php.
     */
    public function webhook(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        $gateway = $this->payments->provider($provider);
        $ref = (string) ($request->input('reference') ?? $request->input('booking') ?? '');
        $booking = Booking::where('uuid', $ref)->orWhere('payment_ref', $ref)->first();

        if (! $booking) {
            return response()->json(['success' => false, 'error' => 'unknown_booking'], 404);
        }

        $result = $gateway->handleCallback($request, $booking);
        if ($result->status === 'paid') {
            $this->markPaidAndReserve($booking, $result);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark a booking paid and create the confirmed Hostaway reservation (once).
     */
    protected function markPaidAndReserve(Booking $booking, PaymentResult $result): void
    {
        if ($booking->isPaid()) {
            return; // idempotent — already handled
        }

        $booking->update([
            'status' => Booking::STATUS_PAID,
            'payment_status' => 'paid',
            'payment_ref' => $result->reference ?: $booking->payment_ref,
            'paid_at' => now(),
        ]);

        $payload = $this->booking->reservationPayloadFromBooking($booking, 'new');
        $reservation = $this->hostaway->createReservation($payload);

        if (! empty($reservation['success'])) {
            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'hostaway_reservation_id' => $reservation['reservation']['id'] ?? null,
            ]);
        } else {
            Log::warning('Booking '.$booking->uuid.' paid but Hostaway reservation failed: '.($reservation['message'] ?? '?'));
        }
    }

    protected function bookingEnabled(): bool
    {
        return filter_var(Setting::get('rental_booking_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function resolveRental(string $slug): RentalProperty
    {
        return RentalProperty::where('slug', $slug)
            ->active()
            ->orderByRaw('hostaway_id IS NULL')
            ->orderByDesc('external_id')
            ->firstOrFail();
    }
}

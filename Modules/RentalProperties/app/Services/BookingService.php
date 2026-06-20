<?php

namespace Modules\RentalProperties\Services;

use Illuminate\Support\Carbon;
use Modules\RentalProperties\Models\RentalProperty;

/**
 * Builds availability-validated price quotes and Hostaway reservation payloads
 * for the rental booking flow (Phase 1: quote + request-to-book).
 */
class BookingService
{
    public function __construct(private HostawayClient $hostaway) {}

    /**
     * Listing meta used to render the booking widget (capacity, min nights, fees).
     *
     * @return array<string, mixed>
     */
    public function listingInfo(RentalProperty $property): array
    {
        $listing = $this->hostaway->getListing((string) $property->hostaway_id) ?? [];

        return [
            'maxGuests' => (int) ($listing['personCapacity'] ?? 0),
            'minNights' => (int) ($listing['minNights'] ?? 1),
            'maxNights' => (int) ($listing['maxNights'] ?? 0),
            'cleaningFee' => (float) ($listing['cleaningFee'] ?? 0),
            'currency' => $listing['currencyCode'] ?? ($property->currency ?: 'EUR'),
        ];
    }

    /**
     * Validate a date range + guest count and compute the price.
     *
     * @return array<string, mixed>
     */
    public function quote(RentalProperty $property, string $checkin, string $checkout, int $adults, int $children): array
    {
        $listingId = (string) $property->hostaway_id;

        try {
            $ci = Carbon::parse($checkin)->startOfDay();
            $co = Carbon::parse($checkout)->startOfDay();
        } catch (\Throwable $e) {
            return $this->fail('Invalid dates.');
        }

        if ($co->lessThanOrEqualTo($ci)) {
            return $this->fail('Check-out must be after check-in.');
        }
        if ($ci->lessThan(now()->startOfDay())) {
            return $this->fail('Check-in cannot be in the past.');
        }

        $nights = $ci->diffInDays($co);
        $adults = max(1, $adults);
        $children = max(0, $children);
        $guests = $adults + $children;

        $listing = $this->hostaway->getListing($listingId) ?? [];
        $capacity = (int) ($listing['personCapacity'] ?? 0);
        $guestsIncluded = max(1, (int) ($listing['guestsIncluded'] ?? 1));
        $cleaningFee = (float) ($listing['cleaningFee'] ?? 0);
        $extraPerson = (float) ($listing['priceForExtraPerson'] ?? 0);
        $minNights = (int) ($listing['minNights'] ?? 1);
        $maxNights = (int) ($listing['maxNights'] ?? 0);
        $currency = $listing['currencyCode'] ?? ($property->currency ?: 'EUR');

        $errors = [];
        if ($capacity > 0 && $guests > $capacity) {
            $errors[] = "This property sleeps up to {$capacity} guests.";
        }
        if ($minNights > 0 && $nights < $minNights) {
            $errors[] = "Minimum stay is {$minNights} nights.";
        }
        if ($maxNights > 0 && $nights > $maxNights) {
            $errors[] = "Maximum stay is {$maxNights} nights.";
        }

        // Nightly prices + availability for every night in the range (checkout excluded).
        $cal = $this->hostaway->getCalendar($listingId, $ci->toDateString(), $co->copy()->subDay()->toDateString());
        $byDate = [];
        foreach (($cal['days'] ?? []) as $d) {
            if (! empty($d['date'])) {
                $byDate[$d['date']] = $d;
            }
        }

        $accommodation = 0.0;
        $unavailable = false;
        $minStay = null;
        $cursor = $ci->copy();
        while ($cursor->lessThan($co)) {
            $info = $byDate[$cursor->toDateString()] ?? null;
            if (! $info || empty($info['isAvailable'])) {
                $unavailable = true;
                break;
            }
            $accommodation += (float) ($info['price'] ?? 0);
            if ($cursor->equalTo($ci) && ! empty($info['minimumStay'])) {
                $minStay = (int) $info['minimumStay'];
            }
            $cursor->addDay();
        }

        if ($unavailable) {
            $errors[] = 'Some nights in your selection are not available.';
        }
        if ($minStay !== null && $nights < $minStay) {
            $errors[] = "Minimum stay for these dates is {$minStay} nights.";
        }

        $extraGuestFee = max(0, $guests - $guestsIncluded) * $extraPerson * $nights;
        $total = $accommodation + $cleaningFee + $extraGuestFee;

        return [
            'ok' => count($errors) === 0,
            'errors' => array_values(array_unique($errors)),
            'currency' => $currency,
            'checkin' => $ci->toDateString(),
            'checkout' => $co->toDateString(),
            'nights' => $nights,
            'adults' => $adults,
            'children' => $children,
            'guests' => $guests,
            'accommodation' => round($accommodation, 2),
            'cleaningFee' => round($cleaningFee, 2),
            'extraGuestFee' => round($extraGuestFee, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Build the Hostaway reservation payload for a request-to-book (inquiry).
     *
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $guest
     * @return array<string, mixed>
     */
    public function buildReservationPayload(RentalProperty $property, array $quote, array $guest): array
    {
        $name = trim((string) ($guest['name'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2);

        return [
            'listingMapId' => (int) $property->hostaway_id,
            'channelId' => 2000, // Direct booking
            'source' => 'KretaEiendom website',
            'status' => 'inquiry',
            'arrivalDate' => $quote['checkin'],
            'departureDate' => $quote['checkout'],
            'guestName' => $name,
            'guestFirstName' => $parts[0] ?? $name,
            'guestLastName' => $parts[1] ?? '',
            'guestEmail' => (string) ($guest['email'] ?? ''),
            'phone' => (string) ($guest['phone'] ?? ''),
            'numberOfGuests' => (int) ($quote['guests'] ?? 1),
            'adults' => (int) ($quote['adults'] ?? 1),
            'children' => (int) ($quote['children'] ?? 0),
            'totalPrice' => (float) ($quote['total'] ?? 0),
            'currency' => (string) ($quote['currency'] ?? 'EUR'),
            'hostNote' => 'Website request-to-book. Quoted total: '.($quote['currency'] ?? 'EUR').' '.($quote['total'] ?? 0)
                .($guest['message'] ? ' — Guest message: '.$guest['message'] : ''),
        ];
    }

    /**
     * Build a Hostaway reservation payload from a persisted Booking (used after
     * a successful payment to create the confirmed reservation).
     *
     * @return array<string, mixed>
     */
    public function reservationPayloadFromBooking(\Modules\RentalProperties\Models\Booking $booking, string $status = 'new'): array
    {
        $name = trim((string) $booking->guest_name);
        $parts = preg_split('/\s+/', $name, 2);

        return [
            'listingMapId' => (int) $booking->hostaway_id,
            'channelId' => 2000,
            'source' => 'KretaEiendom website',
            'status' => $status,
            'arrivalDate' => $booking->checkin->toDateString(),
            'departureDate' => $booking->checkout->toDateString(),
            'guestName' => $name,
            'guestFirstName' => $parts[0] ?? $name,
            'guestLastName' => $parts[1] ?? '',
            'guestEmail' => (string) $booking->guest_email,
            'phone' => (string) $booking->guest_phone,
            'numberOfGuests' => (int) ($booking->adults + $booking->children),
            'adults' => (int) $booking->adults,
            'children' => (int) $booking->children,
            'totalPrice' => (float) $booking->total,
            'currency' => (string) $booking->currency,
            'hostNote' => 'Website booking '.$booking->uuid.' — paid '.$booking->currency.' '.$booking->amount_due.' via '.$booking->payment_provider,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fail(string $message): array
    {
        return ['ok' => false, 'errors' => [$message]];
    }
}

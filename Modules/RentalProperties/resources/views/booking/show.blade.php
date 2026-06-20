@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', 'Your booking')

@section('content')
@php
    $sym = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'][$booking->currency] ?? ($booking->currency . ' ');
@endphp
<section class="py-12">
    <div class="mx-auto max-w-2xl px-4">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">

            @if($booking->status === \Modules\RentalProperties\Models\Booking::STATUS_CONFIRMED)
                <div class="flex items-center gap-2 text-emerald-700">
                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.3 3.3 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    <h1 class="text-xl font-bold">Booking confirmed</h1>
                </div>
                <p class="mt-2 text-sm text-slate-600">Your reservation is confirmed. A confirmation email is on its way.</p>
            @elseif($booking->status === \Modules\RentalProperties\Models\Booking::STATUS_FAILED)
                <h1 class="text-xl font-bold text-red-600">Payment could not be completed</h1>
                <p class="mt-2 text-sm text-slate-600">No charge was made. Please try again or contact us.</p>
            @else
                <h1 class="text-xl font-bold text-slate-900">Your booking is reserved</h1>
                <p class="mt-2 text-sm text-slate-600">We have received your request. Our team will email you a secure link to complete payment and confirm your stay.</p>
            @endif

            <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-200 pt-6 text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Check-in</div>
                    <div class="font-semibold text-slate-800">{{ $booking->checkin->format('D, d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Check-out</div>
                    <div class="font-semibold text-slate-800">{{ $booking->checkout->format('D, d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Guests</div>
                    <div class="font-semibold text-slate-800">{{ $booking->adults }} adults{{ $booking->children ? ', ' . $booking->children . ' children' : '' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Nights</div>
                    <div class="font-semibold text-slate-800">{{ $booking->nights }}</div>
                </div>
            </div>

            <div class="mt-6 space-y-1 border-t border-slate-200 pt-6 text-sm">
                <div class="flex justify-between text-slate-600"><span>Accommodation</span><span>{{ $sym }}{{ number_format($booking->accommodation, 2) }}</span></div>
                @if((float) $booking->cleaning_fee > 0)
                    <div class="flex justify-between text-slate-600"><span>Cleaning fee</span><span>{{ $sym }}{{ number_format($booking->cleaning_fee, 2) }}</span></div>
                @endif
                @if((float) $booking->extra_guest_fee > 0)
                    <div class="flex justify-between text-slate-600"><span>Extra guests</span><span>{{ $sym }}{{ number_format($booking->extra_guest_fee, 2) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-slate-900"><span>Total</span><span>{{ $sym }}{{ number_format($booking->total, 2) }}</span></div>
                @if((float) $booking->amount_due > 0 && (float) $booking->amount_due < (float) $booking->total)
                    <div class="flex justify-between font-semibold text-brand"><span>Due now</span><span>{{ $sym }}{{ number_format($booking->amount_due, 2) }}</span></div>
                @endif
            </div>

            <p class="mt-6 text-xs text-slate-400">Booking reference: {{ $booking->uuid }}</p>
        </div>
    </div>
</section>
@endsection

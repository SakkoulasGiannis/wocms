{{-- Shared body partial. Expects: $property, $related, and optional $isRental --}}
@php
    $featuredImage = $property->getFirstMediaUrl('featured_image', 'large') ?: $property->getFirstMediaUrl('featured_image');
        $gallery = $property->getMedia('gallery');
        $statusLabels = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold', 'rented' => 'Rented'];
        $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status));
        $statusTone = match($property->status) {
            'for_sale' => 'bg-emerald-500',
            'for_rent' => 'bg-amber-500',
            'sold', 'rented' => 'bg-slate-500',
            default => 'bg-brand',
        };
        $isRental = isset($isRental) && $isRental;
        $indexRoute = $isRental ? 'rental-properties.index' : 'properties.index';
        $indexLabel = $isRental ? 'Rentals' : 'Properties';
    @endphp

    {{-- Breadcrumb --}}
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-8xl px-4 py-6 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-sm text-slate-600">
                <a href="{{ route('home') }}" class="hover:text-brand">Home</a>
                <span class="text-slate-400">/</span>
                <a href="{{ route($indexRoute) }}" class="hover:text-brand">{{ $indexLabel }}</a>
                <span class="text-slate-400">/</span>
                <span class="line-clamp-1 text-slate-900">{{ $property->title }}</span>
            </nav>
        </div>
    </section>

    <section class="py-10">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($property->featured)
                            <span class="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">Featured</span>
                        @endif
                        <span class="rounded-full {{ $statusTone }} px-3 py-1 text-xs font-semibold text-white">{{ $statusLabel }}</span>
                    </div>
                    <h1 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $property->title }}</h1>
                    @if($property->address || $property->city)
                        <div class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                            <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                            <span>{{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}</span>
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    {{-- RentalProperty's formatted_price already carries /day.
                         Sale properties with status=for_rent get the /month
                         label here since their model omits it. --}}
                    <div class="text-3xl font-bold text-brand">{{ $property->formatted_price }}</div>
                    @if(! ($isRental ?? false) && $property->status === 'for_rent')
                        <div class="text-sm text-slate-500">/month</div>
                    @endif
                </div>
            </div>

            {{-- Gallery --}}
            @if($featuredImage || $gallery->count() > 0)
                <div class="mt-6 grid grid-cols-1 gap-3 lg:grid-cols-3">
                    @if($featuredImage)
                        <div class="overflow-hidden rounded-2xl bg-slate-100 lg:col-span-2">
                            <img src="{{ $featuredImage }}" alt="{{ $property->title }}" class="h-full max-h-[520px] w-full object-cover">
                        </div>
                    @endif
                    @if($gallery->count() > 0)
                        {{-- 2x2 grid that fills the same row-height as the
                             featured image (max-h-[520px]); each cell stretches
                             and the image covers it so we never get the empty
                             white space below short thumbs. --}}
                        <div class="grid grid-cols-2 grid-rows-2 gap-3 self-stretch">
                            @foreach($gallery->take(4) as $img)
                                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 lg:aspect-auto">
                                    <img src="{{ $img->getUrl('medium') ?: $img->getUrl() }}" alt="" class="h-full w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
                {{-- Main --}}
                <div class="space-y-8 lg:col-span-2">
                    {{-- Availability + booking (Hostaway, fetched async) — placed first --}}
                    @if(($isRental ?? false) && $property->hostaway_id)
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                                 id="booking-widget"
                                 data-cal="{{ route('rental-properties.calendar', $property->slug) }}"
                                 data-quote="{{ route('rental-properties.quote', $property->slug) }}"
                                 data-book="{{ route('rental-properties.book', $property->slug) }}"
                                 data-checkout="{{ route('booking.checkout.start', $property->slug) }}"
                                 data-csrf="{{ csrf_token() }}"
                                 data-currency="{{ $property->currency ?: 'EUR' }}">
                            <h2 class="text-xl font-bold text-slate-900">Availability &amp; Booking</h2>
                            <p class="mt-1 text-sm text-slate-500">Pick your check-in and check-out dates to see the price and request a booking.</p>
                            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                                <div id="hostaway-calendar"></div>
                                <div id="booking-panel"></div>
                            </div>
                        </section>
                    @endif

                    {{-- Description --}}
                    @if($property->description)
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-bold text-slate-900">Description</h2>
                            <div class="mt-4 max-w-none text-justify text-slate-600 [hyphens:auto]">
                                {!! nl2br(e($property->description)) !!}
                            </div>
                        </section>
                    @endif

                    {{-- Details --}}
                    <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-xl font-bold text-slate-900">Property Details</h2>
                        <dl class="mt-4 grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                            @foreach([
                                'Type' => $property->property_type ? str_replace('_', ' ', $property->property_type) : null,
                                'Bedrooms' => $property->bedrooms,
                                'Bathrooms' => $property->bathrooms,
                                'Rooms' => $property->rooms,
                                'Area' => $property->area ? number_format($property->area, 0).' m²' : null,
                                'Land' => $property->land_size ? number_format($property->land_size, 0).' m²' : null,
                                'Garages' => $property->garages,
                                'Year Built' => $property->year_built,
                                'Floor' => $property->floor,
                            ] as $label => $value)
                                @if($value)
                                    <div class="flex flex-col">
                                        <dt class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                        <dd class="mt-0.5 font-semibold capitalize text-slate-900">{{ $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </section>

                    {{-- Features --}}
                    @if(!empty($property->features))
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-bold text-slate-900">Features & Amenities</h2>
                            <ul class="mt-4 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2 md:grid-cols-3">
                                @foreach($property->features as $feature)
                                    <li class="flex items-center gap-2">
                                        <svg class="h-4 w-4 flex-shrink-0 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        <span class="text-slate-700">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    {{-- Video --}}
                    @if($property->video_url)
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-bold text-slate-900">Video</h2>
                            @php
                                preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $property->video_url, $ytMatch);
                                $ytId = $ytMatch[1] ?? null;
                            @endphp
                            <div class="mt-4 aspect-video overflow-hidden rounded-xl bg-slate-900">
                                @if($ytId)
                                    <iframe src="https://www.youtube.com/embed/{{ $ytId }}" class="h-full w-full" frameborder="0" allowfullscreen></iframe>
                                @else
                                    <video controls class="h-full w-full"><source src="{{ $property->video_url }}" type="video/mp4"></video>
                                @endif
                            </div>
                        </section>
                    @endif

                    {{-- Map --}}
                    @if($property->latitude && $property->longitude)
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-bold text-slate-900">Location</h2>
                            <div id="property-map" class="mt-4 h-80 overflow-hidden rounded-xl bg-slate-100"></div>
                        </section>
                    @endif
                </div>

                {{-- Sidebar --}}
                <aside class="lg:col-span-1">
                    <div class="sticky top-24 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Interested in this property?</h3>
                        <p class="mt-1 text-sm text-slate-500">Get in touch with our team for a viewing or more information.</p>

                        @php
                            $contactEmail = \App\Models\Setting::get('site_email', \App\Models\Setting::get('contact_email', ''));
                            $contactPhone = \App\Models\Setting::get('site_phone', \App\Models\Setting::get('contact_phone', ''));
                        @endphp

                        <div class="mt-5 space-y-2">
                            @if($contactEmail)
                                <a href="mailto:{{ $contactEmail }}?subject=Inquiry about {{ $property->title }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></svg>
                                    Contact Us
                                </a>
                            @endif
                            @if($contactPhone)
                                <a href="tel:{{ $contactPhone }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                                    {{ $contactPhone }}
                                </a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>

            {{-- Related --}}
            @if($related->count() > 0)
                <section class="mt-16 border-t border-slate-200 pt-10">
                    <h2 class="text-center text-2xl font-bold text-slate-900 md:text-3xl">Similar Properties</h2>
                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($related as $prop)
                            @include('themes.ketw.templates.properties._card-grid', ['property' => $prop, 'isRental' => $isRental])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>

@if($property->latitude && $property->longitude)
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var map = L.map('property-map').setView([{{ $property->latitude }}, {{ $property->longitude }}], 12);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([{{ $property->latitude }}, {{ $property->longitude }}]).addTo(map)
                    .bindPopup('<strong>{{ e($property->title) }}</strong>').openPopup();
            });
        </script>
    @endpush
@endif

@if(($isRental ?? false) && $property->hostaway_id)
    @push('scripts')
        <script>
        (function () {
            function symFor(c) { return ({ EUR: '€', USD: '$', GBP: '£' })[c] || (c ? c + ' ' : ''); }
            function pad(n) { return String(n).padStart(2, '0'); }
            function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function parseYmd(s) { var p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); }
            function fmtMonth(ym) { var p = ym.split('-'); return new Date(p[0], p[1] - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }); }
            function shiftMonth(ym, d) { var p = ym.split('-').map(Number); var x = new Date(p[0], p[1] - 1 + d, 1); return x.getFullYear() + '-' + pad(x.getMonth() + 1); }
            function fmtNice(ds) { return parseYmd(ds).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short' }); }
            function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

            function initBooking() {
                var root = document.getElementById('booking-widget');
                if (!root || root._init) { return; }
                root._init = true;
                var calEl = document.getElementById('hostaway-calendar');
                var panel = document.getElementById('booking-panel');
                var S = {
                    calUrl: root.dataset.cal, quoteUrl: root.dataset.quote, bookUrl: root.dataset.book, checkoutUrl: root.dataset.checkout, csrf: root.dataset.csrf,
                    sym: symFor(root.dataset.currency || 'EUR'), days: {}, month: null, minMonth: null, maxMonth: null,
                    checkin: null, checkout: null, adults: 2, children: 0, maxGuests: 0, minNights: 1, bookingEnabled: false,
                    quote: null, quoting: false, formOpen: false, submitting: false, done: null, errorMsg: null, _form: {}
                };
                var today = ymd(new Date());

                calEl.innerHTML = '<div class="py-8 text-center text-slate-400"><i class="fa fa-spinner fa-spin mr-2"></i>Loading availability…</div>';
                fetch(S.calUrl, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (data) {
                    if (!data || !data.success || !data.days || !data.days.length) { calEl.innerHTML = '<div class="py-8 text-center text-slate-400">Availability is not available right now.</div>'; return; }
                    data.days.forEach(function (d) { if (d.date) { S.days[d.date] = d; } });
                    var ks = Object.keys(S.days).sort(); S.minMonth = ks[0].slice(0, 7); S.maxMonth = ks[ks.length - 1].slice(0, 7); S.month = S.minMonth;
                    if (data.listing) { S.maxGuests = data.listing.maxGuests || 0; S.minNights = data.listing.minNights || 1; S.bookingEnabled = !!data.listing.bookingEnabled; if (data.listing.currency) { S.sym = symFor(data.listing.currency); } }
                    renderCal(); renderPanel();
                }).catch(function () { calEl.innerHTML = '<div class="py-8 text-center text-red-400">Could not load availability.</div>'; });

                function avail(ds) { var i = S.days[ds]; return !!(i && i.isAvailable); }
                function nightsBlocked(a, b) { var d = parseYmd(a), e = parseYmd(b); while (d < e) { if (!avail(ymd(d))) { return true; } d.setDate(d.getDate() + 1); } return false; }

                function pickDay(ds) {
                    if (ds < today) { return; }
                    if (!S.checkin || (S.checkin && S.checkout)) {
                        if (!avail(ds)) { return; }
                        S.checkin = ds; S.checkout = null; S.quote = null; S.done = null; S.formOpen = false; S.errorMsg = null;
                    } else {
                        if (ds <= S.checkin) { if (avail(ds)) { S.checkin = ds; } renderCal(); renderPanel(); return; }
                        if (nightsBlocked(S.checkin, ds)) { if (avail(ds)) { S.checkin = ds; S.checkout = null; } renderCal(); renderPanel(); return; }
                        S.checkout = ds; doQuote();
                    }
                    renderCal(); renderPanel();
                }

                function renderCal() {
                    var ym = S.month, p = ym.split('-').map(Number), year = p[0], month = p[1];
                    var startDow = (new Date(year, month - 1, 1).getDay() + 6) % 7, dim = new Date(year, month, 0).getDate();
                    var atMin = ym <= S.minMonth, atMax = ym >= S.maxMonth, h = '';
                    h += '<div class="mb-4 flex items-center justify-between">';
                    h += '<button type="button" data-nav="prev" class="rounded-lg border border-slate-300 px-3 py-1 text-slate-600 hover:bg-slate-50 disabled:opacity-30"' + (atMin ? ' disabled' : '') + '>&larr;</button>';
                    h += '<div class="font-semibold text-slate-900">' + fmtMonth(ym) + '</div>';
                    h += '<button type="button" data-nav="next" class="rounded-lg border border-slate-300 px-3 py-1 text-slate-600 hover:bg-slate-50 disabled:opacity-30"' + (atMax ? ' disabled' : '') + '>&rarr;</button>';
                    h += '</div><div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400">';
                    ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].forEach(function (w) { h += '<div>' + w + '</div>'; });
                    h += '</div><div class="grid grid-cols-7 gap-1">';
                    for (var i = 0; i < startDow; i++) { h += '<div></div>'; }
                    for (var day = 1; day <= dim; day++) {
                        var ds = year + '-' + pad(month) + '-' + pad(day), info = S.days[ds], av = info && info.isAvailable, past = ds < today;
                        var isCI = ds === S.checkin, isCO = ds === S.checkout, mid = S.checkin && S.checkout && ds > S.checkin && ds < S.checkout;
                        // While choosing a check-out, allow any future day after check-in —
                        // a "booked" day can still be a valid check-out (turnover day).
                        var pickingCheckout = S.checkin && !S.checkout && !past && ds > S.checkin;
                        var cls, click = ' data-day="' + ds + '"';
                        if (isCI || isCO) { cls = 'bg-emerald-600 text-white font-semibold cursor-pointer'; }
                        else if (mid) { cls = 'bg-emerald-100 text-emerald-800 cursor-pointer'; }
                        else if (av && !past) { cls = 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 cursor-pointer hover:bg-emerald-100'; }
                        else if (pickingCheckout) { cls = 'bg-slate-100 text-slate-500 cursor-pointer hover:bg-emerald-100'; }
                        else { cls = 'bg-slate-100 text-slate-400 line-through'; click = ''; }
                        var price = (av && !past && info.price && !(isCI || isCO)) ? '<div class="font-normal" style="font-size:10px">' + S.sym + Math.round(info.price) + '</div>' : '';
                        h += '<div' + click + ' class="rounded-lg py-2 text-center text-sm ' + cls + '"><div class="font-semibold">' + day + '</div>' + price + '</div>';
                    }
                    h += '</div><div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-slate-500">';
                    h += '<span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-emerald-100 ring-1 ring-emerald-300"></span>Available</span>';
                    h += '<span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-emerald-600"></span>Selected</span>';
                    h += '<span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-slate-200"></span>Booked</span></div>';
                    calEl.innerHTML = h;
                    var pv = calEl.querySelector('[data-nav="prev"]'), nx = calEl.querySelector('[data-nav="next"]');
                    if (pv) { pv.addEventListener('click', function () { if (S.month > S.minMonth) { S.month = shiftMonth(S.month, -1); renderCal(); } }); }
                    if (nx) { nx.addEventListener('click', function () { if (S.month < S.maxMonth) { S.month = shiftMonth(S.month, 1); renderCal(); } }); }
                    calEl.querySelectorAll('[data-day]').forEach(function (c) { c.addEventListener('click', function () { pickDay(c.getAttribute('data-day')); }); });
                }

                function doQuote() {
                    S.quoting = true; S.quote = null; renderPanel();
                    var u = S.quoteUrl + '?checkin=' + S.checkin + '&checkout=' + S.checkout + '&adults=' + S.adults + '&children=' + S.children;
                    fetch(u, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (q) { S.quoting = false; S.quote = q; renderPanel(); })
                        .catch(function () { S.quoting = false; S.quote = { ok: false, errors: ['Could not get a price. Please try again.'] }; renderPanel(); });
                }

                function stepper(label, key) {
                    return '<div class="flex items-center justify-between"><span class="text-sm text-slate-600">' + label + '</span>' +
                        '<span class="inline-flex items-center gap-3"><button type="button" data-step="' + key + '|-1" class="h-7 w-7 rounded-full border border-slate-300 text-slate-600">−</button>' +
                        '<span class="w-6 text-center text-sm font-semibold">' + S[key] + '</span>' +
                        '<button type="button" data-step="' + key + '|1" class="h-7 w-7 rounded-full border border-slate-300 text-slate-600">+</button></span></div>';
                }

                function renderPanel() {
                    if (S.done) {
                        panel.innerHTML = '<div class="rounded-xl bg-emerald-50 p-5 ring-1 ring-emerald-200"><div class="flex items-center gap-2 font-semibold text-emerald-800"><i class="fa fa-check-circle"></i> Request sent</div><p class="mt-2 text-sm text-emerald-700">' + esc(S.done) + '</p></div>';
                        return;
                    }
                    var h = '<div class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">';
                    h += '<div class="grid grid-cols-2 gap-3">';
                    h += '<div class="rounded-lg bg-white p-3 ring-1 ring-slate-200"><div class="text-xs uppercase tracking-wide text-slate-400">Check-in</div><div class="text-sm font-semibold text-slate-800">' + (S.checkin ? fmtNice(S.checkin) : '—') + '</div></div>';
                    h += '<div class="rounded-lg bg-white p-3 ring-1 ring-slate-200"><div class="text-xs uppercase tracking-wide text-slate-400">Check-out</div><div class="text-sm font-semibold text-slate-800">' + (S.checkout ? fmtNice(S.checkout) : '—') + '</div></div>';
                    h += '</div>';
                    if (!S.checkin) { h += '<p class="mt-3 text-sm text-slate-500">Pick your check-in date on the calendar' + (S.minNights > 1 ? ' (min ' + S.minNights + ' nights)' : '') + '.</p>'; }
                    else if (!S.checkout) { h += '<p class="mt-3 text-sm text-slate-500">Now pick your check-out date.</p>'; }
                    h += '<div class="mt-4 space-y-2">' + stepper('Adults', 'adults') + stepper('Children', 'children') + '</div>';
                    if (S.maxGuests) { h += '<p class="mt-1 text-xs text-slate-400">Sleeps up to ' + S.maxGuests + ' guests.</p>'; }
                    if (S.quoting) { h += '<div class="mt-4 text-center text-sm text-slate-400"><i class="fa fa-spinner fa-spin mr-1"></i>Calculating price…</div>'; }
                    else if (S.quote) {
                        if (S.quote.ok) {
                            var q = S.quote;
                            h += '<div class="mt-4 space-y-1 border-t border-slate-200 pt-4 text-sm">';
                            h += '<div class="flex justify-between text-slate-600"><span>' + S.sym + Math.round(q.accommodation / q.nights) + ' × ' + q.nights + ' nights</span><span>' + S.sym + q.accommodation + '</span></div>';
                            if (q.cleaningFee) { h += '<div class="flex justify-between text-slate-600"><span>Cleaning fee</span><span>' + S.sym + q.cleaningFee + '</span></div>'; }
                            if (q.extraGuestFee) { h += '<div class="flex justify-between text-slate-600"><span>Extra guests</span><span>' + S.sym + q.extraGuestFee + '</span></div>'; }
                            h += '<div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-slate-900"><span>Total</span><span>' + S.sym + q.total + '</span></div></div>';
                            if (!S.bookingEnabled) { h += '<div class="mt-4 rounded-lg bg-slate-100 px-4 py-3 text-center text-sm text-slate-500">Online booking requests are temporarily unavailable. Please contact us to book these dates.</div>'; }
                            else if (!S.formOpen) { h += '<button type="button" data-act="open-form" class="mt-4 w-full rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-white hover:bg-brand-dark">Request to Book</button>'; }
                        } else {
                            (S.quote.errors || ['These dates are not available.']).forEach(function (e) { h += '<div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-200"><i class="fa fa-exclamation-triangle mr-1"></i>' + esc(e) + '</div>'; });
                        }
                    }
                    if (S.formOpen && S.quote && S.quote.ok) {
                        h += '<div class="mt-4 space-y-3 border-t border-slate-200 pt-4">';
                        h += '<input data-f="name" type="text" placeholder="Full name *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">';
                        h += '<input data-f="email" type="email" placeholder="Email *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">';
                        h += '<input data-f="phone" type="tel" placeholder="Phone" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">';
                        h += '<textarea data-f="message" rows="2" placeholder="Message (optional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>';
                        if (S.errorMsg) { h += '<div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-200">' + esc(S.errorMsg) + '</div>'; }
                        h += '<button type="button" data-act="submit" class="w-full rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-white hover:bg-brand-dark disabled:opacity-50"' + (S.submitting ? ' disabled' : '') + '>' + (S.submitting ? '<i class="fa fa-spinner fa-spin mr-1"></i>Processing…' : 'Continue to booking') + '</button>';
                        h += '<p class="text-xs text-slate-400">No payment now — we will confirm availability and payment details by email.</p></div>';
                    }
                    h += '</div>';
                    panel.innerHTML = h;
                    panel.querySelectorAll('[data-step]').forEach(function (b) {
                        b.addEventListener('click', function () {
                            var x = b.getAttribute('data-step').split('|'), k = x[0], d = parseInt(x[1], 10), nv = S[k] + d;
                            if (k === 'adults' && nv < 1) { nv = 1; } if (k === 'children' && nv < 0) { nv = 0; }
                            if (S.maxGuests) { var others = k === 'adults' ? S.children : S.adults; if (nv + others > S.maxGuests) { return; } }
                            S[k] = nv; if (S.checkin && S.checkout) { doQuote(); } else { renderPanel(); }
                        });
                    });
                    var of = panel.querySelector('[data-act="open-form"]'); if (of) { of.addEventListener('click', function () { S.formOpen = true; renderPanel(); }); }
                    var sb = panel.querySelector('[data-act="submit"]'); if (sb) { sb.addEventListener('click', submitBooking); }
                    ['name', 'email', 'phone', 'message'].forEach(function (f) {
                        var el = panel.querySelector('[data-f="' + f + '"]');
                        if (el) { if (S._form[f]) { el.value = S._form[f]; } el.addEventListener('input', function () { S._form[f] = el.value; }); }
                    });
                }

                function submitBooking() {
                    if (!S._form.name || !S._form.email) { S.errorMsg = 'Please enter your name and email.'; renderPanel(); return; }
                    S.submitting = true; S.errorMsg = null; renderPanel();
                    fetch(S.checkoutUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': S.csrf },
                        body: JSON.stringify({ checkin: S.checkin, checkout: S.checkout, adults: S.adults, children: S.children, name: S._form.name, email: S._form.email, phone: S._form.phone || '', message: S._form.message || '' })
                    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                        .then(function (res) {
                            S.submitting = false;
                            if (res.ok && res.j.success && res.j.redirect) { window.location.href = res.j.redirect; return; }
                            S.errorMsg = (res.j && res.j.message) || 'Could not continue. Please try again.';
                            renderPanel();
                        })
                        .catch(function () { S.submitting = false; S.errorMsg = 'Network error. Please try again.'; renderPanel(); });
                }
            }
            if (document.readyState !== 'loading') { initBooking(); }
            document.addEventListener('DOMContentLoaded', initBooking);
            document.addEventListener('livewire:navigated', initBooking);
        })();
        </script>
    @endpush
@endif

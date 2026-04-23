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
                    <div class="text-3xl font-bold text-brand">{{ $property->formatted_price }}</div>
                    @if($property->status === 'for_rent')
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
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($gallery->take(4) as $img)
                                <div class="overflow-hidden rounded-xl bg-slate-100">
                                    <img src="{{ $img->getUrl('medium') ?: $img->getUrl() }}" alt="" class="h-40 w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
                {{-- Main --}}
                <div class="space-y-8 lg:col-span-2">
                    {{-- Description --}}
                    @if($property->description)
                        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 class="text-xl font-bold text-slate-900">Description</h2>
                            <div class="prose prose-slate mt-4 max-w-none text-slate-600">
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
                var map = L.map('property-map').setView([{{ $property->latitude }}, {{ $property->longitude }}], 15);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([{{ $property->latitude }}, {{ $property->longitude }}]).addTo(map)
                    .bindPopup('<strong>{{ e($property->title) }}</strong>').openPopup();
            });
        </script>
    @endpush
@endif

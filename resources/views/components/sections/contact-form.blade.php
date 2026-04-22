@props(['content' => [], 'settings' => []])

@php
    $formSlug = $content['form_slug'] ?? 'contact-form';
    $heading = $content['heading'] ?? '';
    $subheading = $content['subheading'] ?? '';
    $description = $content['description'] ?? '';
    $sectionClass = $content['section_class'] ?? 'py-16';
    $showInfo = (bool) ($content['show_info'] ?? true);
    $showMap = (bool) ($content['show_map'] ?? false);
    $maxWidth = $content['max_width'] ?? 'max-w-3xl';

    $address = \App\Models\Setting::get('site_address');
    $phone = \App\Models\Setting::get('site_phone', '');
    $email = \App\Models\Setting::get('site_email', '');
    $opentime = \App\Models\Setting::get('site_opentime');
    $lat = \App\Models\Setting::get('site_latitude', '35.24');
    $lng = \App\Models\Setting::get('site_longitude', '24.47');
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        @if($heading || $subheading)
            <div class="mx-auto max-w-2xl text-center mb-10">
                @if($subheading)
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subheading }}</p>
                @endif
                @if($heading)
                    <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                @endif
                @if($description)
                    <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 gap-10 {{ $showInfo ? 'lg:grid-cols-3' : '' }}">
            {{-- Form --}}
            <div class="{{ $showInfo ? 'lg:col-span-2' : 'mx-auto ' . $maxWidth }}">
                @livewire('frontend.form-renderer', ['slug' => $formSlug])
            </div>

            {{-- Info sidebar --}}
            @if($showInfo)
                <aside class="rounded-2xl bg-slate-50 p-8">
                    <h3 class="text-xl font-bold text-slate-900">Contact Information</h3>
                    <ul class="mt-6 space-y-6 text-sm">
                        @if($address)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    Address
                                </div>
                                <p class="mt-1 text-slate-600">{!! nl2br(e($address)) !!}</p>
                            </li>
                        @endif
                        @if($phone || $email)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                                    Information
                                </div>
                                <p class="mt-1 text-slate-600">
                                    @if($phone)<a href="tel:{{ $phone }}" class="block hover:text-brand">{{ $phone }}</a>@endif
                                    @if($email)<a href="mailto:{{ $email }}" class="block hover:text-brand">{{ $email }}</a>@endif
                                </p>
                            </li>
                        @endif
                        @if($opentime)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                                    Open Hours
                                </div>
                                <p class="mt-1 text-slate-600">{!! nl2br(e($opentime)) !!}</p>
                            </li>
                        @endif
                    </ul>
                </aside>
            @endif
        </div>

        {{-- Map --}}
        @if($showMap)
            <div class="mt-10 overflow-hidden rounded-2xl">
                <div id="contact-map-{{ md5($formSlug) }}" class="h-96 w-full bg-slate-100"></div>
            </div>

            @push('styles')
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            @endpush
            @push('scripts')
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var el = document.getElementById('contact-map-{{ md5($formSlug) }}');
                        if (!el || !window.L) return;
                        var map = L.map(el.id).setView([{{ $lat }}, {{ $lng }}], 14);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
                    });
                </script>
            @endpush
        @endif
    </div>
</section>

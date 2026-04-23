@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', 'Contact Us')

@section('content')
    <!-- Page Title -->
    <section class="flat-title-page" style="background-image: url(/themes/kretaeiendom/images/page-title/page-title-4.jpg);">
        <div class="container">
            <div class="breadcrumb-content">
                <ul class="breadcrumb">
                    <li><a href="{{ url('/') }}" class="text-white">Home</a></li>
                    <li class="text-white">/ Contact Us</li>
                </ul>
                <h1 class="text-center text-white title">Contact Us</h1>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="flat-section flat-contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="contact-content">
                        <h4>Drop Us A Line</h4>
                        <p class="body-2 text-variant-1">Feel free to connect with us through our online channels for updates, news, and more.</p>

                        @livewire('frontend.form-renderer', ['slug' => 'contact-form'])
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info">
                        <h4>Contact Us</h4>
                        <ul>
                            @if($address = \App\Models\Setting::get('site_address'))
                            <li class="box">
                                <h6 class="title">Address:</h6>
                                <p class="text-variant-1">{!! nl2br(e($address)) !!}</p>
                            </li>
                            @endif

                            @php
                                $phone = \App\Models\Setting::get('site_phone', '');
                                $email = \App\Models\Setting::get('site_email', '');
                            @endphp

                            @if($phone || $email)
                            <li class="box">
                                <h6 class="title">Information:</h6>
                                <p class="text-variant-1">
                                    @if($phone)<a href="tel:{{ $phone }}">{{ $phone }}</a><br>@endif
                                    @if($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@endif
                                </p>
                            </li>
                            @endif

                            @if($opentime = \App\Models\Setting::get('site_opentime'))
                            <li class="box">
                                <div class="title">Open Hours:</div>
                                <p class="text-variant-1">{!! nl2br(e($opentime)) !!}</p>
                            </li>
                            @endif

                            @php
                                $facebook = \App\Models\Setting::get('social_facebook', '');
                                $instagram = \App\Models\Setting::get('social_instagram', '');
                                $twitter = \App\Models\Setting::get('social_twitter', '');
                                $linkedin = \App\Models\Setting::get('social_linkedin', '');
                                $youtube = \App\Models\Setting::get('social_youtube', '');
                            @endphp

                            @if($facebook || $instagram || $twitter || $linkedin || $youtube)
                            <li class="box">
                                <div class="title">Follow Us:</div>
                                <ul class="box-social">
                                    @if($facebook)<li><a href="{{ $facebook }}" target="_blank" class="item"><i class="icon icon-facebook"></i></a></li>@endif
                                    @if($instagram)<li><a href="{{ $instagram }}" target="_blank" class="item"><i class="icon icon-instagram"></i></a></li>@endif
                                    @if($twitter)<li><a href="{{ $twitter }}" target="_blank" class="item"><i class="icon icon-twitter"></i></a></li>@endif
                                    @if($youtube)<li><a href="{{ $youtube }}" target="_blank" class="item"><i class="icon icon-youtube"></i></a></li>@endif
                                    @if($linkedin)<li><a href="{{ $linkedin }}" target="_blank" class="item"><i class="icon icon-linkedin"></i></a></li>@endif
                                </ul>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map -->
    @php
        $lat = \App\Models\Setting::get('site_latitude', '35.24');
        $lng = \App\Models\Setting::get('site_longitude', '24.47');
    @endphp
    <section>
        <div id="contact-map" style="width:100%;height:400px;background:#e8e8e8;"></div>
    </section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('contact-map');
    if (!mapEl) return;

    var lat = {{ $lat ?: '35.24' }};
    var lng = {{ $lng ?: '24.47' }};
    var map = L.map('contact-map').setView([lat, lng], 14);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors &copy; <a href="https://carto.com">CARTO</a>'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
});
</script>
@endpush

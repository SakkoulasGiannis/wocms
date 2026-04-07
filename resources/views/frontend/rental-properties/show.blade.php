@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $property->meta_title ?: $property->title)

@section('content')
    @php
        $featuredImage = $property->getFirstMediaUrl('featured_image', 'large') ?: $property->getFirstMediaUrl('featured_image');
        $gallery = $property->getMedia('gallery');
        $statusLabels = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold', 'rented' => 'Rented'];
        $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status));
    @endphp

    {{-- Breadcrumb --}}
    <section class="flat-title-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumbs">
                        <ul>
                            <li><a href="{{ route('home') }}">Home</a></li>
                            <li><a href="{{ route('rental-properties.index') }}">Properties</a></li>
                            <li>{{ $property->title }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="flat-section pt-0 flat-property-detail">
        <div class="container">
            {{-- Header --}}
            <div class="header-property-detail">
                <div class="content-top d-flex justify-content-between align-items-center flex-wrap gap-16">
                    <div class="box-name">
                        <div class="d-flex align-items-center gap-8">
                            @if($property->featured)
                                <span class="flag-tag primary">Featured</span>
                            @endif
                            <span class="flag-tag style-1">{{ $statusLabel }}</span>
                        </div>
                        <h3 class="title mt-8">{{ $property->title }}</h3>
                        @if($property->address || $property->city)
                            <div class="mt-4 d-flex align-items-center gap-8 text-variant-1">
                                <i class="icon icon-mapPinLine fs-16"></i>
                                <span>{{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}{{ $property->state ? ', ' . $property->state : '' }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="box-price">
                        <h3 class="fw-8">{{ $property->formatted_price }}</h3>
                        @if($property->status === 'for_rent')
                            <span class="body-2 text-variant-1">/month</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Gallery --}}
            @if($featuredImage || $gallery->count() > 0)
                <div class="row mt-20">
                    <div class="col-lg-8">
                        @if($featuredImage)
                            <div class="img-style">
                                <img src="{{ $featuredImage }}" alt="{{ $property->title }}" class="w-100" style="border-radius:12px;max-height:500px;object-fit:cover;">
                            </div>
                        @endif
                    </div>
                    @if($gallery->count() > 0)
                        <div class="col-lg-4">
                            <div class="row gap-12">
                                @foreach($gallery->take(4) as $img)
                                    <div class="col-6">
                                        <img src="{{ $img->getUrl('medium') ?: $img->getUrl() }}" alt="gallery" class="w-100" style="border-radius:8px;height:140px;object-fit:cover;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="row mt-30">
                <div class="col-lg-8">
                    {{-- Description --}}
                    @if($property->description)
                        <div class="single-property-element">
                            <div class="box-title-element">
                                <h5 class="fw-6">Description</h5>
                            </div>
                            <div class="body-2 text-variant-1">{!! nl2br(e($property->description)) !!}</div>
                        </div>
                    @endif

                    {{-- Property Details --}}
                    <div class="single-property-element mt-30">
                        <div class="box-title-element">
                            <h5 class="fw-6">Property Details</h5>
                        </div>
                        <div class="row">
                            @if($property->property_type)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Type:</span> <strong>{{ ucfirst($property->property_type) }}</strong></div>
                            @endif
                            @if($property->bedrooms)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Bedrooms:</span> <strong>{{ $property->bedrooms }}</strong></div>
                            @endif
                            @if($property->bathrooms)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Bathrooms:</span> <strong>{{ $property->bathrooms }}</strong></div>
                            @endif
                            @if($property->rooms)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Rooms:</span> <strong>{{ $property->rooms }}</strong></div>
                            @endif
                            @if($property->area)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Area:</span> <strong>{{ number_format($property->area, 0) }} m²</strong></div>
                            @endif
                            @if($property->land_size)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Land:</span> <strong>{{ number_format($property->land_size, 0) }} m²</strong></div>
                            @endif
                            @if($property->garages)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Garages:</span> <strong>{{ $property->garages }}</strong></div>
                            @endif
                            @if($property->year_built)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Year Built:</span> <strong>{{ $property->year_built }}</strong></div>
                            @endif
                            @if($property->floor)
                                <div class="col-md-4 col-6 mb-12"><span class="text-variant-1">Floor:</span> <strong>{{ $property->floor }}</strong></div>
                            @endif
                        </div>
                    </div>

                    {{-- Features --}}
                    @if(!empty($property->features))
                        <div class="single-property-element mt-30">
                            <div class="box-title-element">
                                <h5 class="fw-6">Features & Amenities</h5>
                            </div>
                            <div class="row">
                                @foreach($property->features as $feature)
                                    <div class="col-md-4 col-6 mb-8">
                                        <i class="icon icon-check text-primary mr-4"></i>
                                        <span>{{ $feature }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Video --}}
                    @if($property->video_url)
                        <div class="single-property-element mt-30">
                            <div class="box-title-element">
                                <h5 class="fw-6">Video</h5>
                            </div>
                            <div class="ratio ratio-16x9" style="border-radius:12px;overflow:hidden;">
                                @php
                                    preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $property->video_url, $ytMatch);
                                    $ytId = $ytMatch[1] ?? null;
                                @endphp
                                @if($ytId)
                                    <iframe src="https://www.youtube.com/embed/{{ $ytId }}" frameborder="0" allowfullscreen></iframe>
                                @else
                                    <video controls><source src="{{ $property->video_url }}" type="video/mp4"></video>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Map --}}
                    @if($property->latitude && $property->longitude)
                        <div class="single-property-element mt-30">
                            <div class="box-title-element">
                                <h5 class="fw-6">Location</h5>
                            </div>
                            <div id="property-map" style="height:350px;border-radius:12px;"></div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="col-lg-4">
                    <div class="widget-sidebar fixed-sidebar">
                        <div class="widget-box bg-white p-20 rounded-12 shadow-sm">
                            <h5 class="fw-6 mb-16">Interested in this property?</h5>
                            <a href="mailto:{{ \App\Models\Setting::get('contact_email', 'info@kretaeiendom.com') }}" class="tf-btn primary w-100">
                                <i class="icon icon-mail mr-8"></i> Contact Us
                            </a>
                            @if(\App\Models\Setting::get('contact_phone'))
                                <a href="tel:{{ \App\Models\Setting::get('contact_phone') }}" class="tf-btn btn-line w-100 mt-12">
                                    <i class="icon icon-phone2 mr-8"></i> {{ \App\Models\Setting::get('contact_phone') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related Properties --}}
            @if($related->count() > 0)
                <div class="flat-section pt-0 mt-30">
                    <div class="box-title text-center">
                        <h3 class="title">Similar Properties</h3>
                    </div>
                    <div class="row">
                        @foreach($related as $prop)
                            <div class="col-lg-4 col-md-6">
                                @include('frontend.properties._card-grid', ['property' => $prop])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@if($property->latitude && $property->longitude)
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('property-map').setView([{{ $property->latitude }}, {{ $property->longitude }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([{{ $property->latitude }}, {{ $property->longitude }}]).addTo(map)
        .bindPopup('<strong>{{ e($property->title) }}</strong>').openPopup();
});
</script>
@endpush
@endif

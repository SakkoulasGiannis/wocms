@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $title ?? 'Properties')

@section('content')
    {{-- Filter Bar --}}
    <section class="flat-filter-search-v2">
        <div class="flat-tab flat-tab-form">
            <div class="tab-content">
                <div class="tab-pane fade active show" role="tabpanel">
                    <div class="form-sl">
                        <form method="get" action="{{ route('properties.index') }}">
                            <div class="wd-find-select shadow-3">
                                <div class="inner-group">
                                    <div class="form-group-1 search-form form-style">
                                        <label>Type</label>
                                        <select name="type" class="form-select">
                                            <option value="">All Types</option>
                                            @foreach($propertyTypes as $value => $label)
                                                <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group-2 form-style">
                                        <label>Location</label>
                                        <div class="group-ip">
                                            <input type="text" class="form-control" name="city" placeholder="Search Location" value="{{ $filters['city'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group-3 form-style">
                                        <label>Keyword</label>
                                        <input type="text" class="form-control" name="search" placeholder="Search Keyword" value="{{ $filters['search'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="box-btn-advanced">
                                    <div class="form-group-4 box-filter">
                                        <a class="tf-btn btn-line filter-advanced pull-right" onclick="document.getElementById('advancedFilters').classList.toggle('d-none')">
                                            <span class="text-1">Advanced</span>
                                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5.5 12.375V3.4375M5.5 12.375C5.86467 12.375 6.21441 12.5199 6.47227 12.7777C6.73013 13.0356 6.875 13.3853 6.875 13.75C6.875 14.1147 6.73013 14.4644 6.47227 14.7223C6.21441 14.9801 5.86467 15.125 5.5 15.125M5.5 12.375C5.13533 12.375 4.78559 12.5199 4.52773 12.7777C4.26987 13.0356 4.125 13.3853 4.125 13.75C4.125 14.1147 4.26987 14.4644 4.52773 14.7223C4.78559 14.9801 5.13533 15.125 5.5 15.125M5.5 15.125V18.5625M16.5 12.375V3.4375M16.5 12.375C16.8647 12.375 17.2144 12.5199 17.4723 12.7777C17.7301 13.0356 17.875 13.3853 17.875 13.75C17.875 14.1147 17.7301 14.4644 17.4723 14.7223C17.2144 14.9801 16.8647 15.125 16.5 15.125M16.5 12.375C16.1353 12.375 15.7856 12.5199 15.5277 12.7777C15.2699 13.0356 15.125 13.3853 15.125 13.75C15.125 14.1147 15.2699 14.4644 15.5277 14.7223C15.7856 14.9801 16.1353 15.125 16.5 15.125M16.5 15.125V18.5625M11 6.875V3.4375M11 6.875C11.3647 6.875 11.7144 7.01987 11.9723 7.27773C12.2301 7.53559 12.375 7.88533 12.375 8.25C12.375 8.61467 12.2301 8.96441 11.9723 9.22227C11.7144 9.48013 11.3647 9.625 11 9.625M11 6.875C10.6353 6.875 10.2856 7.01987 10.0277 7.27773C9.76987 7.53559 9.625 7.88533 9.625 8.25C9.625 8.61467 9.76987 8.96441 10.0277 9.22227C10.2856 9.48013 10.6353 9.625 11 9.625M11 9.625V18.5625" stroke="#161E2D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                    <button type="submit" class="tf-btn btn-search primary">Search <i class="icon icon-search"></i></button>
                                </div>
                            </div>

                            {{-- Advanced Filters --}}
                            <div id="advancedFilters" class="wd-search-form {{ empty($filters['min_price']) && empty($filters['max_price']) && empty($filters['bedrooms']) && empty($filters['bathrooms']) ? 'd-none' : '' }}">
                                <div class="grid-2 group-box">
                                    <div class="group-select grid-2">
                                        <div class="box-select">
                                            <label class="title-select fw-6">Min Price</label>
                                            <input type="number" name="min_price" class="form-control" placeholder="Min" value="{{ $filters['min_price'] ?? '' }}">
                                        </div>
                                        <div class="box-select">
                                            <label class="title-select fw-6">Max Price</label>
                                            <input type="number" name="max_price" class="form-control" placeholder="Max" value="{{ $filters['max_price'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="group-select grid-2">
                                        <div class="box-select">
                                            <label class="title-select fw-6">Bedrooms</label>
                                            <select name="bedrooms" class="form-select">
                                                <option value="">Any</option>
                                                @for($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ ($filters['bedrooms'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}+</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="box-select">
                                            <label class="title-select fw-6">Bathrooms</label>
                                            <select name="bathrooms" class="form-select">
                                                <option value="">Any</option>
                                                @for($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ ($filters['bathrooms'] ?? '') == $i ? 'selected' : '' }}>{{ $i }}+</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="group-select">
                                        <div class="box-select">
                                            <label class="title-select fw-6">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="">All</option>
                                                @foreach($statuses as $value => $label)
                                                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Property Listing with Half Map --}}
    <section class="wrapper-layout layout-2">
        <div class="wrap-left">
            {{-- Title + View Toggle --}}
            <div class="box-title-listing">
                <h3 class="fw-8">Property Listing</h3>
                <div class="box-filter-tab">
                    <ul class="nav-tab-filter" role="tablist">
                        <li class="nav-tab-item" role="presentation">
                            <a href="#gridLayout" class="nav-link-item active" data-bs-toggle="tab">
                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.54883 5.90508C4.54883 5.1222 5.17272 4.5 5.91981 4.5C6.66686 4.5 7.2908 5.12221 7.2908 5.90508C7.2908 6.68801 6.66722 7.3101 5.91981 7.3101C5.17241 7.3101 4.54883 6.68801 4.54883 5.90508Z" stroke="#A3ABB0"/>
                                    <path d="M10.6045 5.90508C10.6045 5.12221 11.2284 4.5 11.9755 4.5C12.7229 4.5 13.3466 5.1222 13.3466 5.90508C13.3466 6.68789 12.7227 7.3101 11.9755 7.3101C11.2284 7.3101 10.6045 6.68794 10.6045 5.90508Z" stroke="#A3ABB0"/>
                                    <path d="M19.4998 5.90514C19.4998 6.68797 18.8757 7.31016 18.1288 7.31016C17.3818 7.31016 16.7578 6.68794 16.7578 5.90508C16.7578 5.12211 17.3813 4.5 18.1288 4.5C18.8763 4.5 19.4998 5.12215 19.4998 5.90514Z" stroke="#A3ABB0"/>
                                    <path d="M7.24249 12.0098C7.24249 12.7927 6.61849 13.4148 5.87133 13.4148C5.12411 13.4148 4.5 12.7926 4.5 12.0098C4.5 11.2268 5.12419 10.6045 5.87133 10.6045C6.61842 10.6045 7.24249 11.2267 7.24249 12.0098Z" stroke="#A3ABB0"/>
                                    <path d="M13.2976 12.0098C13.2976 12.7927 12.6736 13.4148 11.9266 13.4148C11.1795 13.4148 10.5557 12.7928 10.5557 12.0098C10.5557 11.2266 11.1793 10.6045 11.9266 10.6045C12.6741 10.6045 13.2976 11.2265 13.2976 12.0098Z" stroke="#A3ABB0"/>
                                    <path d="M19.4516 12.0098C19.4516 12.7928 18.828 13.4148 18.0807 13.4148C17.3329 13.4148 16.709 12.7926 16.709 12.0098C16.709 11.2268 17.3332 10.6045 18.0807 10.6045C18.8279 10.6045 19.4516 11.2266 19.4516 12.0098Z" stroke="#A3ABB0"/>
                                    <path d="M4.54297 18.0945C4.54297 17.3116 5.16709 16.6895 5.9143 16.6895C6.66137 16.6895 7.28523 17.3114 7.28523 18.0945C7.28523 18.8776 6.66139 19.4996 5.9143 19.4996C5.16714 19.4996 4.54297 18.8771 4.54297 18.0945Z" stroke="#A3ABB0"/>
                                    <path d="M10.5986 18.0945C10.5986 17.3116 11.2227 16.6895 11.97 16.6895C12.7169 16.6895 13.3409 17.3115 13.3409 18.0945C13.3409 18.8776 12.7169 19.4996 11.97 19.4996C11.2225 19.4996 10.5986 18.8772 10.5986 18.0945Z" stroke="#A3ABB0"/>
                                    <path d="M16.752 18.0945C16.752 17.3115 17.376 16.6895 18.1229 16.6895C18.8699 16.6895 19.4939 17.3115 19.4939 18.0945C19.4939 18.8776 18.8702 19.4996 18.1229 19.4996C17.376 19.4996 16.752 18.8772 16.752 18.0945Z" stroke="#A3ABB0"/>
                                </svg>
                            </a>
                        </li>
                        <li class="nav-tab-item" role="presentation">
                            <a href="#listLayout" class="nav-link-item" data-bs-toggle="tab">
                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.2016 17.8316H8.50246C8.0615 17.8316 7.7041 17.4742 7.7041 17.0332C7.7041 16.5923 8.0615 16.2349 8.50246 16.2349H19.2013C19.6423 16.2349 19.9997 16.5923 19.9997 17.0332C19.9997 17.4742 19.6426 17.8316 19.2016 17.8316Z" fill="#A3ABB0"/>
                                    <path d="M19.2016 12.8199H8.50246C8.0615 12.8199 7.7041 12.4625 7.7041 12.0215C7.7041 11.5805 8.0615 11.2231 8.50246 11.2231H19.2013C19.6423 11.2231 19.9997 11.5805 19.9997 12.0215C20 12.4625 19.6426 12.8199 19.2016 12.8199Z" fill="#A3ABB0"/>
                                    <path d="M19.2016 7.80913H8.50246C8.0615 7.80913 7.7041 7.45173 7.7041 7.01077C7.7041 6.5698 8.0615 6.2124 8.50246 6.2124H19.2013C19.6423 6.2124 19.9997 6.5698 19.9997 7.01077C19.9997 7.45173 19.6426 7.80913 19.2016 7.80913Z" fill="#A3ABB0"/>
                                    <path d="M5.0722 8.1444C5.66436 8.1444 6.1444 7.66436 6.1444 7.0722C6.1444 6.48004 5.66436 6 5.0722 6C4.48004 6 4 6.48004 4 7.0722C4 7.66436 4.48004 8.1444 5.0722 8.1444Z" fill="#A3ABB0"/>
                                    <path d="M5.0722 13.0941C5.66436 13.0941 6.1444 12.6141 6.1444 12.0219C6.1444 11.4297 5.66436 10.9497 5.0722 10.9497C4.48004 10.9497 4 11.4297 4 12.0219C4 12.6141 4.48004 13.0941 5.0722 13.0941Z" fill="#A3ABB0"/>
                                    <path d="M5.0722 18.0433C5.66436 18.0433 6.1444 17.5633 6.1444 16.9711C6.1444 16.379 5.66436 15.8989 5.0722 15.8989C4.48004 15.8989 4 16.379 4 16.9711C4 17.5633 4.48004 18.0433 5.0722 18.0433Z" fill="#A3ABB0"/>
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <form method="get" action="{{ route('properties.index') }}" class="d-inline">
                        @foreach($filters as $k => $v)
                            @if($k !== 'sort' && $v)
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <select name="sort" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Sort by (Default)</option>
                            <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        </select>
                    </form>
                </div>
            </div>

            {{-- Property Cards --}}
            <div class="flat-animate-tab">
                <div class="tab-content">
                    {{-- Grid View --}}
                    <div class="tab-pane active show" id="gridLayout" role="tabpanel">
                        <div class="row">
                            @forelse($properties as $property)
                                <div class="col-md-6">
                                    @include('frontend.properties._card-grid', ['property' => $property])
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-12">
                                        <i class="icon icon-house-fill" style="font-size:48px;color:#ccc;"></i>
                                        <h5 class="mt-8 text-variant-1">No properties found</h5>
                                        <p class="text-variant-1 mt-4">Try adjusting your filters or search criteria.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- List View --}}
                    <div class="tab-pane" id="listLayout" role="tabpanel">
                        <div class="row">
                            @forelse($properties as $property)
                                <div class="col-md-12">
                                    @include('frontend.properties._card-list', ['property' => $property])
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-12">
                                        <h5 class="text-variant-1">No properties found</h5>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            @if($properties->hasPages())
                <div class="flat-pagination">
                    {{ $properties->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        {{-- Map --}}
        <div class="wrap-right">
            <div id="map" class="top-map" style="width:100%;height:100%;min-height:600px;background:#e8e8e8;"></div>
        </div>
    </section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .leaflet-popup-content h6 { margin: 0 0 4px; font-size: 14px; }
    .leaflet-popup-content p { margin: 0; font-size: 12px; color: #666; }
    .leaflet-popup-content .price { color: #1563DF; font-weight: 700; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('map');
    if (!mapEl) return;

    @php
        $mapData = $properties->getCollection()->map(function($p) {
            return [
                'id' => $p->id,
                'title' => $p->title,
                'price' => $p->formatted_price,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'url' => route('properties.show', $p->slug),
                'image' => $p->getFirstMediaUrl('featured_image', 'thumb'),
                'type' => ucfirst($p->property_type),
            ];
        });
    @endphp
    var properties = @json($mapData);

    // Filter properties with coordinates
    var markers = properties.filter(function(p) { return p.lat && p.lng; });

    // Default center: Crete, Greece
    var defaultCenter = [35.24, 24.47];
    var defaultZoom = 9;

    if (markers.length > 0) {
        defaultCenter = [markers[0].lat, markers[0].lng];
        defaultZoom = 12;
    }

    var map = L.map('map').setView(defaultCenter, defaultZoom);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors &copy; <a href="https://carto.com">CARTO</a>'
    }).addTo(map);

    var bounds = [];
    markers.forEach(function(p) {
        var marker = L.marker([p.lat, p.lng]).addTo(map);
        var popup = '<div style="min-width:180px;">';
        if (p.image) popup += '<img src="' + p.image + '" style="width:100%;height:80px;object-fit:cover;border-radius:4px;margin-bottom:6px;">';
        popup += '<h6><a href="' + p.url + '">' + p.title + '</a></h6>';
        popup += '<p>' + p.type + '</p>';
        popup += '<p class="price">' + p.price + '</p></div>';
        marker.bindPopup(popup);
        bounds.push([p.lat, p.lng]);
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [30, 30] });
    }
});
</script>
@endpush

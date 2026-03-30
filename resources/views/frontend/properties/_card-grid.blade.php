{{-- Property Card - Grid Layout --}}
@php
    $imageUrl = $property->getFirstMediaUrl('featured_image', 'medium') ?: $property->getFirstMediaUrl('featured_image') ?: '/themes/kretaeiendom/images/home/house-7.jpg';
    $statusLabels = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold', 'rented' => 'Rented'];
    $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status));
    $detailUrl = route('properties.show', $property->slug);
@endphp
<div class="homelengo-box">
    <div class="archive-top">
        <a href="{{ $detailUrl }}" class="images-group">
            <div class="images-style">
                <img class="lazyload" data-src="{{ $imageUrl }}" src="{{ $imageUrl }}" alt="{{ $property->title }}">
            </div>
            <div class="top">
                <ul class="d-flex gap-6">
                    @if($property->featured)
                        <li class="flag-tag primary">Featured</li>
                    @endif
                    <li class="flag-tag style-1">{{ $statusLabel }}</li>
                </ul>
            </div>
            @if($property->address || $property->city)
                <div class="bottom">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 7C10 7.53043 9.78929 8.03914 9.41421 8.41421C9.03914 8.78929 8.53043 9 8 9C7.46957 9 6.96086 8.78929 6.58579 8.41421C6.21071 8.03914 6 7.53043 6 7C6 6.46957 6.21071 5.96086 6.58579 5.58579C6.96086 5.21071 7.46957 5 8 5C8.53043 5 9.03914 5.21071 9.41421 5.58579C9.78929 5.96086 10 6.46957 10 7Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 7C13 11.7613 8 14.5 8 14.5C8 14.5 3 11.7613 3 7C3 5.67392 3.52678 4.40215 4.46447 3.46447C5.40215 2.52678 6.67392 2 8 2C9.32608 2 10.5979 2.52678 11.5355 3.46447C12.4732 4.40215 13 5.67392 13 7Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ $property->address ?? '' }}{{ $property->address && $property->city ? ', ' : '' }}{{ $property->city ?? '' }}
                </div>
            @endif
        </a>
    </div>
    <div class="archive-bottom">
        <div class="content-top">
            <h6 class="text-capitalize"><a href="{{ $detailUrl }}" class="link">{{ $property->title }}</a></h6>
            <ul class="meta-list">
                @if($property->bedrooms)
                    <li class="item">
                        <i class="icon icon-bed"></i>
                        <span class="text-variant-1">Beds:</span>
                        <span class="fw-6">{{ $property->bedrooms }}</span>
                    </li>
                @endif
                @if($property->bathrooms)
                    <li class="item">
                        <i class="icon icon-bath"></i>
                        <span class="text-variant-1">Baths:</span>
                        <span class="fw-6">{{ $property->bathrooms }}</span>
                    </li>
                @endif
                @if($property->area)
                    <li class="item">
                        <i class="icon icon-sqft"></i>
                        <span class="text-variant-1">m²:</span>
                        <span class="fw-6">{{ number_format($property->area, 0) }}</span>
                    </li>
                @endif
            </ul>
        </div>
        <div class="content-bottom">
            <div class="d-flex gap-8 align-items-center">
                <span class="text-variant-1">{{ ucfirst($property->property_type) }}</span>
            </div>
            <h6 class="price">{{ $property->formatted_price }}</h6>
        </div>
    </div>
</div>

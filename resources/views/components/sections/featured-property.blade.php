@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Top Properties';
    $title = $content['title'] ?? 'Recommended For You';
    $propertyId = $content['property_id'] ?? null;
    $propertySource = $content['property_source'] ?? 'sales'; // sales or rentals

    $property = null;

    // Try to load a real property from DB
    if ($propertyId) {
        try {
            if ($propertySource === 'rentals' && class_exists(\Modules\RentalProperties\Models\RentalProperty::class)) {
                $property = \Modules\RentalProperties\Models\RentalProperty::find($propertyId);
            } else {
                $property = \Modules\Properties\Models\Property::find($propertyId);
            }
        } catch (\Exception $e) {}
    }

    // If no specific property, try to get the latest featured one
    if (!$property) {
        try {
            $property = \Modules\Properties\Models\Property::where('active', true)
                ->where('featured', true)
                ->latest()
                ->first();
        } catch (\Exception $e) {}
    }

    // If still no property, get any active property
    if (!$property) {
        try {
            $property = \Modules\Properties\Models\Property::where('active', true)->latest()->first();
        } catch (\Exception $e) {}
    }

    // Build display data from real property or fallbacks
    if ($property) {
        $isRental = $property instanceof \Modules\RentalProperties\Models\RentalProperty;
        $image = $property->getFirstMediaUrl('featured_image', 'large') ?: ($property->featured_image ?? '/themes/kretaeiendom/images/banner/img-w-text6.jpg');
        $propertyTitle = $property->title;
        $propertyLink = $isRental
            ? route('rental-properties.show', $property->slug)
            : route('properties.show', $property->slug);
        $beds = $property->bedrooms ?? '-';
        $baths = $property->bathrooms ?? '-';
        $sqft = $property->area ? number_format($property->area) : '-';
        $address = trim(implode(', ', array_filter([$property->address, $property->city, $property->country])));
        $price = $property->formatted_price ?? ($property->currency . ' ' . number_format($property->price, 2));
        $priceUnit = $isRental ? '/month' : '';
        $statusLabel = ucfirst(str_replace('_', ' ', $property->status ?? 'for_sale'));
        $typeLabel = ucfirst($property->property_type ?? 'Property');

        $tags = [];
        if ($property->featured ?? false) {
            $tags[] = ['label' => 'Featured', 'class' => 'primary'];
        }
        $tags[] = ['label' => $statusLabel, 'class' => 'style-1'];
    } else {
        // Static fallback
        $image = $content['image'] ?? '/themes/kretaeiendom/images/banner/img-w-text6.jpg';
        $propertyTitle = $content['property_title'] ?? 'Rancho Vista Verde, Santa Barbara';
        $propertyLink = $content['property_link'] ?? '#';
        $beds = $content['beds'] ?? '3';
        $baths = $content['baths'] ?? '2';
        $sqft = $content['sqft'] ?? '1150';
        $address = $content['address'] ?? '145 Brooklyn Ave, Califonia, New York';
        $price = $content['price'] ?? '$250,00';
        $priceUnit = $content['price_unit'] ?? '/month';
        $tags = $content['tags'] ?? [
            ['label' => 'Featured', 'class' => 'primary'],
            ['label' => 'For Sale', 'class' => 'style-1'],
        ];
    }
@endphp

<!-- Property -->
<section class="flat-section">
    <div class="container">
        <div class="flat-img-with-text style-3 bg-primary-new">
            <div class="content-left img-animation wow">
                <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="">
            </div>
            <div class="content-right">
                <div class="box-title wow fadeInUp">
                    <div class="text-subtitle text-primary">{{ $subtitle }}</div>
                    <h3 class="title mt-4">{{ $title }}</h3>
                </div>
                <div class="flat-property-box wow fadeInUp" data-wow-delay=".2s">
                    <div class="archive-top">
                        <ul class="d-flex gap-6">
                            @foreach($tags as $tag)
                                <li class="flag-tag {{ $tag['class'] }}">{{ $tag['label'] }}</li>
                            @endforeach
                        </ul>
                        <h4 class="title"><a href="{{ $propertyLink }}" class="link">{{ $propertyTitle }}</a></h4>
                        <ul class="meta-list">
                            <li class="item">
                                <i class="icon icon-bed"></i>
                                <span class="text-variant-1">Beds:</span>
                                <span class="fw-6">{{ $beds }}</span>
                            </li>
                            <li class="item">
                                <i class="icon icon-bath"></i>
                                <span class="text-variant-1">Baths:</span>
                                <span class="fw-6">{{ $baths }}</span>
                            </li>
                            <li class="item">
                                <i class="icon icon-sqft"></i>
                                <span class="text-variant-1">Sqft:</span>
                                <span class="fw-6">{{ $sqft }}</span>
                            </li>
                        </ul>
                        <div class="meta-location d-flex gap-4 align-items-center mt-16">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 7C10 7.53043 9.78929 8.03914 9.41421 8.41421C9.03914 8.78929 8.53043 9 8 9C7.46957 9 6.96086 8.78929 6.58579 8.41421C6.21071 8.03914 6 7.53043 6 7C6 6.46957 6.21071 5.96086 6.58579 5.58579C6.96086 5.21071 7.46957 5 8 5C8.53043 5 9.03914 5.21071 9.41421 5.58579C9.78929 5.96086 10 6.46957 10 7Z" stroke="#A3ABB0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M13 7C13 11.7613 8 14.5 8 14.5C8 14.5 3 11.7613 3 7C3 5.67392 3.52678 4.40215 4.46447 3.46447C5.40215 2.52678 6.67392 2 8 2C9.32608 2 10.5979 2.52678 11.5355 3.46447C12.4732 4.40215 13 5.67392 13 7Z" stroke="#A3ABB0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-variant-1">{{ $address ?: 'Address not available' }}</p>
                        </div>
                    </div>
                    <div class="archive-bottom">
                        <div>
                            <h4 class="d-inline-block">{{ $price }}</h4>
                            @if($priceUnit)
                                <span class="body-2 text-variant-1">{{ $priceUnit }}</span>
                            @endif
                        </div>
                        <div class="g-icon">
                            <a href="{{ $propertyLink }}" class="item-icon">
                                <span class="icon icon-arrRight"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

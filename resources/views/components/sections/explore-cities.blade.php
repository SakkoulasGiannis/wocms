@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Explore Cities';
    $title = $content['title'] ?? 'Our Location For You';
    $cities = $content['cities'] ?? [
        ['name' => 'Naperville', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-1.jpg', 'link' => '#'],
        ['name' => 'Pembroke Pines', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-2.jpg', 'link' => '#'],
        ['name' => 'Toledo', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-3.jpg', 'link' => '#'],
        ['name' => 'Orange', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-4.jpg', 'link' => '#'],
        ['name' => 'Fairfield', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-5.jpg', 'link' => '#'],
        ['name' => 'Naperville', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-6.jpg', 'link' => '#'],
        ['name' => 'Austin', 'property_count' => '321 Property', 'image' => '/themes/kretaeiendom/images/location/location-1.jpg', 'link' => '#'],
    ];
@endphp

<!-- Location -->
<section class="px-10">
    <div class="box-title text-center wow fadeInUp">
        <div class="text-subtitle text-primary">{{ $subtitle }}</div>
        <h3 class="mt-4 title">{{ $title }}</h3>
    </div>
    <div class="wow fadeInUp" data-wow-delay=".2s">
        <div dir="ltr" class="swiper tf-sw-location" data-preview="6" data-tablet="3" data-mobile-sm="2" data-mobile="1" data-space-lg="8" data-space-md="8" data-space="8" data-pagination="1" data-pagination-sm="2" data-pagination-md="3" data-pagination-lg="3">
            <div class="swiper-wrapper">
                @foreach($cities as $city)
                    <div class="swiper-slide">
                        <div class="box-location">
                            <a href="{{ $city['link'] }}" class="image img-style">
                                <img class="lazyload" data-src="{{ $city['image'] }}" src="{{ $city['image'] }}" alt="image-location">
                            </a>
                            <div class="content">
                                <div class="inner-left">
                                    <span class="sub-title fw-6">{{ $city['property_count'] }}</span>
                                    <h6 class="title text-line-clamp-1 link">{{ $city['name'] }}</h6>
                                </div>
                                <a href="{{ $city['link'] }}" class="box-icon line w-44 round"><i class="icon icon-arrow-right2"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-location text-center"></div>
        </div>
    </div>
</section>

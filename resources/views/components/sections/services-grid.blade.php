@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Services';
    $title = $content['title'] ?? 'Welcome to Kreta Eiendom';
    $items = $content['items'] ?? [
        ['image' => '/themes/kretaeiendom/images/service/home-1.png', 'title' => 'Buy A New Home', 'description' => 'Discover your dream home effortlessly. Explore diverse properties and expert guidance for a seamless buying experience.', 'link' => '#'],
        ['image' => '/themes/kretaeiendom/images/service/home-2.png', 'title' => 'Sell a Home', 'description' => 'Sell confidently with expert guidance and effective strategies, showcasing your property\'s best features for a successful sale.', 'link' => '#'],
        ['image' => '/themes/kretaeiendom/images/service/home-3.png', 'title' => 'Rent a Home', 'description' => 'Discover your perfect rental effortlessly. Explore a diverse variety of listings tailored precisely to suit your unique lifestyle needs.', 'link' => '#'],
    ];
@endphp

<section class="flat-spacing-service bg-primary-new">
    <div class="container">
        <div class="box-title text-center wow fadeInUp">
            <div class="text-subtitle text-primary">{{ $subtitle }}</div>
            <h3 class="mt-4 title">{{ $title }}</h3>
        </div>
        <div class="tf-grid-layout md-col-3 wow fadeInUp" data-wow-delay=".2s">
            @foreach($items as $item)
            <div class="box-service border-0">
                <div class="image">
                    <img class="lazyload" data-src="{{ $item['image'] }}" src="{{ $item['image'] }}" alt="{{ $item['title'] }}">
                </div>
                <div class="content">
                    <h5 class="title">{{ $item['title'] }}</h5>
                    <p class="description">{{ $item['description'] }}</p>
                    <a href="{{ $item['link'] ?? '#' }}" class="tf-btn btn-line">Learn More <span class="icon icon-arrow-right2"></span></a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

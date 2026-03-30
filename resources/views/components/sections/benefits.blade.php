@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Benifit';
    $title = $content['title'] ?? 'Why Choose HomeLengo';
    $description = $content['description'] ?? 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.';
    $image = $content['image'] ?? '/themes/kretaeiendom/images/banner/img-w-text5.jpg';
    $items = $content['items'] ?? [
        [
            'icon' => 'icon-proven',
            'title' => 'Proven Expertise',
            'description' => 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.',
            'link' => '#',
        ],
        [
            'icon' => 'icon-customize',
            'title' => 'Customized Solutions',
            'description' => 'We pride ourselves on crafting personalized strategies to match your unique goals, ensuring a seamless real estate journey.',
            'link' => '#',
        ],
        [
            'icon' => 'icon-partnership',
            'title' => 'Transparent Partnerships',
            'description' => 'Transparency is key in our client relationships. We prioritize clear communication and ethical practices, fostering trust and reliability throughout.',
            'link' => '#',
        ],
    ];
@endphp

<!-- Benefit -->
<section class="mx-5 bg-primary-new radius-30">
    <div class="flat-img-with-text">
        <div class="content-left img-animation wow">
            <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="">
        </div>
        <div class="content-right">
            <div class="box-title wow fadeInUp">
                <div class="text-subtitle text-primary">{{ $subtitle }}</div>
                <h3 class="title mt-4">{{ $title }}</h3>
                <p class="desc text-variant-1">{{ $description }}</p>
            </div>
            <div class="flat-service wow fadeInUp" data-wow-delay=".2s">
                @foreach($items as $item)
                    <a href="{{ $item['link'] }}" class="box-benefit hover-btn-view">
                        <div class="icon-box">
                            <span class="icon {{ $item['icon'] }}"></span>
                        </div>
                        <div class="content">
                            <h5 class="title">{{ $item['title'] }}</h5>
                            <p class="description">{{ $item['description'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

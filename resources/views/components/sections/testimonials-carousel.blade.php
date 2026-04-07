@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Testimonials';
    $title = $content['title'] ?? "What's people say's";
    $description = $content['description'] ?? 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.';
    $testimonials = $content['testimonials'] ?? [
        [
            'quote' => 'My experience with property management services has exceeded expectations. They efficiently manage properties with a professional and attentive approach in every situation. I feel reassured that any issue will be resolved promptly and effectively.',
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png1.png',
            'name' => 'Courtney Henry',
            'role' => 'CEO Themesflat',
            'stars' => 5,
        ],
        [
            'quote' => 'My experience with property management services has exceeded expectations. They efficiently manage properties with a professional and attentive approach in every situation. I feel reassured that any issue will be resolved promptly and effectively.',
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png2.png',
            'name' => 'Esther Howard',
            'role' => 'CEO Themesflat',
            'stars' => 5,
        ],
        [
            'quote' => 'My experience with property management services has exceeded expectations. They efficiently manage properties with a professional and attentive approach in every situation. I feel reassured that any issue will be resolved promptly and effectively.',
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png4.png',
            'name' => 'Annette Black',
            'role' => 'CEO Themesflat',
            'stars' => 5,
        ],
        [
            'quote' => 'My experience with property management services has exceeded expectations. They efficiently manage properties with a professional and attentive approach in every situation. I feel reassured that any issue will be resolved promptly and effectively.',
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png6.png',
            'name' => 'Bessie Cooper',
            'role' => 'CEO Themesflat',
            'stars' => 5,
        ],
    ];
@endphp

<!-- Testimonial -->
<section class="flat-section flat-testimonial pt-0">
    <div class="container">
        <div class="box-title px-15">
            <div class="text-center wow fadeInUp">
                <div class="text-subtitle text-primary">{{ $subtitle }}</div>
                <h3 class="title mt-4">{{ $title }}</h3>
                <p class="desc text-variant-1">{{ $description }}</p>
            </div>
        </div>
        <div dir="ltr" class="swiper tf-sw-testimonial" data-preview="3" data-tablet="2" data-mobile-sm="2" data-mobile="1" data-space="15" data-space-md="30" data-space-lg="30" data-centered="false" data-loop="false">
            <div class="swiper-wrapper wow fadeInUp" data-wow-delay=".2s">
                @foreach($testimonials as $testimonial)
                    <div class="swiper-slide">
                        <div class="box-tes-item style-2">
                            <span class="icon icon-quote"></span>
                            <p class="note body-2">
                                "{{ $testimonial['quote'] }}"
                            </p>
                            <div class="box-avt d-flex align-items-center gap-12">
                                <div class="avatar avt-60 round">
                                    <img src="{{ $testimonial['avatar'] }}" alt="avatar">
                                </div>
                                <div class="info">
                                    <h6>{{ $testimonial['name'] }}</h6>
                                    <p class="caption-2 text-variant-1 mt-4">{{ $testimonial['role'] }}</p>
                                    <ul class="list-star">
                                        @for($i = 0; $i < ($testimonial['stars'] ?? 5); $i++)
                                            <li class="icon icon-star"></li>
                                        @endfor
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-testimonial text-center"></div>
        </div>
    </div>
</section>
<!-- End Testimonial -->

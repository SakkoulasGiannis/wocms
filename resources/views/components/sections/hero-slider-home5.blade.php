@props(['content' => [], 'settings' => []])

@php
    // Defensive helper: ensure value is array (JSON strings may come from form/editor)
    $ensureArray = function ($val, $default = []) {
        if (is_array($val)) return $val;
        if (is_string($val) && $val !== '') { $d = json_decode($val, true); if (is_array($d)) return $d; }
        return $default;
    };

    // Fallbacks when no slider is selected
    $defaultHeading = $content['heading'] ?? 'Indulge in Your';
    $animatedWords = $ensureArray($content['animated_words'] ?? null, ['Sanctuary', 'Safe House']);
    $defaultSubtitle = $content['subtitle'] ?? 'Discover your private oasis, where every corner, from the spacious garden to the relaxing pool, is crafted for your comfort and enjoyment.';
    $categories = $ensureArray($content['categories'] ?? null, [
        ['icon' => 'icon-house-fill', 'label' => 'Houses', 'url' => '#'],
        ['icon' => 'icon-villa-fill', 'label' => 'Villa', 'url' => '#'],
        ['icon' => 'icon-office-fill', 'label' => 'Office', 'url' => '#'],
        ['icon' => 'icon-apartment', 'label' => 'Apartments', 'url' => '#'],
    ]);

    // Try to load slides from Slider module
    $sliderSlug = $content['slider_slug'] ?? null;
    $sliderId = $content['slider_id'] ?? null;
    $sliderSlides = collect();

    if ($sliderSlug || $sliderId) {
        try {
            $sliderModel = $sliderId
                ? \Modules\Slider\Models\Slider::with(['slides' => fn($q) => $q->where('is_active', true)->orderBy('order')])->where('is_active', true)->find($sliderId)
                : \Modules\Slider\Models\Slider::with(['slides' => fn($q) => $q->where('is_active', true)->orderBy('order')])->where('slug', $sliderSlug)->where('is_active', true)->first();

            if ($sliderModel) {
                $sliderSlides = $sliderModel->slides;
            }
        } catch (\Throwable $e) {}
    }

    $useSliderData = $sliderSlides->isNotEmpty();

    if ($useSliderData) {
        $bgSlides = $sliderSlides->map(fn($s) => [
            'url' => $s->getFirstMediaUrl('image') ?: '/themes/kretaeiendom/images/slider/slider-5.jpg',
            'media_type' => $s->media_type ?? 'image',
            'video_url' => $s->video_url,
            'video_file_url' => $s->getFirstMediaUrl('video'),
            'title' => $s->title,
            'description' => $s->description,
            'link' => $s->link,
            'button_text' => $s->button_text,
        ])->toArray();
        $thumbSlides = $sliderSlides->map(fn($s) => $s->getFirstMediaUrl('image', 'thumb') ?: $s->getFirstMediaUrl('image') ?: '/themes/kretaeiendom/images/slider/slider-pagi.jpg')->toArray();
    } else {
        $defaultImages = $ensureArray($content['bg_slides'] ?? null, [
            '/themes/kretaeiendom/images/slider/slider-5.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-1.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-2.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-3.jpg',
        ]);
        $bgSlides = array_map(fn($url) => [
            'url' => $url, 'media_type' => 'image', 'video_url' => null,
            'video_file_url' => null, 'title' => null, 'description' => null,
            'link' => null, 'button_text' => null,
        ], $defaultImages);
        $thumbSlides = $ensureArray($content['thumb_slides'] ?? null, [
            '/themes/kretaeiendom/images/slider/slider-pagi.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi2.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi3.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi4.jpg',
        ]);
    }
@endphp

<section class="flat-slider home-5">
    <div class="wrap-slider-swiper">
        {{-- Background slides --}}
        <div dir="ltr" class="swiper-container thumbs-swiper-column">
            <div class="swiper-wrapper">
                @foreach($bgSlides as $slide)
                <div class="swiper-slide">
                    <div class="box-img">
                        @if(($slide['media_type'] ?? 'image') === 'youtube' && $slide['video_url'])
                            @php
                                preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $slide['video_url'], $ytMatch);
                                $ytId = $ytMatch[1] ?? null;
                            @endphp
                            @if($ytId)
                                <div class="video-slide" style="position:relative;width:100%;height:100%;overflow:hidden;">
                                    <iframe src="https://www.youtube.com/embed/{{ $ytId }}?autoplay=0&mute=1&controls=0&loop=1&playlist={{ $ytId }}&rel=0"
                                            style="position:absolute;top:50%;left:50%;width:120%;height:120%;transform:translate(-50%,-50%);border:0;pointer-events:none;"
                                            allow="autoplay;encrypted-media" allowfullscreen></iframe>
                                    @if($slide['url'] ?? null)
                                        <img src="{{ $slide['url'] }}" alt="poster" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-1;">
                                    @endif
                                </div>
                            @endif
                        @elseif(($slide['media_type'] ?? 'image') === 'video' && ($slide['video_file_url'] ?? null))
                            <video autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover;"
                                   @if($slide['url'] ?? null) poster="{{ $slide['url'] }}" @endif>
                                <source src="{{ $slide['video_file_url'] }}" type="video/mp4">
                            </video>
                        @else
                            <img src="{{ $slide['url'] ?? '' }}" alt="{{ $slide['title'] ?? 'slider' }}">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Content overlay - synced with slides --}}
        <div class="box-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        @if($useSliderData)
                            {{-- Per-slide content: synced swiper that changes text with each slide --}}
                            <div dir="ltr" class="swiper-container thumbs-swiper-content">
                                <div class="swiper-wrapper">
                                    @foreach($bgSlides as $slide)
                                    <div class="swiper-slide">
                                        <div class="slider-content">
                                            <div class="heading">
                                                @if($slide['title'])
                                                    <h1 class="title-large title text-white wow fadeIn" data-wow-delay=".2s">
                                                        {{ $slide['title'] }}
                                                    </h1>
                                                @endif
                                                @if($slide['description'])
                                                    <p class="subtitle text-white body-2 mt-8">
                                                        {{ $slide['description'] }}
                                                    </p>
                                                @endif
                                            </div>
                                            @if($slide['link'])
                                                <div class="wrap-search-link mt-16">
                                                    <a href="{{ $slide['link'] }}" class="tf-btn primary">
                                                        {{ $slide['button_text'] ?? 'Learn More' }}
                                                        <span class="icon icon-arrow-right2"></span>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Static content with animated words --}}
                            <div class="slider-content">
                                <div class="heading">
                                    <h1 class="title-large title text-white wow fadeIn animationtext clip"
                                        data-wow-delay=".2s" data-wow-duration="2000ms">
                                        {{ $defaultHeading }}
                                        <br>
                                        <span class="tf-text s1 cd-words-wrapper">
                                            @foreach($animatedWords as $i => $word)
                                            <span class="item-text {{ $i === 0 ? 'is-visible' : 'is-hidden' }}">{{ $word }}</span>
                                            @endforeach
                                        </span>
                                    </h1>
                                    <p class="subtitle text-white body-2 wow fadeInUp" data-wow-delay=".2s">
                                        {{ $defaultSubtitle }}
                                    </p>
                                </div>
                                <div class="wrap-search-link">
                                    <div class="categories-list style-2">
                                        @foreach($categories as $cat)
                                        <a href="{{ $cat['url'] }}"><i class="icon {{ $cat['icon'] }}"></i> {{ $cat['label'] }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-6">
                        {{-- Thumbnail pagination --}}
                        <div class="swiper-container thumbs-swiper-column1 swiper-pagination5">
                            <div class="swiper-wrapper">
                                @foreach($thumbSlides as $thumb)
                                <div class="swiper-slide">
                                    <div class="image-detail">
                                        <img src="{{ is_array($thumb) ? ($thumb['url'] ?? '') : $thumb }}" alt="thumbnail">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="overlay"></div>
</section>

@if($useSliderData)
{{-- Sync text swiper with background swiper --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for swipers to initialize, then sync content swiper with bg swiper
    var checkSwipers = setInterval(function() {
        var bgSwiper = document.querySelector('.thumbs-swiper-column');
        if (bgSwiper && bgSwiper.swiper) {
            clearInterval(checkSwipers);

            var contentEl = document.querySelector('.thumbs-swiper-content');
            if (contentEl) {
                var contentSwiper = new Swiper(contentEl, {
                    effect: 'fade',
                    fadeEffect: { crossFade: true },
                    allowTouchMove: false,
                    speed: 600,
                });
                // Sync: when bg slides change, change content too
                bgSwiper.swiper.on('slideChange', function() {
                    contentSwiper.slideTo(bgSwiper.swiper.realIndex);
                });
            }
        }
    }, 200);
});
</script>
@endpush
@endif

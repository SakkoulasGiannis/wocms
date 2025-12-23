@props(['content', 'settings'])

<section class="relative h-screen">
    <div class="swiper h-full" data-autoplay="{{ $settings['autoplay'] ?? true ? 'true' : 'false' }}" data-interval="{{ $settings['interval'] ?? 5000 }}">
        <div class="swiper-wrapper">
            @foreach(($content['slides'] ?? []) as $slide)
                <div class="swiper-slide relative">
                    @if(!empty($slide['image']))
                        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $slide['image'] }}');"></div>
                        <div class="absolute inset-0 bg-black opacity-50"></div>
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-blue-800"></div>
                    @endif

                    <div class="relative h-full flex items-center justify-center">
                        <div class="container mx-auto px-4 text-center text-white">
                            @if(!empty($slide['subheading']))
                                <p class="text-xl mb-4 opacity-90">{{ $slide['subheading'] }}</p>
                            @endif
                            <h2 class="text-5xl md:text-6xl font-bold mb-6">{{ $slide['heading'] ?? '' }}</h2>
                            @if(!empty($slide['text']))
                                <p class="text-xl mb-8 max-w-2xl mx-auto">{{ $slide['text'] }}</p>
                            @endif
                            @if(!empty($slide['button_text']))
                                <a href="{{ $slide['button_url'] ?? '#' }}" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                                    {{ $slide['button_text'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($settings['show_arrows'] ?? true)
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        @endif

        @if($settings['show_dots'] ?? true)
            <div class="swiper-pagination"></div>
        @endif
    </div>
</section>

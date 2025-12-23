@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="hero-slider relative overflow-hidden">
    <div class="slides-container">
        @foreach($content['slides'] ?? [] as $index => $slide)
            <div class="slide {{ $index === 0 ? 'active' : '' }} relative h-screen flex items-center justify-center bg-cover bg-center"
                 @if(!empty($slide['image']))
                 style="background-image: url('{{ $slide['image'] }}')"
                 @endif>

                <div class="absolute inset-0 bg-black bg-opacity-40"></div>

                <div class="container mx-auto px-4 relative z-10 text-center text-white">
                    @if(!empty($slide['subheading']))
                        <p class="text-lg md:text-xl mb-4 font-light">{{ $slide['subheading'] }}</p>
                    @endif

                    @if(!empty($slide['heading']))
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">{{ $slide['heading'] }}</h1>
                    @endif

                    @if(!empty($slide['text']))
                        <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto">{{ $slide['text'] }}</p>
                    @endif

                    @if(!empty($slide['button_text']) && !empty($slide['button_url']))
                        <a href="{{ $slide['button_url'] }}"
                           class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition">
                            {{ $slide['button_text'] }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if(count($content['slides'] ?? []) > 1)
        @if($settings['show_arrows'] ?? true)
            <button class="slider-prev absolute left-4 top-1/2 -translate-y-1/2 bg-white/30 hover:bg-white/50 text-white p-3 rounded-full z-20">
                ←
            </button>
            <button class="slider-next absolute right-4 top-1/2 -translate-y-1/2 bg-white/30 hover:bg-white/50 text-white p-3 rounded-full z-20">
                →
            </button>
        @endif

        @if($settings['show_dots'] ?? true)
            <div class="slider-dots absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                @foreach($content['slides'] ?? [] as $index => $slide)
                    <button class="dot w-3 h-3 rounded-full {{ $index === 0 ? 'bg-white' : 'bg-white/50' }}"
                            data-slide="{{ $index }}"></button>
                @endforeach
            </div>
        @endif
    @endif
</section>

@if(count($content['slides'] ?? []) > 1 && ($settings['autoplay'] ?? true))
    <script>
        // Simple slider script - can be enhanced with Alpine.js or similar
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const interval = {{ $settings['interval'] ?? 5000 }};

        function showSlide(n) {
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('bg-white'));
            dots.forEach(d => d.classList.add('bg-white/50'));

            currentSlide = (n + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('bg-white');
            dots[currentSlide].classList.remove('bg-white/50');
        }

        document.querySelector('.slider-next')?.addEventListener('click', () => showSlide(currentSlide + 1));
        document.querySelector('.slider-prev')?.addEventListener('click', () => showSlide(currentSlide - 1));
        dots.forEach((dot, i) => dot.addEventListener('click', () => showSlide(i)));

        @if($settings['autoplay'] ?? true)
        setInterval(() => showSlide(currentSlide + 1), interval);
        @endif
    </script>

    <style>
        .slide {
            display: none;
        }
        .slide.active {
            display: flex;
        }
    </style>
@endif

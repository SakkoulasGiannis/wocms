@props(['content' => [], 'settings' => []])

@php
    $slides = [];

    // Priority 1: Load from Slider module if slider_id is set
    $sliderId = $content['slider_id'] ?? null;
    if ($sliderId && class_exists(\Modules\Slider\Models\Slider::class)) {
        try {
            $slider = \Modules\Slider\Models\Slider::with(['slides' => fn($q) => $q->where('is_active', true)->orderBy('order')])
                ->where('is_active', true)
                ->find($sliderId);

            if ($slider && $slider->slides->isNotEmpty()) {
                $slides = $slider->slides->map(fn($s) => [
                    'image'       => $s->getFirstMediaUrl('image') ?: '',
                    'heading'     => $s->title ?? '',
                    'subheading'  => $s->subtitle ?? '',
                    'text'        => $s->description ?? '',
                    'button_text' => $s->button_text ?? '',
                    'button_url'  => $s->link ?? '#',
                ])->toArray();
            }
        } catch (\Throwable $e) {}
    }

    // Priority 2: Inline slides from section content
    if (empty($slides)) {
        $slides = $content['slides'] ?? [];
        if (is_string($slides)) {
            $decoded = json_decode($slides, true);
            $slides = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($slides)) {
            $slides = [];
        }
    }

    // Merge slider model settings (slider settings are defaults; section settings override)
    $sliderSettings = [];
    if (isset($slider) && $slider) {
        $sliderSettings = $slider->settings ?? [];
    }

    $autoplay        = (bool)   ($settings['autoplay']         ?? $sliderSettings['autoplay']          ?? true);
    $interval        = (int)    ($settings['interval']         ?? $sliderSettings['autoplay_interval'] ?? 5000);
    $showArrows      = (bool)   ($settings['show_arrows']      ?? $sliderSettings['show_arrows']       ?? true);
    $showDots        = (bool)   ($settings['show_dots']        ?? $sliderSettings['show_dots']         ?? true);
    $height          =           $settings['height']           ?? $sliderSettings['height']            ?? 'h-screen';
    $imageFit        =           $settings['image_fit']        ?? $sliderSettings['image_fit']         ?? 'cover';
    $overlayColor    =           $settings['overlay_color']    ?? $sliderSettings['overlay_color']     ?? 'black';
    $overlayOpacity  =           $settings['overlay_opacity']  ?? $sliderSettings['overlay_opacity']   ?? '0.5';
    $transitionEffect =          $settings['transition_effect'] ?? $sliderSettings['transition_effect'] ?? 'fade';
    $textPosition    =           $settings['text_position']    ?? $sliderSettings['text_position']     ?? 'center';
    $contentMaxWidth =           $settings['content_max_width'] ?? $sliderSettings['content_max_width'] ?? 'max-w-4xl';

    // Resolve CSS classes from settings values
    $imageFitClass = match($imageFit) {
        'contain' => 'object-contain',
        'fill'    => 'object-fill',
        default   => 'object-cover',
    };

    $overlayColorClass = match($overlayColor) {
        'brand' => 'bg-brand',
        'white' => 'bg-white',
        default => 'bg-black',
    };

    $textAlignClass = match($textPosition) {
        'left'  => 'text-left',
        'right' => 'text-right',
        default => 'text-center',
    };

    $justifyClass = match($textPosition) {
        'left'  => 'justify-start',
        'right' => 'justify-end',
        default => 'justify-center',
    };

    $transitionClass = $transitionEffect === 'slide'
        ? 'transition-transform duration-700 ease-in-out'
        : 'transition-opacity duration-700 ease-in-out';
@endphp

@if(count($slides) > 0)
<section
    class="relative {{ $height }} overflow-hidden"
    x-data="{
        current: 0,
        total: {{ count($slides) }},
        autoplay: {{ $autoplay ? 'true' : 'false' }},
        interval: {{ $interval }},
        timer: null,
        init() {
            if (this.autoplay && this.total > 1) this.startAutoplay();
        },
        next() {
            this.current = (this.current + 1) % this.total;
            this.restartAutoplay();
        },
        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
            this.restartAutoplay();
        },
        goTo(i) {
            this.current = i;
            this.restartAutoplay();
        },
        startAutoplay() {
            this.timer = setInterval(() => this.next(), this.interval);
        },
        restartAutoplay() {
            clearInterval(this.timer);
            if (this.autoplay && this.total > 1) this.startAutoplay();
        }
    }"
    @mouseenter="clearInterval(timer)"
    @mouseleave="if(autoplay && total > 1) startAutoplay()"
>
    {{-- Slides --}}
    @foreach($slides as $i => $slide)
        <div
            class="absolute inset-0 {{ $transitionClass }}"
            :class="current === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
        >
            {{-- Background --}}
            @if(!empty($slide['image']))
                <div class="absolute inset-0 w-full h-full">
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['heading'] ?? '' }}" class="w-full h-full {{ $imageFitClass }}">
                </div>
                @if((float) $overlayOpacity > 0)
                    <div class="absolute inset-0 {{ $overlayColorClass }}" style="opacity: {{ $overlayOpacity }}"></div>
                @endif
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-brand to-brand-dark"></div>
            @endif

            {{-- Content --}}
            <div class="relative flex h-full items-center {{ $justifyClass }}">
                <div class="mx-auto {{ $contentMaxWidth }} px-6 {{ $textAlignClass }} text-white">
                    @if(!empty($slide['subheading']))
                        <p
                            class="mb-4 text-lg opacity-90 md:text-xl"
                            x-show="current === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500 delay-200"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        >
                            {{ $slide['subheading'] }}
                        </p>
                    @endif

                    <h2
                        class="text-4xl font-bold leading-tight md:text-6xl"
                        x-show="current === {{ $i }}"
                        x-transition:enter="transition ease-out duration-500 delay-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                    >
                        {{ $slide['heading'] ?? '' }}
                    </h2>

                    @if(!empty($slide['text']))
                        <p
                            class="mx-auto mb-8 mt-4 max-w-2xl text-lg opacity-90 md:text-xl"
                            x-show="current === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500 delay-[400ms]"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        >
                            {{ $slide['text'] }}
                        </p>
                    @endif

                    @if(!empty($slide['button_text']))
                        <div
                            x-show="current === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500 delay-500"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                        >
                            <a
                                href="{{ $slide['button_url'] ?? '#' }}"
                                class="mt-6 inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold text-brand shadow-lg transition-colors hover:bg-brand hover:text-white"
                            >
                                {{ $slide['button_text'] }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- Arrows --}}
    @if($showArrows && count($slides) > 1)
        <button
            type="button"
            @click="prev()"
            class="absolute left-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition-colors hover:bg-white/40"
            aria-label="Previous slide"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button
            type="button"
            @click="next()"
            class="absolute right-4 top-1/2 z-20 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur-sm transition-colors hover:bg-white/40"
            aria-label="Next slide"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    @endif

    {{-- Dots --}}
    @if($showDots && count($slides) > 1)
        <div class="absolute bottom-8 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2.5">
            @foreach($slides as $i => $slide)
                <button
                    type="button"
                    @click="goTo({{ $i }})"
                    :class="current === {{ $i }} ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/70'"
                    class="h-2.5 rounded-full transition-all duration-300"
                    aria-label="Go to slide {{ $i + 1 }}"
                ></button>
            @endforeach
        </div>
    @endif
</section>
@endif

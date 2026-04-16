@props(['content', 'settings' => []])

@php
    $heading         = $content['heading'] ?? 'Ready to Get Started?';
    $subheading      = $content['subheading'] ?? '';
    $text            = $content['text'] ?? '';
    $bgImage         = $content['background_image'] ?? '';
    $bgColor         = $content['bg_color'] ?? 'bg-gradient-to-br from-blue-600 to-purple-700';
    $overlayOpacity  = $content['bg_overlay_opacity'] ?? '0.55';
    $textAlign       = $content['text_align'] ?? 'text-center';
    $sectionClass    = $content['section_class'] ?? 'py-24';
    $headingClass    = $content['heading_class'] ?? 'text-4xl md:text-5xl font-bold';
    $btn1Text        = $content['button_text'] ?? '';
    $btn1Url         = $content['button_url'] ?? '#';
    $btn1Class       = $content['button_class'] ?? 'bg-white text-blue-700 hover:bg-gray-100';
    $btn2Text        = $content['button2_text'] ?? '';
    $btn2Url         = $content['button2_url'] ?? '#';
    $btn2Class       = $content['button2_class'] ?? 'border-2 border-white text-white hover:bg-white/10';
    $hasButtons      = $btn1Text || $btn2Text;
    $alignItems      = match($textAlign) {
        'text-left'  => 'items-start',
        'text-right' => 'items-end',
        default      => 'items-center',
    };
@endphp

<section class="relative overflow-hidden {{ $sectionClass }}">

    {{-- Background layer --}}
    @if($bgImage)
        <div
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('{{ $bgImage }}');"
            aria-hidden="true"
        ></div>
        <div
            class="absolute inset-0 bg-black"
            style="opacity: {{ $overlayOpacity }};"
            aria-hidden="true"
        ></div>
    @else
        <div class="absolute inset-0 {{ $bgColor }}" aria-hidden="true"></div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 container mx-auto px-6">
        <div class="flex flex-col {{ $alignItems }} gap-6 max-w-3xl {{ $textAlign === 'text-center' ? 'mx-auto' : ($textAlign === 'text-right' ? 'ml-auto' : '') }}">

            {{-- Heading --}}
            <h2 class="{{ $headingClass }} text-white leading-tight {{ $textAlign }}">
                {{ $heading }}
            </h2>

            {{-- Subheading --}}
            @if($subheading)
                <p class="text-xl font-medium text-white/90 {{ $textAlign }}">
                    {{ $subheading }}
                </p>
            @endif

            {{-- Body text --}}
            @if($text)
                <p class="text-lg text-white/80 leading-relaxed max-w-2xl {{ $textAlign }}">
                    {{ $text }}
                </p>
            @endif

            {{-- Buttons --}}
            @if($hasButtons)
                <div class="flex flex-wrap gap-4 {{ $textAlign === 'text-center' ? 'justify-center' : ($textAlign === 'text-right' ? 'justify-end' : 'justify-start') }} mt-2">

                    @if($btn1Text)
                        <a
                            href="{{ $btn1Url }}"
                            class="inline-flex items-center justify-center px-8 py-3 rounded-lg font-semibold text-base transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 {{ $btn1Class }}"
                        >
                            {{ $btn1Text }}
                        </a>
                    @endif

                    @if($btn2Text)
                        <a
                            href="{{ $btn2Url }}"
                            class="inline-flex items-center justify-center px-8 py-3 rounded-lg font-semibold text-base transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white/50 {{ $btn2Class }}"
                        >
                            {{ $btn2Text }}
                        </a>
                    @endif

                </div>
            @endif

        </div>
    </div>

</section>

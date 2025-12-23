{{-- Hero Simple Section --}}
@php
    $content = is_array($section->content) ? $section->content : json_decode($section->content, true);
    $settings = is_array($section->settings) ? $section->settings : json_decode($section->settings, true);
    $height = $settings['height'] ?? 'screen';
    $overlay = $settings['overlay_opacity'] ?? 0.5;
    $alignment = $settings['text_alignment'] ?? 'center';
@endphp

<section class="hero-simple relative bg-cover bg-center h-{{ $height === 'screen' ? 'screen' : '96' }}"
         @if(!empty($content['background_image'])) style="background-image: url('{{ $content['background_image'] }}');" @endif>

    @if(!empty($content['background_image']))
        <div class="absolute inset-0 bg-black" style="opacity: {{ $overlay }};"></div>
    @endif

    <div class="relative container mx-auto px-4 h-full flex items-center justify-{{ $alignment === 'left' ? 'start' : ($alignment === 'right' ? 'end' : 'center') }}">
        <div class="text-{{ $alignment }} max-w-3xl {{ $alignment === 'center' ? 'mx-auto' : '' }}">
            @if(!empty($content['heading']))
                <h1 class="text-4xl md:text-6xl font-bold {{ !empty($content['background_image']) ? 'text-white' : 'text-gray-900' }} mb-4">
                    {{ $content['heading'] }}
                </h1>
            @endif

            @if(!empty($content['subheading']))
                <p class="text-xl md:text-2xl {{ !empty($content['background_image']) ? 'text-gray-200' : 'text-gray-700' }} mb-6">
                    {{ $content['subheading'] }}
                </p>
            @endif

            @if(!empty($content['text']))
                <p class="text-lg {{ !empty($content['background_image']) ? 'text-gray-300' : 'text-gray-600' }} mb-8">
                    {{ $content['text'] }}
                </p>
            @endif

            @if(!empty($content['button_text']) && !empty($content['button_url']))
                <a href="{{ $content['button_url'] }}"
                   class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    {{ $content['button_text'] }}
                </a>
            @endif
        </div>
    </div>
</section>

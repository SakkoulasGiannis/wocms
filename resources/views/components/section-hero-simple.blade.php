@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="hero-simple relative bg-cover bg-center"
         style="height: {{ $settings['height'] === 'screen' ? '100vh' : '500px' }};
         @if(!empty($content['background_image']))
         background-image: url('{{ $content['background_image'] }}');
         @endif">

    <div class="absolute inset-0 bg-black"
         style="opacity: {{ $settings['overlay_opacity'] ?? 0.5 }}"></div>

    <div class="container mx-auto px-4 h-full relative z-10 flex items-center">
        <div class="text-white {{ $settings['text_alignment'] === 'center' ? 'text-center mx-auto' : '' }} max-w-3xl">
            @if(!empty($content['subheading']))
                <p class="text-lg md:text-xl mb-4 font-light">{{ $content['subheading'] }}</p>
            @endif

            @if(!empty($content['heading']))
                <h1 class="text-4xl md:text-6xl font-bold mb-6">{{ $content['heading'] }}</h1>
            @endif

            @if(!empty($content['text']))
                <p class="text-lg md:text-xl mb-8">{{ $content['text'] }}</p>
            @endif

            @if(!empty($content['button_text']) && !empty($content['button_url']))
                <a href="{{ $content['button_url'] }}"
                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition">
                    {{ $content['button_text'] }}
                </a>
            @endif
        </div>
    </div>
</section>

@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="call-to-action py-20 relative bg-cover bg-center"
         @if(!empty($content['background_image']))
         style="background-image: url('{{ $content['background_image'] }}')"
         @endif>

    <div class="absolute inset-0 bg-blue-900"
         style="opacity: {{ $settings['overlay_opacity'] ?? 0.7 }}"></div>

    <div class="container mx-auto px-4 relative z-10 text-center text-white">
        @if(!empty($content['heading']))
            <h2 class="text-4xl md:text-5xl font-bold mb-6">{{ $content['heading'] }}</h2>
        @endif

        @if(!empty($content['text']))
            <p class="text-xl md:text-2xl mb-8 max-w-2xl mx-auto">{{ $content['text'] }}</p>
        @endif

        @if(!empty($content['button_text']) && !empty($content['button_url']))
            <a href="{{ $content['button_url'] }}"
               class="inline-block bg-white text-blue-900 hover:bg-gray-100 px-8 py-4 rounded-lg text-lg font-semibold transition">
                {{ $content['button_text'] }}
            </a>
        @endif
    </div>
</section>

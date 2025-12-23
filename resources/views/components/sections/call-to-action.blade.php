@props(['content', 'settings'])

@php
    $style = $settings['style'] ?? 'centered';
    $overlayOpacity = $settings['overlay_opacity'] ?? 0.7;
@endphp

<section class="relative py-20">
    @if(!empty($content['background_image']))
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $content['background_image'] }}');"></div>
        <div class="absolute inset-0 bg-black" style="opacity: {{ $overlayOpacity }};"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600"></div>
    @endif

    <div class="relative container mx-auto px-4 text-center text-white z-10">
        <h2 class="text-4xl font-bold mb-4">{{ $content['heading'] ?? 'Ready to Get Started?' }}</h2>
        @if(!empty($content['text']))
            <p class="text-xl mb-8 max-w-2xl mx-auto">{{ $content['text'] }}</p>
        @endif
        @if(!empty($content['button_text']))
            <a href="{{ $content['button_url'] ?? '#' }}" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                {{ $content['button_text'] }}
            </a>
        @endif
    </div>
</section>

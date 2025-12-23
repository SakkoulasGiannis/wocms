@props(['content', 'settings'])

@php
    $height = $settings['height'] ?? 'screen';
    $heightClass = $height === 'screen' ? 'h-screen' : 'h-96';
    $alignment = $settings['text_alignment'] ?? 'center';
    $alignmentClass = match($alignment) {
        'left' => 'text-left items-start',
        'right' => 'text-right items-end',
        default => 'text-center items-center',
    };
    $overlayOpacity = $settings['overlay_opacity'] ?? 0.5;
@endphp

<section class="relative {{ $heightClass }} flex items-center justify-center">
    <!-- Background Image -->
    @if(!empty($content['background_image']))
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $content['background_image'] }}');"></div>
        <div class="absolute inset-0 bg-black" style="opacity: {{ $overlayOpacity }};"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-blue-800"></div>
    @endif

    <!-- Content -->
    <div class="relative container mx-auto px-4 z-10">
        <div class="flex flex-col {{ $alignmentClass }} max-w-4xl mx-auto text-white">
            @if(!empty($content['subheading']))
                <p class="text-xl mb-4 opacity-90">{{ $content['subheading'] }}</p>
            @endif
            <h1 class="text-5xl md:text-6xl font-bold mb-6">{{ $content['heading'] ?? 'Welcome' }}</h1>
            @if(!empty($content['text']))
                <p class="text-xl mb-8 opacity-90">{{ $content['text'] }}</p>
            @endif
            @if(!empty($content['button_text']))
                <div>
                    <a href="{{ $content['button_url'] ?? '#' }}" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                        {{ $content['button_text'] }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>

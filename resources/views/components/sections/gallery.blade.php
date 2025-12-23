@props(['content', 'settings'])

@php
    $columns = $settings['columns'] ?? 3;
    $lightbox = $settings['enable_lightbox'] ?? true;
@endphp

<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        <div class="grid md:grid-cols-{{ $columns }} gap-4">
            @foreach(($content['images'] ?? []) as $image)
                <div class="relative group overflow-hidden rounded-lg">
                    <img src="{{ $image['url'] ?? '' }}" alt="{{ $image['caption'] ?? '' }}" class="w-full h-64 object-cover group-hover:scale-110 transition duration-300">
                    @if(!empty($image['caption']))
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white p-4 transform translate-y-full group-hover:translate-y-0 transition">
                            <p class="text-sm">{{ $image['caption'] }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

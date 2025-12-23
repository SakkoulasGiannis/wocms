@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="gallery py-16">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        <div class="grid md:grid-cols-{{ $settings['columns'] ?? 3 }} gap-4">
            @foreach($content['images'] ?? [] as $image)
                <div class="gallery-item relative overflow-hidden rounded-lg group cursor-pointer">
                    <img src="{{ $image['url'] }}"
                         alt="{{ $image['caption'] ?? '' }}"
                         class="w-full h-64 object-cover transform group-hover:scale-110 transition duration-300">

                    @if(!empty($image['caption']))
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white p-3 transform translate-y-full group-hover:translate-y-0 transition duration-300">
                            {{ $image['caption'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

@if($settings['lightbox'] ?? true)
    {{-- Lightbox functionality would go here --}}
    {{-- Can be implemented with Alpine.js or a JS library --}}
@endif

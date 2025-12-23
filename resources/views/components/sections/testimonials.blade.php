@props(['content', 'settings'])

@php
    $layout = $settings['layout'] ?? 'carousel';
    $showRating = $settings['show_rating'] ?? true;
@endphp

<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        <div class="{{ $layout === 'grid' ? 'grid md:grid-cols-2 lg:grid-cols-3 gap-8' : 'max-w-4xl mx-auto' }}">
            @foreach(($content['testimonials'] ?? []) as $testimonial)
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    @if($showRating && isset($testimonial['rating']))
                        <div class="flex mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    @endif

                    <p class="text-gray-700 mb-6 italic">"{{ $testimonial['text'] ?? '' }}"</p>

                    <div class="flex items-center gap-4">
                        @if(!empty($testimonial['avatar']))
                            <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] ?? '' }}" class="w-12 h-12 rounded-full">
                        @endif
                        <div>
                            <p class="font-semibold">{{ $testimonial['name'] ?? '' }}</p>
                            @if(!empty($testimonial['role']))
                                <p class="text-sm text-gray-600">{{ $testimonial['role'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

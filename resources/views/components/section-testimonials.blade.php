@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="testimonials py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        @if(($settings['layout'] ?? 'carousel') === 'grid')
            <div class="grid md:grid-cols-{{ $settings['columns'] ?? 3 }} gap-8">
                @foreach($content['testimonials'] ?? [] as $testimonial)
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        @if(($settings['show_rating'] ?? true) && !empty($testimonial['rating']))
                            <div class="flex mb-4">
                                @for($i = 0; $i < $testimonial['rating']; $i++)
                                    <span class="text-yellow-500">★</span>
                                @endfor
                            </div>
                        @endif

                        <p class="text-gray-700 mb-4 italic">"{{ $testimonial['text'] }}"</p>

                        <div class="flex items-center gap-3">
                            @if(!empty($testimonial['avatar']))
                                <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] }}" class="w-12 h-12 rounded-full">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($testimonial['name'], 0, 1) }}</span>
                                </div>
                            @endif

                            <div>
                                <p class="font-bold">{{ $testimonial['name'] }}</p>
                                @if(!empty($testimonial['role']))
                                    <p class="text-sm text-gray-600">{{ $testimonial['role'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Carousel layout - simplified version --}}
            <div class="max-w-3xl mx-auto text-center">
                @foreach($content['testimonials'] ?? [] as $index => $testimonial)
                    <div class="testimonial-slide {{ $index === 0 ? '' : 'hidden' }}">
                        @if(($settings['show_rating'] ?? true) && !empty($testimonial['rating']))
                            <div class="flex justify-center mb-4">
                                @for($i = 0; $i < $testimonial['rating']; $i++)
                                    <span class="text-yellow-500 text-2xl">★</span>
                                @endfor
                            </div>
                        @endif

                        <p class="text-xl text-gray-700 mb-6 italic">"{{ $testimonial['text'] }}"</p>

                        <div class="flex items-center justify-center gap-3">
                            @if(!empty($testimonial['avatar']))
                                <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] }}" class="w-16 h-16 rounded-full">
                            @endif

                            <div class="text-left">
                                <p class="font-bold text-lg">{{ $testimonial['name'] }}</p>
                                @if(!empty($testimonial['role']))
                                    <p class="text-gray-600">{{ $testimonial['role'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

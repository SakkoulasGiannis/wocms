@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="about-us py-16">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 items-center {{ ($settings['layout'] ?? 'image_left') === 'image_right' ? 'flex-row-reverse' : '' }}">
            @if(!empty($content['image']))
                <div class="{{ ($settings['layout'] ?? 'image_left') === 'image_right' ? 'md:order-2' : '' }}">
                    <img src="{{ $content['image'] }}" alt="{{ $content['heading'] ?? 'About Us' }}" class="rounded-lg shadow-lg w-full">
                </div>
            @endif

            <div class="{{ ($settings['layout'] ?? 'image_left') === 'image_right' ? 'md:order-1' : '' }}">
                @if(!empty($content['heading']))
                    <h2 class="text-4xl font-bold mb-6">{{ $content['heading'] }}</h2>
                @endif

                @if(!empty($content['text']))
                    <p class="text-lg text-gray-700 mb-8">{!! nl2br(e($content['text'])) !!}</p>
                @endif

                @if(($settings['show_features'] ?? true) && !empty($content['features']))
                    <div class="space-y-4">
                        @foreach($content['features'] as $feature)
                            <div class="flex items-start gap-4">
                                @if(!empty($feature['icon']))
                                    <div class="text-blue-600 text-2xl">
                                        {!! $feature['icon'] !!}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-bold text-lg mb-1">{{ $feature['title'] }}</h3>
                                    <p class="text-gray-600">{{ $feature['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

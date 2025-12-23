@props(['content', 'settings'])

<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col {{ ($settings['layout'] ?? 'image_left') === 'image_left' ? 'md:flex-row' : 'md:flex-row-reverse' }} items-center gap-8">
            <!-- Image -->
            @if(!empty($content['image']))
                <div class="md:w-1/2">
                    <img src="{{ $content['image'] }}" alt="{{ $content['heading'] ?? 'About Us' }}" class="rounded-lg shadow-lg w-full">
                </div>
            @endif

            <!-- Content -->
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold mb-4">{{ $content['heading'] ?? 'About Us' }}</h2>
                <p class="text-gray-700 mb-6">{{ $content['text'] ?? '' }}</p>

                @if(($settings['show_features'] ?? true) && isset($content['features']))
                    <div class="space-y-4">
                        @foreach($content['features'] as $feature)
                            <div class="flex items-start gap-3">
                                @if(!empty($feature['icon']))
                                    <div class="flex-shrink-0 w-6 h-6">
                                        {!! $feature['icon'] !!}
                                    </div>
                                @endif
                                <div>
                                    <h4 class="font-semibold">{{ $feature['title'] ?? '' }}</h4>
                                    <p class="text-gray-600 text-sm">{{ $feature['description'] ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

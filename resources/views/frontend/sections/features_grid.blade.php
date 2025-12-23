{{-- Features Grid Section --}}
@php
    $content = is_array($section->content) ? $section->content : json_decode($section->content, true);
    $settings = is_array($section->settings) ? $section->settings : json_decode($section->settings, true);
    $columns = $settings['columns'] ?? 3;
    $layout = $settings['layout'] ?? 'card';
@endphp

<section class="features-grid bg-gray-50 py-16">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']) || !empty($content['subheading']))
            <div class="text-center mb-12">
                @if(!empty($content['heading']))
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        {{ $content['heading'] }}
                    </h2>
                @endif

                @if(!empty($content['subheading']))
                    <p class="text-xl text-gray-600">
                        {{ $content['subheading'] }}
                    </p>
                @endif
            </div>
        @endif

        @if(!empty($content['features']) && is_array($content['features']))
            <div class="grid grid-cols-1 md:grid-cols-{{ min($columns, 4) }} gap-8">
                @foreach($content['features'] as $feature)
                    <div class="{{ $layout === 'card' ? 'bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition' : 'text-center' }}">
                        @if(!empty($feature['icon']))
                            <div class="mb-4">
                                <img src="{{ $feature['icon'] }}" alt="{{ $feature['title'] ?? '' }}" class="w-16 h-16 {{ $layout === 'card' ? '' : 'mx-auto' }}">
                            </div>
                        @endif

                        @if(!empty($feature['title']))
                            <h3 class="text-xl font-bold text-gray-900 mb-2">
                                {{ $feature['title'] }}
                            </h3>
                        @endif

                        @if(!empty($feature['description']))
                            <p class="text-gray-600">
                                {{ $feature['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

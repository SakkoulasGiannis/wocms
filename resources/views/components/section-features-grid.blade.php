@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
    $columns = $settings['columns'] ?? 3;
@endphp

<section class="features-grid py-16">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-4">{{ $content['heading'] }}</h2>
        @endif

        @if(!empty($content['subheading']))
            <p class="text-xl text-gray-600 text-center mb-12">{{ $content['subheading'] }}</p>
        @endif

        <div class="grid md:grid-cols-{{ $columns }} gap-8">
            @foreach($content['features'] ?? [] as $feature)
                <div class="feature-card {{ ($settings['layout'] ?? 'card') === 'card' ? 'bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition' : 'text-center' }}">
                    @if(!empty($feature['icon']))
                        <div class="text-4xl mb-4 text-blue-600">
                            {!! $feature['icon'] !!}
                        </div>
                    @endif

                    @if(!empty($feature['title']))
                        <h3 class="text-xl font-bold mb-3">{{ $feature['title'] }}</h3>
                    @endif

                    @if(!empty($feature['description']))
                        <p class="text-gray-600">{{ $feature['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

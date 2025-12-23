@props(['content', 'settings'])

@php
    $columns = $settings['columns'] ?? 3;
    $layout = $settings['layout'] ?? 'card';
@endphp

<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']) || !empty($content['subheading']))
            <div class="text-center mb-12">
                @if(!empty($content['subheading']))
                    <p class="text-blue-600 font-semibold mb-2">{{ $content['subheading'] }}</p>
                @endif
                @if(!empty($content['heading']))
                    <h2 class="text-4xl font-bold">{{ $content['heading'] }}</h2>
                @endif
            </div>
        @endif

        <div class="grid md:grid-cols-{{ $columns }} gap-8">
            @foreach(($content['features'] ?? []) as $feature)
                <div class="{{ $layout === 'card' ? 'bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition' : 'text-center' }}">
                    @if(!empty($feature['icon']))
                        <div class="mb-4 {{ $layout === 'card' ? '' : 'flex justify-center' }}">
                            <div class="w-12 h-12 text-blue-600">
                                {!! $feature['icon'] !!}
                            </div>
                        </div>
                    @endif
                    <h3 class="text-xl font-semibold mb-3">{{ $feature['title'] ?? '' }}</h3>
                    <p class="text-gray-600">{{ $feature['description'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

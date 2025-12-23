@props(['content', 'settings'])

<section class="py-16 bg-blue-600 text-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-12">{{ $content['heading'] }}</h2>
        @endif

        <div class="grid md:grid-cols-{{ count($content['stats'] ?? []) <= 3 ? count($content['stats'] ?? []) : 4 }} gap-8">
            @foreach(($content['stats'] ?? []) as $stat)
                <div class="text-center">
                    <div class="text-5xl font-bold mb-2">{{ $stat['number'] ?? '0' }}</div>
                    <div class="text-xl opacity-90">{{ $stat['label'] ?? '' }}</div>
                    @if(!empty($stat['description']))
                        <p class="text-sm opacity-75 mt-2">{{ $stat['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

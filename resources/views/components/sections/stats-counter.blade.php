@props(['content', 'settings'])

<section class="py-20 lg:py-24 bg-brand text-white">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        @if(!empty($content['heading']))
            <h2 class="mb-14 text-center text-3xl font-extrabold capitalize leading-tight md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $content['heading'] }}</h2>
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

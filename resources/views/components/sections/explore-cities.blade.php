@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Explore Cities';
    $title = $content['title'] ?? 'Our Location For You';
    $description = $content['description'] ?? '';
    $sectionClass = $content['section_class'] ?? 'py-20 lg:py-24 bg-white';

    $items = $content['items'] ?? $content['cities'] ?? [];
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($items)) {
        $items = [];
    }

    if (empty($items)) {
        $items = [
            ['name' => 'Chania', 'image' => '/themes/homelengo/images/location/location-1.jpg', 'count' => null],
            ['name' => 'Heraklion', 'image' => '/themes/homelengo/images/location/location-2.jpg', 'count' => null],
            ['name' => 'Rethymno', 'image' => '/themes/homelengo/images/location/location-3.jpg', 'count' => null],
            ['name' => 'Agios Nikolaos', 'image' => '/themes/homelengo/images/location/location-4.jpg', 'count' => null],
            ['name' => 'Sitia', 'image' => '/themes/homelengo/images/location/location-5.jpg', 'count' => null],
            ['name' => 'Kissamos', 'image' => '/themes/homelengo/images/location/location-6.jpg', 'count' => null],
        ];
    }

    $propertyCounts = [];
    if (class_exists(\Modules\Properties\Models\Property::class)) {
        try {
            $propertyCounts = \Modules\Properties\Models\Property::query()
                ->where('active', true)
                ->selectRaw('city, COUNT(*) as total')
                ->whereNotNull('city')
                ->groupBy('city')
                ->pluck('total', 'city')
                ->all();
        } catch (\Throwable $e) {
            $propertyCounts = [];
        }
    }

    $itemCount = count($items);
    $gridClasses = match(true) {
        $itemCount >= 6 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
        $itemCount === 5 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
        $itemCount === 4 => 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4',
        $itemCount === 3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        {{-- Header (homelengo .box-title spec) --}}
        <div class="mx-auto max-w-3xl text-center mb-14">
            @if($subtitle)
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subtitle }}</p>
            @endif
            @if($title)
                <h2 class="mt-4 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="mt-4 text-lg text-variant-1">{{ $description }}</p>
            @endif
        </div>

        {{-- Grid --}}
        <div class="grid {{ $gridClasses }} gap-5">
            @foreach($items as $city)
                @php
                    $name = $city['name'] ?? '';
                    $image = $city['image'] ?? '/themes/kretaeiendom/images/location/location-1.jpg';
                    $count = $city['count'] ?? $propertyCounts[$name] ?? null;
                    $link = $city['link'] ?? null;
                    if (! $link) {
                        try {
                            $link = route('properties.index', ['city' => $name]);
                        } catch (\Throwable $e) {
                            $link = '/properties?city=' . urlencode($name);
                        }
                    }
                    $countLabel = $count !== null
                        ? ($count . ' ' . ($count === 1 ? 'Property' : 'Properties'))
                        : null;
                @endphp
                <a href="{{ $link }}"
                   class="group relative block aspect-[3/4] overflow-hidden rounded-2xl shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft">
                    <img src="{{ $image }}"
                         alt="{{ $name }}"
                         loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">

                    {{-- Overlay (homelengo .location-item: dark gradient bottom) --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-on-surface/85 via-on-surface/30 to-transparent"></div>

                    {{-- Content --}}
                    <div class="absolute inset-x-0 bottom-0 p-5">
                        <h3 class="text-xl font-bold capitalize text-white drop-shadow line-clamp-1 transition-colors group-hover:text-brand-soft">{{ $name }}</h3>
                        @if($countLabel)
                            <p class="mt-1 text-sm font-medium text-white/90">{{ $countLabel }}</p>
                        @endif
                    </div>

                    {{-- Arrow (brand on hover) --}}
                    <div class="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-on-surface shadow-card opacity-0 transition-all duration-300 group-hover:opacity-100 group-hover:bg-brand group-hover:text-white">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

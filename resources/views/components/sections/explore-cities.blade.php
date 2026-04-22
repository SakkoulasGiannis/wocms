@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Explore Cities';
    $title = $content['title'] ?? 'Our Location For You';
    $description = $content['description'] ?? '';
    $sectionClass = $content['section_class'] ?? 'py-16 bg-white';

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
        {{-- Header --}}
        <div class="mx-auto max-w-3xl text-center mb-12">
            @if($subtitle)
                <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
            @endif
            @if($title)
                <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
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
                   class="group relative block aspect-[3/4] overflow-hidden rounded-2xl shadow-md ring-1 ring-slate-200 transition-all hover:shadow-xl hover:-translate-y-1">
                    <img src="{{ $image }}"
                         alt="{{ $name }}"
                         loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">

                    {{-- Overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>

                    {{-- Content --}}
                    <div class="absolute inset-x-0 bottom-0 p-4">
                        @if($countLabel)
                            <p class="text-xs font-semibold uppercase tracking-wider text-brand">{{ $countLabel }}</p>
                        @endif
                        <h3 class="mt-1 text-lg font-bold text-white drop-shadow line-clamp-1">{{ $name }}</h3>
                    </div>

                    {{-- Arrow --}}
                    <div class="absolute top-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 opacity-0 transition-opacity group-hover:opacity-100">
                        <svg class="h-4 w-4 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

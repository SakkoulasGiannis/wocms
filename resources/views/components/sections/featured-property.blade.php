@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Featured Properties';
    $title = $content['title'] ?? "Discover Homelengo's Finest Properties for Your Dream Home";
    $description = $content['description'] ?? '';
    $count = (int) ($settings['count'] ?? $content['count'] ?? 6);
    $sectionClass = $content['section_class'] ?? 'py-20 lg:py-24 bg-white';
    $showTabs = (bool) ($content['show_tabs'] ?? $settings['show_tabs'] ?? true);

    $properties = collect();
    $propertiesModelExists = class_exists(\Modules\Properties\Models\Property::class);

    if ($propertiesModelExists) {
        try {
            $properties = \Modules\Properties\Models\Property::query()
                ->where('active', true)
                ->where('featured', true)
                ->latest()
                ->limit($count)
                ->get();

            if ($properties->isEmpty()) {
                $properties = \Modules\Properties\Models\Property::query()
                    ->where('active', true)
                    ->latest()
                    ->limit($count)
                    ->get();
            }
        } catch (\Throwable $e) {
            $properties = collect();
        }
    }

    $availableTypes = $properties
        ->pluck('property_type')
        ->filter()
        ->unique()
        ->values()
        ->map(fn ($type) => [
            'key' => $type,
            'label' => ucwords(str_replace('_', ' ', $type)),
        ])
        ->all();

    $activeTheme = null;
    try {
        $activeTheme = app(\App\Services\ThemeManager::class)->getActiveTheme();
    } catch (\Throwable $e) {
        $activeTheme = null;
    }
    $useKetwCard = $activeTheme === 'ketw' && view()->exists('themes.ketw.templates.properties._card-grid');

    $statusLabels = [
        'for_sale' => 'For Sale',
        'for_rent' => 'For Rent',
        'sold' => 'Sold',
        'rented' => 'Rented',
    ];

    $viewAllUrl = null;
    try {
        $viewAllUrl = route('properties.index');
    } catch (\Throwable $e) {
        $viewAllUrl = '/properties';
    }
@endphp

<section class="{{ $sectionClass }}"
         x-data="{ selectedType: 'all' }">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        {{-- Header (homelengo .box-title.text-center spec) --}}
        <div class="mx-auto max-w-3xl text-center mb-12">
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

        @if($properties->isEmpty())
            <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">No featured properties available</h3>
                <p class="mt-2 text-sm text-slate-500">Check back soon for our latest listings.</p>
            </div>
        @else
            {{-- Tabs (homelengo .nav-tab-recommended) — pills with surface bg,
                 brand on active/hover. Horizontal scroll on overflow. --}}
            @if($showTabs && count($availableTypes) > 0)
                <div class="mb-12 flex flex-wrap items-center justify-center gap-3 overflow-x-auto">
                    <button type="button"
                            @click="selectedType = 'all'"
                            :class="selectedType === 'all' ? 'bg-brand text-white' : 'bg-surface text-on-surface hover:bg-brand hover:text-white'"
                            class="whitespace-nowrap rounded-full px-6 py-2 text-sm font-semibold transition-colors">
                        View All
                    </button>
                    @foreach($availableTypes as $type)
                        <button type="button"
                                @click="selectedType = '{{ $type['key'] }}'"
                                :class="selectedType === '{{ $type['key'] }}' ? 'bg-brand text-white' : 'bg-surface text-on-surface hover:bg-brand hover:text-white'"
                                class="whitespace-nowrap rounded-full px-6 py-2 text-sm font-semibold transition-colors">
                            {{ $type['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Grid --}}
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach($properties as $property)
                    <div wire:key="featured-property-{{ $property->id }}"
                         x-show="selectedType === 'all' || selectedType === '{{ $property->property_type }}'"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        @if($useKetwCard)
                            @include('themes.ketw.templates.properties._card-grid', ['property' => $property])
                        @else
                            @php
                                $imageUrl = method_exists($property, 'getFirstMediaUrl')
                                    ? ($property->getFirstMediaUrl('featured_image', 'medium')
                                        ?: $property->getFirstMediaUrl('featured_image'))
                                    : null;
                                $imageUrl = $imageUrl ?: ($property->featured_image ?? '/themes/kretaeiendom/images/home/house-7.jpg');
                                $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status ?? ''));
                                $statusTone = match($property->status ?? '') {
                                    'for_sale' => 'bg-emerald-500',
                                    'for_rent' => 'bg-amber-500',
                                    'sold', 'rented' => 'bg-slate-500',
                                    default => 'bg-brand',
                                };
                                try {
                                    $detailUrl = route('properties.show', $property->slug);
                                } catch (\Throwable $e) {
                                    $detailUrl = '#';
                                }
                            @endphp
                            <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl hover:-translate-y-1">
                                <a href="{{ $detailUrl }}" class="relative block aspect-4/3 overflow-hidden bg-slate-100">
                                    <img src="{{ $imageUrl }}" alt="{{ $property->title }}" loading="lazy"
                                         class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">

                                    <div class="absolute left-3 top-3 flex flex-wrap items-center gap-2">
                                        @if($property->featured ?? false)
                                            <span class="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white shadow">Featured</span>
                                        @endif
                                        @if($statusLabel)
                                            <span class="rounded-full {{ $statusTone }} px-3 py-1 text-xs font-semibold text-white shadow">{{ $statusLabel }}</span>
                                        @endif
                                    </div>

                                    @if($property->address || $property->city)
                                        <div class="absolute bottom-3 left-3 right-3 flex items-center gap-1.5 text-sm text-white drop-shadow">
                                            <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="truncate">{{ $property->address ?? '' }}{{ $property->address && $property->city ? ', ' : '' }}{{ $property->city ?? '' }}</span>
                                        </div>
                                    @endif
                                </a>

                                <div class="p-5">
                                    <h3 class="line-clamp-1 text-lg font-semibold text-slate-900 capitalize">
                                        <a href="{{ $detailUrl }}" class="hover:text-brand">{{ $property->title }}</a>
                                    </h3>

                                    <ul class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-600">
                                        @if($property->bedrooms)
                                            <li class="flex items-center gap-1.5" title="Bedrooms">
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12v7h18v-7M5 12V9a3 3 0 013-3h8a3 3 0 013 3v3M5 12h14" />
                                                </svg>
                                                <span class="font-medium text-slate-900">{{ $property->bedrooms }}</span>
                                            </li>
                                        @endif
                                        @if($property->bathrooms)
                                            <li class="flex items-center gap-1.5" title="Bathrooms">
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 13h16M6 13V7a3 3 0 015.92-.7M8 21v-3M16 21v-3" />
                                                </svg>
                                                <span class="font-medium text-slate-900">{{ $property->bathrooms }}</span>
                                            </li>
                                        @endif
                                        @if($property->area)
                                            <li class="flex items-center gap-1.5" title="Area">
                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4zM4 9h16M9 4v16" />
                                                </svg>
                                                <span class="font-medium text-slate-900">{{ number_format($property->area, 0) }} m&sup2;</span>
                                            </li>
                                        @endif
                                    </ul>

                                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                        <span class="text-xs text-slate-500 capitalize">{{ str_replace('_', ' ', $property->property_type ?? '') }}</span>
                                        <span class="text-lg font-bold text-brand">
                                            {{ $property->formatted_price ?? (($property->currency ?? '') . ' ' . number_format((float) ($property->price ?? 0), 0)) }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- CTA --}}
            <div class="mt-12 text-center">
                <a href="{{ $viewAllUrl }}"
                   class="inline-flex items-center gap-2 rounded-full bg-brand px-8 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
                    View All Properties
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>

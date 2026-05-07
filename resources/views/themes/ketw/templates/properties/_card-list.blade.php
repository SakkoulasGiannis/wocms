{{-- Property Card — List Layout (homelengo .homelengo-box.list-style design) --}}
@php
    $imageUrl = $property->getFirstMediaUrl('featured_image', 'medium')
        ?: $property->getFirstMediaUrl('featured_image')
        ?: '/themes/kretaeiendom/images/home/house-sm-11.jpg';

    $statusLabels = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold', 'rented' => 'Rented'];
    $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status ?? ''));

    $detailUrl = isset($isRental) && $isRental
        ? route('rental-properties.show', $property->slug)
        : route('properties.show', $property->slug);

    $location = trim(implode(', ', array_filter([$property->address ?? '', $property->city ?? ''])));

    $agent = method_exists($property, 'agent') ? ($property->agent ?? null) : null;
    if (! $agent && property_exists($property, 'agent_name')) {
        $agent = (object) ['name' => $property->agent_name ?? null, 'photo' => null];
    }
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30 sm:flex-row">

    {{-- Image (left) --}}
    <a href="{{ $detailUrl }}" class="relative block aspect-[4/3] flex-shrink-0 overflow-hidden bg-slate-100 sm:w-80 sm:aspect-auto">
        <img src="{{ $imageUrl }}" alt="{{ $property->title }}" loading="lazy"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">

        {{-- Top-left badges --}}
        <div class="absolute left-3 top-3 flex flex-wrap items-center gap-1.5">
            @if($property->featured)
                <span class="rounded-md bg-brand px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow">Featured</span>
            @endif
            @if($statusLabel)
                <span class="rounded-md bg-white/95 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-on-surface shadow">{{ $statusLabel }}</span>
            @endif
        </div>

        {{-- Bottom-left location --}}
        @if($location !== '')
            <div class="absolute bottom-3 left-3 right-3 flex items-center gap-1.5 text-sm text-white drop-shadow">
                <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 7C10 7.53 9.79 8.04 9.41 8.41C9.04 8.79 8.53 9 8 9C7.47 9 6.96 8.79 6.59 8.41C6.21 8.04 6 7.53 6 7C6 6.47 6.21 5.96 6.59 5.59C6.96 5.21 7.47 5 8 5C8.53 5 9.04 5.21 9.41 5.59C9.79 5.96 10 6.47 10 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13 7C13 11.76 8 14.5 8 14.5C8 14.5 3 11.76 3 7C3 5.67 3.53 4.40 4.46 3.46C5.40 2.53 6.67 2 8 2C9.33 2 10.60 2.53 11.54 3.46C12.47 4.40 13 5.67 13 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="truncate">{{ $location }}</span>
            </div>
        @endif
    </a>

    {{-- Body (right) --}}
    <div class="flex flex-1 flex-col justify-between p-5">
        <div>
            <h3 class="line-clamp-1 text-xl font-bold capitalize text-on-surface">
                <a href="{{ $detailUrl }}" class="transition-colors hover:text-brand">{{ $property->title }}</a>
            </h3>

            {{-- Description preview if available --}}
            @if(! empty($property->description))
                <p class="mt-2 line-clamp-2 text-sm text-variant-1">{{ strip_tags($property->description) }}</p>
            @endif

            <ul class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                @if($property->bedrooms)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12v7h18v-7M5 12V9a3 3 0 013-3h8a3 3 0 013 3v3M5 12h14"/></svg>
                        <span class="text-variant-1">Beds:</span>
                        <span class="font-semibold text-on-surface">{{ $property->bedrooms }}</span>
                    </li>
                @endif
                @if($property->bathrooms)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h16M6 13V7a3 3 0 015.92-.7M8 21v-3M16 21v-3"/></svg>
                        <span class="text-variant-1">Baths:</span>
                        <span class="font-semibold text-on-surface">{{ $property->bathrooms }}</span>
                    </li>
                @endif
                @if($property->area)
                    <li class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4zM4 9h16M9 4v16"/></svg>
                        <span class="text-variant-1">Sqft:</span>
                        <span class="font-semibold text-on-surface">{{ number_format($property->area, 0) }}</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="mt-5 flex items-center justify-between gap-4 border-t border-outline pt-4">
            @if($agent && ! empty($agent->name))
                <div class="flex items-center gap-2 min-w-0">
                    @if(! empty($agent->photo))
                        <img src="{{ $agent->photo }}" alt="{{ $agent->name }}" class="h-10 w-10 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="h-10 w-10 rounded-full bg-brand-soft flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-brand">{{ mb_substr($agent->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <span class="truncate text-sm font-medium text-on-surface">{{ $agent->name }}</span>
                </div>
            @else
                <span class="text-xs uppercase tracking-wide text-variant-1">{{ str_replace('_', ' ', $property->property_type ?? '') }}</span>
            @endif

            <span class="text-xl font-bold text-on-surface">{{ $property->formatted_price }}</span>
        </div>
    </div>
</article>

{{-- Property Card — Grid Layout (Tailwind) --}}
@php
    $imageUrl = $property->getFirstMediaUrl('featured_image', 'medium') ?: $property->getFirstMediaUrl('featured_image') ?: '/themes/kretaeiendom/images/home/house-7.jpg';
    $statusLabels = ['for_sale' => 'For Sale', 'for_rent' => 'For Rent', 'sold' => 'Sold', 'rented' => 'Rented'];
    $statusLabel = $statusLabels[$property->status] ?? ucfirst(str_replace('_', ' ', $property->status));
    $statusTone = match($property->status) {
        'for_sale' => 'bg-emerald-500',
        'for_rent' => 'bg-amber-500',
        'sold', 'rented' => 'bg-slate-500',
        default => 'bg-brand',
    };
    $detailUrl = isset($isRental) && $isRental
        ? route('rental-properties.show', $property->slug)
        : route('properties.show', $property->slug);
@endphp

<article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl hover:-translate-y-1">
    {{-- Image --}}
    <a href="{{ $detailUrl }}" class="relative block aspect-4/3 overflow-hidden bg-slate-100">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $property->title }}"
            loading="lazy"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
        >

        {{-- Tags --}}
        <div class="absolute left-3 top-3 flex flex-wrap items-center gap-2">
            @if($property->featured)
                <span class="rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white shadow">Featured</span>
            @endif
            <span class="rounded-full {{ $statusTone }} px-3 py-1 text-xs font-semibold text-white shadow">{{ $statusLabel }}</span>
        </div>

        {{-- Location --}}
        @if($property->address || $property->city)
            <div class="absolute bottom-3 left-3 right-3 flex items-center gap-1.5 text-sm text-white drop-shadow">
                <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                <span class="truncate">{{ $property->address ?? '' }}{{ $property->address && $property->city ? ', ' : '' }}{{ $property->city ?? '' }}</span>
            </div>
        @endif
    </a>

    {{-- Body --}}
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
                    <span class="font-medium text-slate-900">{{ number_format($property->area, 0) }} m²</span>
                </li>
            @endif
        </ul>

        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
            <span class="text-xs text-slate-500 capitalize">{{ str_replace('_', ' ', $property->property_type ?? '') }}</span>
            <span class="text-lg font-bold text-brand">{{ $property->formatted_price }}</span>
        </div>
    </div>
</article>

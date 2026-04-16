@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Services';
    $title = $content['title'] ?? 'Welcome to HomeLengo';
    $description = $content['description'] ?? '';
    $columns = (int) ($settings['columns'] ?? $content['columns'] ?? 3);
    $sectionClass = $content['section_class'] ?? 'py-20 bg-slate-50';

    $items = $content['items'] ?? [];
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }
    if (empty($items)) {
        $items = [
            ['icon' => 'home', 'title' => 'Buy A New Home', 'description' => 'Discover your dream home effortlessly. Explore diverse properties and expert guidance for a seamless buying experience.', 'link' => '#'],
            ['icon' => 'tag', 'title' => 'Sell a Home', 'description' => 'Sell confidently with expert guidance and effective strategies, showcasing your property\'s best features for a successful sale.', 'link' => '#'],
            ['icon' => 'key', 'title' => 'Rent a Home', 'description' => 'Discover your perfect rental effortlessly. Explore a diverse variety of listings tailored precisely to suit your unique lifestyle needs.', 'link' => '#'],
        ];
    }

    $iconSvgs = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />',
        'key' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />',
        'building' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
    ];
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mx-auto max-w-2xl text-center mb-14">
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
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-{{ $columns }}">
            @foreach($items as $item)
                <div class="group relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl hover:-translate-y-1">
                    {{-- Icon or Image --}}
                    @if(!empty($item['image']))
                        <div class="mb-6 h-16 w-16 overflow-hidden rounded-xl">
                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] ?? '' }}" class="h-full w-full object-contain">
                        </div>
                    @else
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-brand/10 text-brand">
                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                {!! $iconSvgs[$item['icon'] ?? 'home'] ?? $iconSvgs['home'] !!}
                            </svg>
                        </div>
                    @endif

                    <h3 class="text-xl font-bold text-slate-900">{{ $item['title'] ?? '' }}</h3>
                    @if(!empty($item['description']))
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
                    @endif
                    @if(!empty($item['link']) && $item['link'] !== '#')
                        <a href="{{ $item['link'] }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark transition-colors">
                            Learn More
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

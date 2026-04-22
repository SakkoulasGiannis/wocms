@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Our Benefits';
    $title = $content['title'] ?? 'Why Choose HomeLengo';
    $description = $content['description'] ?? 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.';
    $image = $content['image'] ?? '/themes/homelengo/images/banner/img-w-text5.jpg';
    $sectionClass = $content['section_class'] ?? 'py-16 bg-white';
    $bgClass = $content['bg_class'] ?? 'bg-slate-50';

    $items = $content['items'] ?? [];
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($items)) {
        $items = [];
    }

    $defaultIcons = [
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'star' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
        'handshake' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />',
    ];

    if (empty($items)) {
        $items = [
            [
                'icon' => 'shield',
                'title' => 'Proven Expertise',
                'description' => 'Our seasoned team excels in real estate with years of successful market navigation.',
            ],
            [
                'icon' => 'star',
                'title' => 'Customized Solutions',
                'description' => 'Tailored to your unique needs, our approach ensures satisfaction at every step.',
            ],
            [
                'icon' => 'handshake',
                'title' => 'Transparent Partnerships',
                'description' => 'Building trust through clarity, integrity, and open communication.',
            ],
        ];
    }
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl {{ $bgClass }}">
            <div class="grid grid-cols-1 gap-0 lg:grid-cols-2">
                {{-- Image --}}
                <div class="relative min-h-[320px] lg:min-h-full">
                    <img src="{{ $image }}"
                         alt="{{ $title }}"
                         loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover">
                </div>

                {{-- Benefits --}}
                <div class="p-8 md:p-12 lg:p-16">
                    <div class="mb-10">
                        @if($subtitle)
                            <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                        @endif
                        @if($title)
                            <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $title }}</h2>
                        @endif
                        @if($description)
                            <p class="mt-4 text-base text-slate-600">{{ $description }}</p>
                        @endif
                    </div>

                    <div class="space-y-5">
                        @foreach($items as $item)
                            @php
                                $itemIcon = $item['icon'] ?? 'shield';
                                $customSvg = $item['icon_svg'] ?? null;
                            @endphp
                            <div class="group flex gap-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md hover:-translate-y-0.5">
                                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                                    @if($customSvg)
                                        {!! $customSvg !!}
                                    @else
                                        <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            {!! $defaultIcons[$itemIcon] ?? $defaultIcons['shield'] !!}
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-slate-900">{{ $item['title'] ?? '' }}</h3>
                                    @if(! empty($item['description']))
                                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

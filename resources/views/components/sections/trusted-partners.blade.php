@props(['content' => [], 'settings' => []])

@php
    $ensureArray = function ($val, $default = []) {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && $val !== '') {
            $d = json_decode($val, true);
            if (is_array($d)) {
                return $d;
            }
        }
        return $default;
    };

    $sectionClass = $content['section_class'] ?? 'bg-white';
    $heading = $content['heading'] ?? $content['title'] ?? 'Trusted by over 150+ major companies';
    $subtitle = $content['subtitle'] ?? null;
    $description = $content['description'] ?? null;

    $rawItems = $ensureArray($content['items'] ?? null, []);

    // Normalize items: accept either strings (URLs) or arrays with logo/name/url keys
    $items = [];
    foreach ($rawItems as $it) {
        if (is_string($it)) {
            $items[] = ['logo' => $it, 'name' => '', 'url' => null];
        } elseif (is_array($it)) {
            $items[] = [
                'logo' => $it['logo'] ?? $it['image'] ?? '',
                'name' => $it['name'] ?? '',
                'url' => $it['url'] ?? null,
            ];
        }
    }

    $hasItems = ! empty($items);
@endphp

<section class="py-20 lg:py-24 {{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        {{-- Header (homelengo .box-title spec) --}}
        <div class="mx-auto max-w-3xl text-center mb-14">
            @if ($subtitle)
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subtitle }}</p>
            @endif
            @if ($heading)
                <h2 class="mt-4 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $heading }}</h2>
            @endif
            @if ($description)
                <p class="mt-4 text-lg text-variant-1">{{ $description }}</p>
            @endif
        </div>

        {{-- Logo grid (homelengo .partners-list — grayscale → color hover) --}}
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-6">
            @if ($hasItems)
                @foreach ($items as $item)
                    @php
                        $inner = '
                            <div class="flex h-24 w-full items-center justify-center rounded-2xl bg-white p-6 ring-1 ring-outline transition-all duration-300 hover:shadow-card hover:ring-brand/30">
                                <img src="'.e($item['logo']).'" alt="'.e($item['name'] ?: 'Partner logo').'" class="max-h-12 w-auto max-w-full object-contain opacity-50 grayscale transition-all duration-300 group-hover:opacity-100 group-hover:grayscale-0">
                            </div>
                        ';
                    @endphp
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="group block">
                            {!! $inner !!}
                        </a>
                    @else
                        <div class="group">
                            {!! $inner !!}
                        </div>
                    @endif
                @endforeach
            @else
                {{-- Placeholder squares --}}
                @for ($i = 0; $i < 6; $i++)
                    <div class="group">
                        <div class="flex h-24 w-full items-center justify-center rounded-2xl bg-surface p-6 ring-1 ring-outline transition-all duration-300 hover:bg-white hover:shadow-card hover:ring-brand/30">
                            <span class="text-sm font-semibold uppercase tracking-wider text-variant-2 transition-colors group-hover:text-brand">Logo</span>
                        </div>
                    </div>
                @endfor
            @endif
        </div>
    </div>
</section>

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

<section class="py-16 {{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mx-auto max-w-3xl text-center mb-12">
            @if ($subtitle)
                <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
            @endif
            @if ($heading)
                <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
            @endif
            @if ($description)
                <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
            @endif
        </div>

        {{-- Logo grid --}}
        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-6">
            @if ($hasItems)
                @foreach ($items as $item)
                    @php
                        $inner = '
                            <div class="flex h-24 w-full items-center justify-center rounded-xl bg-white p-6 ring-1 ring-slate-200 transition-all duration-300 hover:shadow-md hover:ring-brand/40">
                                <img src="'.e($item['logo']).'" alt="'.e($item['name'] ?: 'Partner logo').'" class="max-h-12 w-auto max-w-full object-contain opacity-60 grayscale transition-all duration-300 group-hover:opacity-100 group-hover:grayscale-0">
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
                        <div class="flex h-24 w-full items-center justify-center rounded-xl bg-slate-50 p-6 ring-1 ring-slate-200 transition-all duration-300 hover:bg-white hover:shadow-md hover:ring-brand/40">
                            <span class="text-sm font-semibold uppercase tracking-wider text-slate-400 transition-colors group-hover:text-brand">Logo</span>
                        </div>
                    </div>
                @endfor
            @endif
        </div>
    </div>
</section>

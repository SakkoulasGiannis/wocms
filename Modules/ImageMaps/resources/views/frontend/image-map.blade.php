{{--
    Frontend Image Map Renderer
    Usage: @include('imagemaps::frontend.image-map', ['imageMap' => $imageMap])
    Renders shapes as SVG overlay on the base image. Fully responsive, no JS required.
--}}
@php
    $shapes = $imageMap->items['shapes'] ?? [];
    $settings = $imageMap->items['settings'] ?? [];
    $showTooltips = $settings['showTooltips'] ?? true;
    $imageUrl = $imageMap->getFirstMediaUrl('image');
@endphp

@if($imageUrl)
<div class="image-map-wrapper" style="position:relative; display:inline-block; width:100%;" data-image-map="{{ $imageMap->slug }}">
    <img src="{{ $imageUrl }}" alt="{{ $imageMap->title }}" style="width:100%; height:auto; display:block; border-radius:8px;">

    @if(count($shapes) > 0)
    <svg viewBox="0 0 100 100" preserveAspectRatio="none"
         style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; border-radius:8px;">
        @foreach($shapes as $shape)
            @if($shape['type'] === 'rect')
                <a href="{{ $shape['link'] ?: 'javascript:void(0)' }}" style="pointer-events:auto;">
                    <rect x="{{ $shape['x'] }}" y="{{ $shape['y'] }}"
                          width="{{ $shape['width'] }}" height="{{ $shape['height'] }}"
                          fill="{{ $shape['color'] ?? '#1563df' }}"
                          fill-opacity="{{ $shape['opacity'] ?? 0.3 }}"
                          stroke="{{ $shape['color'] ?? '#1563df' }}" stroke-width="0.3"
                          class="image-map-shape"
                          data-title="{{ $shape['title'] ?? '' }}"
                          data-description="{{ $shape['description'] ?? '' }}"
                          style="cursor:{{ !empty($shape['link']) ? 'pointer' : 'default' }};">
                        @if($showTooltips && !empty($shape['title']))
                            <title>{{ $shape['title'] }}{{ !empty($shape['description']) ? ' - ' . $shape['description'] : '' }}</title>
                        @endif
                    </rect>
                </a>
            @elseif($shape['type'] === 'circle')
                <a href="{{ $shape['link'] ?: 'javascript:void(0)' }}" style="pointer-events:auto;">
                    <circle cx="{{ $shape['cx'] }}" cy="{{ $shape['cy'] }}" r="{{ $shape['radius'] }}"
                            fill="{{ $shape['color'] ?? '#e74c3c' }}"
                            fill-opacity="{{ $shape['opacity'] ?? 0.3 }}"
                            stroke="{{ $shape['color'] ?? '#e74c3c' }}" stroke-width="0.3"
                            class="image-map-shape"
                            data-title="{{ $shape['title'] ?? '' }}"
                            data-description="{{ $shape['description'] ?? '' }}"
                            style="cursor:{{ !empty($shape['link']) ? 'pointer' : 'default' }};">
                        @if($showTooltips && !empty($shape['title']))
                            <title>{{ $shape['title'] }}{{ !empty($shape['description']) ? ' - ' . $shape['description'] : '' }}</title>
                        @endif
                    </circle>
                </a>
            @elseif($shape['type'] === 'polygon')
                @php $pointsStr = collect($shape['points'] ?? [])->map(fn($p) => $p['x'] . ',' . $p['y'])->join(' '); @endphp
                <a href="{{ $shape['link'] ?: 'javascript:void(0)' }}" style="pointer-events:auto;">
                    <polygon points="{{ $pointsStr }}"
                             fill="{{ $shape['color'] ?? '#2ecc71' }}"
                             fill-opacity="{{ $shape['opacity'] ?? 0.3 }}"
                             stroke="{{ $shape['color'] ?? '#2ecc71' }}" stroke-width="0.3"
                             class="image-map-shape"
                             data-title="{{ $shape['title'] ?? '' }}"
                             data-description="{{ $shape['description'] ?? '' }}"
                             style="cursor:{{ !empty($shape['link']) ? 'pointer' : 'default' }};">
                        @if($showTooltips && !empty($shape['title']))
                            <title>{{ $shape['title'] }}{{ !empty($shape['description']) ? ' - ' . $shape['description'] : '' }}</title>
                        @endif
                    </polygon>
                </a>
            @endif
        @endforeach
    </svg>
    @endif
</div>

<style>
    .image-map-shape { transition: fill-opacity 0.2s ease; }
    .image-map-shape:hover { fill-opacity: 0.5 !important; }
</style>
@endif

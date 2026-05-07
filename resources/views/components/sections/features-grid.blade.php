@props(['content', 'settings'])

@php
    $columns = $settings['columns'] ?? 3;
    $layout = $settings['layout'] ?? 'card';
@endphp

<section class="py-20 lg:py-24 bg-surface">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        @if(!empty($content['heading']) || !empty($content['subheading']))
            <div class="mx-auto max-w-2xl text-center mb-14">
                @if(!empty($content['subheading']))
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $content['subheading'] }}</p>
                @endif
                @if(!empty($content['heading']))
                    <h2 class="mt-4 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $content['heading'] }}</h2>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-{{ $columns }}">
            @foreach(($content['features'] ?? []) as $feature)
                @if($layout === 'card')
                    <div class="group rounded-2xl bg-white p-8 shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30">
                        @if(!empty($feature['icon']))
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-soft text-brand transition-colors duration-300 group-hover:bg-brand group-hover:text-white">
                                <div class="h-8 w-8">
                                    {!! $feature['icon'] !!}
                                </div>
                            </div>
                        @endif
                        <h3 class="text-xl font-bold capitalize text-on-surface transition-colors group-hover:text-brand">{{ $feature['title'] ?? '' }}</h3>
                        @if(!empty($feature['description']))
                            <p class="mt-3 text-sm leading-relaxed text-variant-1">{{ $feature['description'] }}</p>
                        @endif
                    </div>
                @else
                    <div class="text-center">
                        @if(!empty($feature['icon']))
                            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                                <div class="h-8 w-8">
                                    {!! $feature['icon'] !!}
                                </div>
                            </div>
                        @endif
                        <h3 class="text-xl font-bold capitalize text-on-surface">{{ $feature['title'] ?? '' }}</h3>
                        @if(!empty($feature['description']))
                            <p class="mt-3 text-sm leading-relaxed text-variant-1">{{ $feature['description'] }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>

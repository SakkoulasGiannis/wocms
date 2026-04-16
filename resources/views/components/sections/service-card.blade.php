@props(['content' => [], 'settings' => []])

@php
    $image = $content['image'] ?? '';
    $title = $content['title'] ?? '';
    $description = $content['description'] ?? '';
    $link = $content['link'] ?? '';
    $linkText = !empty($content['link_text']) ? $content['link_text'] : 'Learn More';
    $cardClass = !empty($content['card_class']) ? $content['card_class'] : 'rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl hover:-translate-y-1';
    $imageClass = !empty($content['image_class']) ? $content['image_class'] : 'h-16 w-16 object-contain';
    $titleClass = !empty($content['title_class']) ? $content['title_class'] : 'text-xl font-bold text-slate-900';
@endphp

<div class="group {{ $cardClass }}">
    @if($image)
        <div class="mb-6">
            <img src="{{ $image }}" alt="{{ $title }}" class="{{ $imageClass }}">
        </div>
    @endif

    @if($title)
        <h3 class="{{ $titleClass }}">{{ $title }}</h3>
    @endif

    @if($description)
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
    @endif

    @if($link)
        <a href="{{ $link }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark transition-colors">
            {{ $linkText }}
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
        </a>
    @endif
</div>

@props(['content', 'settings'])

@php
    $containerClass = $settings['container'] ?? true ? 'container mx-auto px-4' : '';
    $paddingClass = $settings['padding'] ?? true ? 'py-16' : '';
@endphp

<section class="{{ $paddingClass }}">
    <div class="{{ $containerClass }}">
        {!! $content['html'] ?? '' !!}
    </div>
</section>

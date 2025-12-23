@props(['section'])

@php
    $content = $section->content;
@endphp

<section class="custom-html">
    {!! $content['html'] ?? '' !!}
</section>

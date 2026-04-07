{{-- Renders EditorJS JSON (or legacy HTML) as HTML --}}
@props(['content' => ''])

@php
    $rendered = app(\App\Services\EditorJsRenderer::class)->toHtml($content);
@endphp

{!! $rendered !!}

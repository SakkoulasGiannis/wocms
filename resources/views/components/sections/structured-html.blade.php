@props(['content', 'settings'])

@php
    use App\Helpers\StructuredHTMLRenderer;

    // Render the structured JSON to HTML
    $html = StructuredHTMLRenderer::render($content['structure'] ?? []);
@endphp

{!! $html !!}

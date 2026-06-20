{{-- Raw HTML Section — prefers token-resolved $content from render-section --}}
@php
    if (isset($content) && is_array($content)) {
        $html = $content['html'] ?? '';
    } else {
        $html = is_array($section->content) ? ($section->content['html'] ?? '') : $section->content;
    }
    // Expand any visual-builder repeater regions (data-vb-loop) into real items.
    $html = app(\App\VisualBuilder\LoopRenderer::class)->expandHtml($html);
@endphp
{!! $html !!}

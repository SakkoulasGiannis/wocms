{{-- Raw HTML Section — prefers token-resolved $content from render-section --}}
@php
    if (isset($content) && is_array($content)) {
        $html = $content['html'] ?? '';
    } else {
        $html = is_array($section->content) ? ($section->content['html'] ?? '') : $section->content;
    }
@endphp
{!! $html !!}

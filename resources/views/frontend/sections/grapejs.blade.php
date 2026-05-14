{{-- GrapeJS Section — prefers token-resolved $content from render-section --}}
@php
    if (isset($content) && is_array($content)) {
        $html = $content['html'] ?? '';
    } else {
        $html = is_array($section->content) ? ($section->content['html'] ?? '') : $section->content;
    }
@endphp
{!! $html !!}

{{-- Section-specific CSS --}}
@if($section->css)
    @once('section-css-' . $section->id)
        <style>{!! $section->css !!}</style>
    @endonce
@elseif(is_array($section->content) && isset($section->content['css']))
    @once('section-css-' . $section->id)
        <style>{!! $section->content['css'] !!}</style>
    @endonce
@endif

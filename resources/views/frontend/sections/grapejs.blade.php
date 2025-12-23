{{-- GrapeJS Section --}}
@if(is_array($section->content))
    @if(isset($section->content['html']))
        {!! $section->content['html'] !!}
    @endif
@else
    {!! $section->content !!}
@endif

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

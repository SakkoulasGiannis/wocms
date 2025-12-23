{{-- Raw HTML Section --}}
@if(is_array($section->content) && isset($section->content['html']))
    {!! $section->content['html'] !!}
@else
    {!! $section->content !!}
@endif

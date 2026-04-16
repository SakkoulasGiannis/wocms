{{--
    Stub: delegates to the PageBuilder module's render-section partial.
    Usage: @include('partials.render-section', ['section' => $section])
--}}
@include('pagebuilder::partials.render-section', ['section' => $section, 'forceVe' => $forceVe ?? false])

@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Page')

{{-- Bare placeholder token only. Wrapping it in @if/@foreach inside @section
     causes Blade section capture to drop the content; a single literal token
     survives. The controller's sectionsResponse() swaps this token for the
     pre-rendered sections HTML after the view renders. --}}
@section('content')
__VE_PRERENDERED_SECTIONS__
@stop

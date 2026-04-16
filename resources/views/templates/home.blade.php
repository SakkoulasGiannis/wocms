@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Home')

{{-- Pre-render sections BEFORE @section to protect Blade stack --}}
@php
    $__sectionsHtml = '';
    if (isset($sections) && $sections->count() > 0) {
        foreach ($sections as $section) {
            try {
                $__sectionsHtml .= view('partials.render-section', ['section' => $section, 'forceVe' => $forceVe ?? false])->render();
            } catch (\Throwable $e) {
                if (config('app.debug')) {
                    $__sectionsHtml .= '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                        . '<strong>Section #' . e($section->id ?? '?') . ' (' . e($section->section_type ?? '?') . '):</strong> '
                        . e($e->getMessage()) . '</div>';
                }
            }
        }
    }
@endphp

@section('content')
    @if($__sectionsHtml)
        {!! $__sectionsHtml !!}
    @elseif($content && $content->body)
        {!! $content->body !!}
    @endif
@endsection

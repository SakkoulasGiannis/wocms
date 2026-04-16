@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Page')

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
    @else
        <div class="container mx-auto px-4 py-12">
            <div class="text-center text-gray-500">
                <p>This page has no sections yet. Add sections from the admin panel.</p>
            </div>
        </div>
    @endif
@endsection

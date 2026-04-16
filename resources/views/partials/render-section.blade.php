{{--
    Stub: delegates to the PageBuilder module's render-section partial.
    Uses isolated render to prevent exceptions from corrupting Blade section stack.
    Usage: @include('partials.render-section', ['section' => $section])
--}}
@php
    try {
        echo view('pagebuilder::partials.render-section', [
            'section' => $section,
            'forceVe' => $forceVe ?? false,
        ])->render();
    } catch (\Throwable $e) {
        if (config('app.debug')) {
            echo '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                . '<strong>Section #' . e($section->id ?? '?') . ' (' . e($section->section_type ?? '?') . '):</strong> '
                . e($e->getMessage()) . '</div>';
        }
    }
@endphp

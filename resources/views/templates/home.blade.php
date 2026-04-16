@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Home')

@section('content')
    {{-- Render all active sections (isolated to protect Blade section stack) --}}
    @if(isset($sections) && $sections->count() > 0)
        @foreach($sections as $section)
            @php
                try {
                    echo view('partials.render-section', ['section' => $section, 'forceVe' => $forceVe ?? false])->render();
                } catch (\Throwable $e) {
                    if (config('app.debug')) {
                        echo '<div style="background:#fee;border:1px solid #c00;color:#c00;padding:12px;margin:8px;border-radius:4px;">'
                            . '<strong>Section #' . e($section->id ?? '?') . ' (' . e($section->section_type ?? '?') . '):</strong> '
                            . e($e->getMessage()) . '</div>';
                    }
                }
            @endphp
        @endforeach
    @else
        {{-- Fallback: render body content if no sections --}}
        @if($content && $content->body)
            {!! $content->body !!}
        @endif
    @endif
@endsection

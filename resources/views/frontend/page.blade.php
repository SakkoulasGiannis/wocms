@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $content->title ?? $content->titles ?? $node->title ?? 'Page')

{{-- Pre-render sections BEFORE @section to protect Blade stack --}}
@php
    $__sectionsHtml = '';
    $__renderMode = $content->render_mode ?? $template->render_mode ?? null;

    // Load sections if page is in sections mode and none were pre-loaded
    if ($__renderMode === 'sections' && empty($sections) && $content && method_exists($content, 'activeSections')) {
        try {
            $sections = $content->activeSections()
                ->whereNull('parent_section_id')
                ->with(['sectionTemplate', 'childrenRecursive.sectionTemplate'])
                ->get();
        } catch (\Throwable $e) {
            $sections = collect();
        }
    }

    if (! empty($sections) && $sections->count() > 0) {
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
        <div class="bg-white">
            {{-- Featured Image Section --}}
            @if(isset($content->featured_images) && $content->featured_images)
                <div class="w-full h-96 bg-gray-200 overflow-hidden">
                    <img src="{{ $content->featured_images }}" alt="{{ $content->titles ?? 'Page' }}" class="w-full h-full object-cover">
                </div>
            @endif

            <div class="container mx-auto px-4 py-12">
                @if(isset($content->title) && $content->title)
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8">{{ $content->title }}</h1>
                @elseif(isset($content->titles) && $content->titles)
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8">{{ $content->titles }}</h1>
                @endif

                <div class="max-w-4xl mx-auto prose prose-lg">
                    @if(isset($content->body) && $content->body)
                        {!! $content->body !!}
                    @else
                        <div class="text-center text-gray-500 py-12">
                            <p>This page is empty. Edit it from the admin panel to add your content.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection

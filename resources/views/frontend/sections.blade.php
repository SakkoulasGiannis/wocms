@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $node->title ?? $title ?? 'Page')

@section('content')
    {{-- Render all active sections --}}
    @if(isset($sections) && $sections->count() > 0)
        @foreach($sections as $section)
            {{-- 1. Pre-rendered HTML (cached from template) --}}
            @if(!empty($section->rendered_html))
                {!! $section->rendered_html !!}

            {{-- 2. Template blade_file --}}
            @elseif($section->sectionTemplate?->blade_file && view()->exists($section->sectionTemplate->blade_file))
                @include($section->sectionTemplate->blade_file, ['section' => $section, 'content' => $section->content, 'settings' => $section->settings])

            {{-- 3. Legacy blade partial (frontend.sections.{section_type}) --}}
            @elseif(view()->exists('frontend.sections.' . $section->section_type))
                @include('frontend.sections.' . $section->section_type, ['section' => $section])

            {{-- 4. Component (section-{type}) --}}
            @elseif(view()->exists('components.sections.' . str_replace('_', '-', $section->section_type)))
                @php
                    try {
                        echo view('components.sections.' . str_replace('_', '-', $section->section_type), [
                            'content' => $section->content,
                            'settings' => $section->settings,
                        ])->render();
                    } catch (\Throwable $e) {
                        if (config('app.debug')) {
                            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-4"><strong>' . e($section->section_type) . ':</strong> ' . e($e->getMessage()) . '</div>';
                        }
                    }
                @endphp

            {{-- 5. Template render() inline --}}
            @elseif($section->sectionTemplate?->html_template)
                {!! $section->sectionTemplate->render($section->content ?? []) !!}

            {{-- 6. Error fallback (only in debug mode) --}}
            @elseif(config('app.debug'))
                <div class="container mx-auto px-4 py-8">
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                        <p>Section type "{{ $section->section_type }}" not found.</p>
                    </div>
                </div>
            @endif
        @endforeach
    @else
        <div class="container mx-auto px-4 py-12">
            <div class="text-center text-gray-500">
                <p>This page has no sections yet. Add sections from the admin panel.</p>
            </div>
        </div>
    @endif
@endsection
